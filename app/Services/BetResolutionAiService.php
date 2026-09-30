<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\BetAiResolution;
use App\Models\BetAiSourceCheck;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BetResolutionAiService
{
    private string $model = 'gemini-3.6-flash';

    public function resolve(Bet $bet): void
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            throw new RuntimeException(
                'Gemini API key is not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare resolution
        |--------------------------------------------------------------------------
        */

        $resolution = BetAiResolution::updateOrCreate(
            [
                'bet_id' => $bet->id,
            ],
            [
                'suggested_answer_id' => null,
                'confidence' => null,
                'summary' => null,
                'status' => 'processing',
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        /*
         * If we're retrying a previously failed resolution,
         * clear its old AI checks.
         */
        $resolution->sourceChecks()->delete();

        try {

            /*
            |--------------------------------------------------------------------------
            | Market data
            |--------------------------------------------------------------------------
            */

            $market = $this->buildMarketPayload(
                $bet
            );


            /*
            |--------------------------------------------------------------------------
            | Check supplied source URLs
            |--------------------------------------------------------------------------
            */

            $evaluations = [];

            foreach ($bet->sources as $source) {

                $result = $this->checkProvidedSource(
                    apiKey: $apiKey,
                    bet: $bet,
                    market: $market,
                    url: $source->url
                );

                $sourceCheck =
                    $this->saveSourceCheck(
                        bet: $bet,
                        resolution: $resolution,
                        sourceType: 'provided',
                        url: $source->url,
                        result: $result
                    );

                if (
                    $sourceCheck->is_success
                    && $sourceCheck->suggested_answer_id
                ) {
                    $evaluations[] = [
                        'answer_id' =>
                            $sourceCheck->suggested_answer_id,

                        'confidence' =>
                            (float) $sourceCheck->confidence,
                    ];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Google Search verification
            |--------------------------------------------------------------------------
            */

            $googleResult =
                $this->checkGoogleSearch(
                    apiKey: $apiKey,
                    bet: $bet,
                    market: $market
                );

            $googleUrl =
                $googleResult['url']
                ?? 'google-search';

            $googleCheck =
                $this->saveSourceCheck(
                    bet: $bet,
                    resolution: $resolution,
                    sourceType: 'google',
                    url: $googleUrl,
                    result: $googleResult
                );

            if (
                $googleCheck->is_success
                && $googleCheck->suggested_answer_id
            ) {
                $evaluations[] = [
                    'answer_id' =>
                        $googleCheck->suggested_answer_id,

                    'confidence' =>
                        (float) $googleCheck->confidence,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Aggregate result
            |--------------------------------------------------------------------------
            */

            $final =
                $this->aggregateResults(
                    bet: $bet,
                    evaluations: $evaluations
                );


            /*
            |--------------------------------------------------------------------------
            | No reliable winner
            |--------------------------------------------------------------------------
            */

            if (! $final['answer_id']) {

                $resolution->update([
                    'suggested_answer_id' => null,
                    'confidence' =>
                        $final['confidence'],

                    'summary' =>
                        'Automatic winner determination was unsuccessful. '
                        . 'Manual review is required.',

                    'status' =>
                        'manual_review',

                    'completed_at' =>
                        now(),
                ]);

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | AI found proposed winner
            |--------------------------------------------------------------------------
            */

            $resolution->update([
                'suggested_answer_id' =>
                    $final['answer_id'],

                'confidence' =>
                    $final['confidence'],

                'summary' =>
                    $final['summary'],

                'status' =>
                    'completed',

                'completed_at' =>
                    now(),
            ]);

        } catch (Throwable $e) {

            $resolution->update([
                'suggested_answer_id' => null,

                'confidence' => 0,

                'summary' =>
                    'Automatic winner determination failed because '
                    . 'the AI resolution process encountered an error. '
                    . 'Manual review is required.',

                'status' =>
                    'failed',

                'completed_at' =>
                    now(),
            ]);

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Market payload
    |--------------------------------------------------------------------------
    */

    private function buildMarketPayload(
        Bet $bet
    ): array {

        $translation =
            $bet->translations
                ->firstWhere(
                    'locale',
                    $bet->source_locale
                )
            ?? $bet->translations
            ->firstWhere(
                'locale',
                'en'
            )
            ?? $bet->translations
                ->first();


        $answers = [];

        foreach ($bet->answers as $answer) {

            $translation =
                $answer->translations
                    ->firstWhere(
                        'locale',
                        $bet->source_locale
                    )
                ?? $answer->translations
                ->firstWhere(
                    'locale',
                    'en'
                )
                ?? $answer->translations
                    ->first();

            $answers[] = [
                'id' => $answer->id,
                'title' =>
                    $translation?->title
                    ?? ('Answer #' . $answer->id),
            ];
        }


        /*
         * Restore Bet translation because variable was
         * reused above.
         */
        $betTranslation =
            $bet->translations
                ->firstWhere(
                    'locale',
                    $bet->source_locale
                )
            ?? $bet->translations
            ->firstWhere(
                'locale',
                'en'
            )
            ?? $bet->translations
                ->first();


        return [
            'bet_id' =>
                $bet->id,

            'title' =>
                $betTranslation?->title
                ?? 'Untitled market',

            'description' =>
                $betTranslation?->description
                ?? '',

            'finish_at' =>
                $bet->finish_at?->toIso8601String(),

            'answers' =>
                $answers,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Check user-provided URL
    |--------------------------------------------------------------------------
    */

    private function checkProvidedSource(
        string $apiKey,
        Bet $bet,
        array $market,
        string $url
    ): array {

        $prompt = $this->buildSourcePrompt(
            market: $market,
            url: $url
        );

        try {

            $response =
                $this->geminiRequest(
                    apiKey: $apiKey,
                    prompt: $prompt,
                    tools: [
                        [
                            'url_context' => new \stdClass(),
                        ],
                    ]
                );

        } catch (Throwable $e) {

            return [
                'success' => false,

                'answer_id' => null,

                'confidence' => 0,

                'title' => null,

                'interpretation' =>
                    'The source could not be evaluated.',

                'evidence' => null,

                'error' =>
                    $e->getMessage(),
            ];
        }


        return $this->parseEvaluation(
            response: $response,
            bet: $bet
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Google Search
    |--------------------------------------------------------------------------
    */

    private function checkGoogleSearch(
        string $apiKey,
        Bet $bet,
        array $market
    ): array {

        $prompt =
            $this->buildGooglePrompt(
                $market
            );

        try {

            $response =
                $this->geminiRequest(
                    apiKey: $apiKey,
                    prompt: $prompt,
                    tools: [
                        [
                            'google_search' => new \stdClass(),
                        ],
                        [
                            'url_context' => new \stdClass(),
                        ],
                    ]
                );

        } catch (Throwable $e) {

            return [
                'success' => false,

                'answer_id' => null,

                'confidence' => 0,

                'title' => 'Google Search',

                'url' => 'google-search',

                'interpretation' =>
                    'Google verification could not determine the result.',

                'evidence' => null,

                'error' =>
                    $e->getMessage(),
            ];
        }


        $result =
            $this->parseEvaluation(
                response: $response,
                bet: $bet
            );


        /*
         * Extract first cited web URL from Gemini grounding metadata.
         */
        $result['url'] =
            $this->extractFirstWebUrl(
                $response
            )
            ?? 'google-search';


        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Gemini API
    |--------------------------------------------------------------------------
    */

    private function geminiRequest(
        string $apiKey,
        string $prompt,
        array $tools
    ): array {

        $url =
            'https://generativelanguage.googleapis.com/'
            . 'v1beta/models/'
            . $this->model
            . ':generateContent';


        try {

            $response = Http::withHeaders([
                'x-goog-api-key' =>
                    $apiKey,

                'Content-Type' =>
                    'application/json',
            ])
                ->connectTimeout(10)
                ->timeout(90)
                ->retry(
                    2,
                    1500,
                    throw: false
                )
                ->post(
                    $url,
                    [
                        'contents' => [
                            [
                                'role' => 'user',

                                'parts' => [
                                    [
                                        'text' =>
                                            $prompt,
                                    ],
                                ],
                            ],
                        ],

                        'tools' =>
                            $tools,

                        'generationConfig' => [
                            'temperature' => 0.05,
                        ],
                    ]
                );

        } catch (ConnectionException $e) {

            throw new RuntimeException(
                'Unable to connect to Gemini API: '
                . $e->getMessage(),
                previous: $e
            );
        }


        if (! $response->successful()) {

            throw new RuntimeException(
                'Gemini API error HTTP '
                . $response->status()
                . ': '
                . $response->body()
            );
        }


        return $response->json();
    }


    /*
    |--------------------------------------------------------------------------
    | Parse AI evaluation
    |--------------------------------------------------------------------------
    */

    private function parseEvaluation(
        array $response,
        Bet $bet
    ): array {

        $text =
            data_get(
                $response,
                'candidates.0.content.parts.0.text'
            );


        if (
            ! is_string($text)
            || trim($text) === ''
        ) {

            return [
                'success' => false,

                'answer_id' => null,

                'confidence' => 0,

                'title' => null,

                'interpretation' =>
                    'AI returned no usable evaluation.',

                'evidence' => null,

                'error' =>
                    'Empty Gemini response.',
            ];
        }


        $json =
            $this->extractJson(
                $text
            );


        if (! is_array($json)) {

            return [
                'success' => false,

                'answer_id' => null,

                'confidence' => 0,

                'title' => null,

                'interpretation' =>
                    trim($text),

                'evidence' => null,

                'error' =>
                    'Gemini response was not valid JSON.',
            ];
        }


        $answerId =
            isset($json['answer_id'])
                ? (int) $json['answer_id']
                : null;


        /*
         * Very important:
         * never trust an arbitrary answer_id from AI.
         */
        if ($answerId) {

            $exists =
                $bet->answers
                    ->contains(
                        'id',
                        $answerId
                    );

            if (! $exists) {
                $answerId = null;
            }
        }


        $confidence =
            isset($json['confidence'])
                ? (float) $json['confidence']
                : 0;


        $confidence =
            max(
                0,
                min(
                    100,
                    $confidence
                )
            );


        return [
            'success' =>
                (bool) (
                    $json['success']
                    ?? false
                ),

            'answer_id' =>
                $answerId,

            'confidence' =>
                $confidence,

            'title' =>
                isset($json['source_title'])
                    ? trim(
                    (string) $json['source_title']
                )
                    : null,

            'interpretation' =>
                trim(
                    (string) (
                        $json['interpretation']
                        ?? 'No interpretation available.'
                    )
                ),

            'evidence' =>
                isset($json['evidence'])
                    ? trim(
                    (string) $json['evidence']
                )
                    : null,

            'error' =>
                isset($json['error'])
                    ? trim(
                    (string) $json['error']
                )
                    : null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Save individual source check
    |--------------------------------------------------------------------------
    */

    private function saveSourceCheck(
        Bet $bet,
        BetAiResolution $resolution,
        string $sourceType,
        string $url,
        array $result
    ): BetAiSourceCheck {

        return BetAiSourceCheck::create([
            'bet_id' =>
                $bet->id,

            'bet_ai_resolution_id' =>
                $resolution->id,

            'suggested_answer_id' =>
                $result['answer_id']
                ?? null,

            'source_type' =>
                $sourceType,

            'url' =>
                $url,

            'title' =>
                $result['title']
                ?? null,

            'confidence' =>
                $result['confidence']
                ?? 0,

            'interpretation' =>
                $result['interpretation']
                ?? 'No interpretation available.',

            'evidence' =>
                $result['evidence']
                ?? null,

            'is_success' =>
                $result['success']
                ?? false,

            'error' =>
                $result['error']
                ?? null,

            'checked_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Aggregate source results
    |--------------------------------------------------------------------------
    */

    private function aggregateResults(
        Bet $bet,
        array $evaluations
    ): array {

        if (empty($evaluations)) {

            return [
                'answer_id' => null,

                'confidence' => 0,

                'summary' =>
                    'Automatic winner determination was unsuccessful. '
                    . 'No source produced a reliable result.',
            ];
        }


        $scores = [];

        foreach ($evaluations as $evaluation) {

            $answerId =
                (int) $evaluation['answer_id'];

            $confidence =
                (float) $evaluation['confidence'];

            if (! isset($scores[$answerId])) {

                $scores[$answerId] = [
                    'count' => 0,
                    'score' => 0,
                ];
            }

            $scores[$answerId]['count']++;

            $scores[$answerId]['score'] +=
                $confidence;
        }


        uasort(
            $scores,
            function ($a, $b) {

                if (
                    $a['count']
                    === $b['count']
                ) {
                    return
                        $b['score']
                        <=>
                        $a['score'];
                }

                return
                    $b['count']
                    <=>
                    $a['count'];
            }
        );


        $winnerId =
            (int) array_key_first(
                $scores
            );

        $winner =
            $scores[$winnerId];


        $averageConfidence =
            $winner['count'] > 0
                ? $winner['score']
                / $winner['count']
                : 0;


        /*
         * Require at least one reasonably confident source.
         *
         * Later we can make this stricter, for example:
         * - 2 agreeing sources
         * - confidence >= 70
         */
        if ($averageConfidence < 55) {

            return [
                'answer_id' => null,

                'confidence' =>
                    round(
                        $averageConfidence,
                        2
                    ),

                'summary' =>
                    'Automatic winner determination was unsuccessful. '
                    . 'The available evidence was not reliable enough.',
            ];
        }


        /*
         * Detect a strong conflict.
         */
        if (count($scores) > 1) {

            $scoreValues =
                array_values($scores);

            $first =
                $scoreValues[0];

            $second =
                $scoreValues[1];

            if (
                $first['count']
                === $second['count']
                && abs(
                    $first['score']
                    - $second['score']
                ) < 20
            ) {

                return [
                    'answer_id' => null,

                    'confidence' =>
                        round(
                            $averageConfidence,
                            2
                        ),

                    'summary' =>
                        'Automatic winner determination was unsuccessful. '
                        . 'The checked sources provide conflicting results.',
                ];
            }
        }


        $answer =
            $bet->answers
                ->firstWhere(
                    'id',
                    $winnerId
                );


        $answerTranslation =
            $answer?->translations
                ->firstWhere(
                    'locale',
                    $bet->source_locale
                )
            ?? $answer?->translations
            ->firstWhere(
                'locale',
                'en'
            )
            ?? $answer?->translations
                ->first();


        return [
            'answer_id' =>
                $winnerId,

            'confidence' =>
                round(
                    $averageConfidence,
                    2
                ),

            'summary' =>
                'AI suggests "'
                . (
                    $answerTranslation?->title
                    ?? ('Answer #' . $winnerId)
                )
                . '" as the winning answer based on '
                . $winner['count']
                . ' supporting source check(s).',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Prompt for supplied URL
    |--------------------------------------------------------------------------
    */

    private function buildSourcePrompt(
        array $market,
        string $url
    ): string {

        $marketJson =
            json_encode(
                $market,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );


        return <<<PROMPT
You are resolving a prediction market.

You must inspect the following source URL:

{$url}

Use the URL context tool to read the actual page.

MARKET:

{$marketJson}

Determine whether the page contains enough reliable information to determine the winning answer.

Important rules:

- Use only facts supported by the source.
- Do not guess.
- Do not invent missing results.
- Respect the market title, description and finish date.
- The winning answer must be one of the supplied answer IDs.
- If the source does not contain enough information, answer_id must be null.
- If the source is inaccessible, irrelevant, ambiguous or does not prove a result, success must be false.
- confidence is from 0 to 100.
- Keep evidence concise.
- Return JSON only.
- Do not use Markdown.

Return exactly this structure:

{
  "success": true,
  "answer_id": 123,
  "confidence": 95,
  "source_title": "Page title",
  "interpretation": "Short explanation of what the source says.",
  "evidence": "Specific fact supporting the conclusion.",
  "error": null
}

If no result can be determined:

{
  "success": false,
  "answer_id": null,
  "confidence": 0,
  "source_title": "Page title if known",
  "interpretation": "Automatic winner determination was unsuccessful from this source.",
  "evidence": null,
  "error": "Reason why the result could not be determined."
}
PROMPT;
    }


    /*
    |--------------------------------------------------------------------------
    | Prompt for Google
    |--------------------------------------------------------------------------
    */

    private function buildGooglePrompt(
        array $market
    ): string {

        $marketJson =
            json_encode(
                $market,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );


        return <<<PROMPT
You are resolving a prediction market.

MARKET:

{$marketJson}

Use Google Search to find current information about the event.

Prefer authoritative sources in this order:

1. Official organization, regulator, league, government or company.
2. Primary source directly connected to the event.
3. Major reputable news organization.
4. Other reliable public source.

Avoid sponsored or promotional pages when possible.

Use the URL context tool when a search result needs deeper inspection.

Determine the winning answer only when the evidence clearly supports one of the supplied answer IDs.

Important:

- Do not guess.
- Do not invent facts.
- Respect the market finish date.
- The result must correspond to one of the supplied answer IDs.
- If reliable public information cannot determine the result, answer_id must be null.
- confidence is from 0 to 100.
- Return JSON only.
- Do not include Markdown.

Return:

{
  "success": true,
  "answer_id": 123,
  "confidence": 95,
  "source_title": "Source title",
  "interpretation": "Short interpretation.",
  "evidence": "Specific evidence supporting the selected answer.",
  "error": null
}

If you cannot reliably determine the winner:

{
  "success": false,
  "answer_id": null,
  "confidence": 0,
  "source_title": null,
  "interpretation": "Automatic winner determination was unsuccessful.",
  "evidence": null,
  "error": "No reliable result was found."
}
PROMPT;
    }


    /*
    |--------------------------------------------------------------------------
    | Extract JSON
    |--------------------------------------------------------------------------
    */

    private function extractJson(
        string $text
    ): ?array {

        $text =
            trim($text);


        /*
         * Remove Markdown fences if Gemini ignored instruction.
         */
        $text = preg_replace(
            '/^```(?:json)?\s*/i',
            '',
            $text
        );

        $text = preg_replace(
            '/\s*```$/',
            '',
            $text
        );


        $decoded =
            json_decode(
                $text,
                true
            );


        if (
            json_last_error()
            === JSON_ERROR_NONE
            && is_array($decoded)
        ) {
            return $decoded;
        }


        /*
         * Try extracting first JSON object.
         */
        $start =
            strpos(
                $text,
                '{'
            );

        $end =
            strrpos(
                $text,
                '}'
            );


        if (
            $start === false
            || $end === false
            || $end <= $start
        ) {
            return null;
        }


        $json =
            substr(
                $text,
                $start,
                $end - $start + 1
            );


        $decoded =
            json_decode(
                $json,
                true
            );


        return
            is_array($decoded)
                ? $decoded
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Google grounding URL
    |--------------------------------------------------------------------------
    */

    private function extractFirstWebUrl(
        array $response
    ): ?string {

        $chunks =
            data_get(
                $response,
                'candidates.0.groundingMetadata.groundingChunks',
                []
            );


        if (! is_array($chunks)) {
            return null;
        }


        foreach ($chunks as $chunk) {

            $uri =
                data_get(
                    $chunk,
                    'web.uri'
                );

            if (
                is_string($uri)
                && $uri !== ''
            ) {
                return $uri;
            }
        }


        return null;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiTranslationService
{
    /**
     * Models are tried in order.
     *
     * If a model is overloaded or temporarily unavailable,
     * the next model is used automatically.
     */
    private array $models = [
        'gemini-3.5-flash-lite',
        'gemini-3.8-flash',
        'gemini-3.7-flash',
        'gemini-3.6-flash',
        'gemini-3.5-flash',
    ];

    /**
     * HTTP statuses that should trigger fallback
     * to another Gemini model.
     */
    private array $fallbackStatuses = [
        429,
        500,
        502,
        503,
        504,
    ];

    public function translateMarket(
        string $sourceLocale,
        string $targetLocale,
        string $title,
        string $description,
        array $answers
    ): array {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            throw new RuntimeException(
                'Gemini API key is not configured.'
            );
        }

        $payload = [
            'source_language' => $sourceLocale,
            'target_language' => $targetLocale,
            'title' => $title,
            'description' => $description,
            'answers' => array_values($answers),
        ];

        $prompt = $this->buildPrompt(
            $sourceLocale,
            $targetLocale
        );

        $errors = [];

        foreach ($this->models as $model) {

            try {

                $response = $this->requestTranslation(
                    model: $model,
                    apiKey: $apiKey,
                    prompt: $prompt,
                    payload: $payload
                );

            } catch (ConnectionException $e) {

                $errors[] =
                    $model
                    . ': connection error: '
                    . $e->getMessage();

                /*
                 * Try next model.
                 */
                continue;

            } catch (Throwable $e) {

                $errors[] =
                    $model
                    . ': '
                    . $e->getMessage();

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            if ($response->successful()) {

                try {

                    return $this->parseResponse(
                        $response,
                        $model
                    );

                } catch (Throwable $e) {

                    $errors[] =
                        $model
                        . ': invalid response: '
                        . $e->getMessage();

                    /*
                     * A malformed response should not
                     * completely break translation.
                     *
                     * Try another model.
                     */
                    continue;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | TEMPORARY GEMINI ERROR
            |--------------------------------------------------------------------------
            |
            | 429 / 500 / 502 / 503 / 504
            |
            | Try the next model.
            |
            */

            if (
                in_array(
                    $response->status(),
                    $this->fallbackStatuses,
                    true
                )
            ) {

                $errors[] =
                    $model
                    . ' HTTP '
                    . $response->status()
                    . ': '
                    . $this->extractErrorMessage(
                        $response
                    );

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | NON-RECOVERABLE ERROR
            |--------------------------------------------------------------------------
            |
            | For example:
            |
            | 400 invalid request
            | 401 invalid key
            | 403 permission problem
            |
            | Switching model usually will not help.
            |
            */

            throw new RuntimeException(
                'Gemini API error using '
                . $model
                . ' HTTP '
                . $response->status()
                . ': '
                . $this->extractErrorMessage(
                    $response
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ALL MODELS FAILED
        |--------------------------------------------------------------------------
        */

        throw new RuntimeException(
            "All Gemini translation models failed.\n"
            . implode(
                "\n",
                $errors
            )
        );
    }


    private function requestTranslation(
        string $model,
        string $apiKey,
        string $prompt,
        array $payload
    ): Response {

        $url =
            'https://generativelanguage.googleapis.com/'
            . 'v1beta/models/'
            . $model
            . ':generateContent';

        return Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->connectTimeout(8)
            ->timeout(30)

            /*
             * Do not excessively retry the same overloaded
             * model. One retry is enough before fallback.
             */
            ->retry(
                1,
                500,
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
                                        $prompt
                                        . "\n\n"
                                        . json_encode(
                                            $payload,
                                            JSON_UNESCAPED_UNICODE
                                            | JSON_UNESCAPED_SLASHES
                                        ),
                                ],
                            ],
                        ],
                    ],

                    'generationConfig' => [

                        /*
                         * Translation does not need
                         * creative generation.
                         */
                        'temperature' => 0.1,

                        /*
                         * JSON response only.
                         */
                        'responseMimeType' =>
                            'application/json',

                        'responseSchema' => [
                            'type' => 'OBJECT',

                            'properties' => [

                                'title' => [
                                    'type' => 'STRING',
                                ],

                                'description' => [
                                    'type' => 'STRING',
                                ],

                                'answers' => [
                                    'type' => 'ARRAY',

                                    'items' => [
                                        'type' => 'STRING',
                                    ],
                                ],
                            ],

                            'required' => [
                                'title',
                                'description',
                                'answers',
                            ],
                        ],
                    ],
                ]
            );
    }


    private function parseResponse(
        Response $response,
        string $model
    ): array {

        $json = $response->json();

        $text = data_get(
            $json,
            'candidates.0.content.parts.0.text'
        );

        if (
            ! is_string($text)
            || trim($text) === ''
        ) {

            throw new RuntimeException(
                'Empty response from '
                . $model
                . ': '
                . json_encode(
                    $json,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );
        }


        $result = json_decode(
            $text,
            true
        );


        if (
            ! is_array($result)
            || ! array_key_exists(
                'title',
                $result
            )
            || ! array_key_exists(
                'description',
                $result
            )
            || ! isset(
                $result['answers']
            )
            || ! is_array(
                $result['answers']
            )
        ) {

            throw new RuntimeException(
                'Gemini returned invalid JSON: '
                . $text
            );
        }


        return [
            'title' =>
                trim(
                    (string) $result['title']
                ),

            'description' =>
                trim(
                    (string) $result['description']
                ),

            'answers' =>
                array_values(
                    array_map(
                        fn ($answer) =>
                        trim(
                            (string) $answer
                        ),
                        $result['answers']
                    )
                ),
        ];
    }


    private function extractErrorMessage(
        Response $response
    ): string {

        $json = $response->json();

        $message = data_get(
            $json,
            'error.message'
        );

        if (
            is_string($message)
            && trim($message) !== ''
        ) {
            return trim($message);
        }

        return trim(
            $response->body()
        );
    }


    private function buildPrompt(
        string $sourceLocale,
        string $targetLocale
    ): string {

        return <<<PROMPT
You are a professional translator for prediction market content.

Translate the supplied prediction market from {$sourceLocale} to {$targetLocale}.

Requirements:

- Preserve the exact meaning.
- Do not add new facts.
- Do not remove information.
- Do not change dates.
- Do not change prices.
- Do not change percentages.
- Do not change numbers.
- Do not change cryptocurrency tickers.
- Do not translate proper names unless a standard localized form exists.
- Preserve football club names, company names, organizations and product names correctly.
- Use natural professional language in the target language.
- Translate prediction-market terminology naturally.
- Preserve the meaning and order of every answer.
- The number of translated answers must exactly match the number of source answers.
- Do not explain the translation.
- Do not include Markdown.
- Return valid JSON only.

Required JSON structure:

{
  "title": "translated title",
  "description": "translated description",
  "answers": [
    "translated answer 1",
    "translated answer 2"
  ]
}

CONTENT:
PROMPT;
    }
}

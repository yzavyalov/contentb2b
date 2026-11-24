<?php

namespace App\Http\Controllers\Api;

use App\Enums\BetStatus;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Category;
use App\Models\Country;
use App\Models\CountryGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ContentMarketImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'source_market_id' => ['required', 'integer', 'min:1'],
            'source_locale' => ['required', 'string', 'max:10'],
            'finish_at' => ['nullable', 'date'],

            'question' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'resolution_rules' => ['nullable', 'string'],

            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10', 'distinct'],
            'translations.*.question' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
            'translations.*.resolution_rules' => ['nullable', 'string'],

            'outcomes' => ['required', 'array', 'min:2'],
            'outcomes.*.code' => ['required', 'string', 'max:100', 'distinct'],
            'outcomes.*.title' => ['required', 'string', 'max:255'],
            'outcomes.*.sort_order' => ['required', 'integer', 'min:0'],
            'outcomes.*.translations' => ['sometimes', 'array'],
            'outcomes.*.translations.*.locale' => ['required', 'string', 'max:10'],
            'outcomes.*.translations.*.title' => ['required', 'string', 'max:255'],

            'category' => ['required', 'array'],
            'category.slug' => ['required', 'string', 'max:255'],
            'category.name' => ['required', 'string', 'max:255'],

            'country' => ['nullable', 'array'],
            'country.iso2' => ['required_with:country', 'string', 'size:2'],
            'country.name' => ['required_with:country', 'string', 'max:255'],

            'country_groups' => ['sometimes', 'array'],
            'country_groups.*.slug' => ['required', 'string', 'max:255'],
            'country_groups.*.name' => ['required', 'string', 'max:255'],
            'country_groups.*.countries' => ['sometimes', 'array'],
            'country_groups.*.countries.*.iso2' => ['required', 'string', 'size:2'],
            'country_groups.*.countries.*.name' => ['required', 'string', 'max:255'],

            'sources' => ['sometimes', 'array', 'max:3'],
            'sources.*' => ['required', 'url', 'max:2048'],

            'image' => ['nullable', 'array'],
            'image.mime_type' => ['required_with:image', 'in:image/jpeg,image/png,image/webp'],
            'image.data_base64' => ['required_with:image', 'string'],
        ])->validate();

        $userId = config('services.content_import.user_id');

        if (! filter_var($userId, FILTER_VALIDATE_INT)) {
            return response()->json([
                'message' => 'Import administrator is not configured.',
            ], 503);
        }

        $user = User::find((int) $userId);

        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Import administrator is invalid.',
            ], 503);
        }

        /*
         * Validate the complete image before any database writes.
         */
        $imageBytes = null;
        $imageExtension = null;

        if (! empty($data['image'])) {
            $encoded = $data['image']['data_base64'];

            // Prevent excessively large encoded payloads.
            if (strlen($encoded) > 7_000_000) {
                throw ValidationException::withMessages([
                    'image' => 'Image exceeds the maximum size.',
                ]);
            }

            $imageBytes = base64_decode($encoded, true);

            if ($imageBytes === false || strlen($imageBytes) > 5 * 1024 * 1024) {
                throw ValidationException::withMessages([
                    'image' => 'Invalid image or image exceeds 5 MB.',
                ]);
            }

            $imageInfo = @getimagesizefromstring($imageBytes);

            if ($imageInfo === false) {
                throw ValidationException::withMessages([
                    'image' => 'Invalid image content.',
                ]);
            }

            $actualMime = $imageInfo['mime'] ?? null;

            if ($actualMime !== $data['image']['mime_type']) {
                throw ValidationException::withMessages([
                    'image' => 'Image MIME type does not match its content.',
                ]);
            }

            $imageExtension = match ($actualMime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => null,
            };

            if ($imageExtension === null) {
                throw ValidationException::withMessages([
                    'image' => 'Unsupported image format.',
                ]);
            }
        }

        $newImagePath = null;

        try {
            /*
             * Serialize imports for the same source market.
             * The unique database constraint is the final protection.
             */
            $result = DB::transaction(function () use (
                $data,
                $user,
                $imageBytes,
                $imageExtension,
                &$newImagePath
            ) {
                $existingImport = DB::table('content_market_imports')
                    ->where('source_market_id', $data['source_market_id'])
                    ->lockForUpdate()
                    ->first();

                if ($existingImport) {
                    return [
                        'created' => false,
                        'bet_id' => (int) $existingImport->bet_id,
                    ];
                }

                $bet = Bet::create([
                    'created_by_user_id' => $user->id,
                    'supervisor_user_id' => null,
                    'status' => BetStatus::DRAFT,
                    'source_locale' => $data['source_locale'],
                    'finish_at' => $data['finish_at'] ?? null,
                    'winning_answer_id' => null,
                ]);

                /*
                 * Category.
                 */
                $category = Category::firstOrCreate(
                    ['slug' => $data['category']['slug']],
                    [
                        'name' => $data['category']['name'],
                        'is_active' => true,
                    ]
                );

                $bet->categories()->sync([$category->id]);

                /*
                 * Country.
                 */
                $countryIds = [];

                if (! empty($data['country'])) {
                    $country = $this->findOrCreateCountry($data['country']);
                    $countryIds[] = $country->id;
                }

                $bet->countries()->sync($countryIds);

                /*
                 * Country groups.
                 */
                $countryGroupIds = [];

                foreach ($data['country_groups'] ?? [] as $groupData) {
                    $group = CountryGroup::firstOrCreate(
                        ['slug' => $groupData['slug']],
                        ['name' => $groupData['name']]
                    );

                    $groupCountryIds = [];

                    foreach ($groupData['countries'] ?? [] as $countryData) {
                        $groupCountryIds[] = $this
                            ->findOrCreateCountry($countryData)
                            ->id;
                    }

                    if ($groupCountryIds !== []) {
                        // Do not remove countries already assigned to a group.
                        $group->countries()->syncWithoutDetaching(
                            array_unique($groupCountryIds)
                        );
                    }

                    $countryGroupIds[] = $group->id;
                }

                $bet->countryGroups()->sync(
                    array_unique($countryGroupIds)
                );

                /*
                 * Market translations.
                 */
                $translations = [];

                $translations[$data['source_locale']] = [
                    'question' => $data['question'],
                    'description' => $data['description'] ?? null,
                    'resolution_rules' => $data['resolution_rules'] ?? null,
                ];

                foreach ($data['translations'] ?? [] as $translation) {
                    $translations[$translation['locale']] = [
                        'question' => $translation['question'],
                        'description' => $translation['description'] ?? null,
                        'resolution_rules' => $translation['resolution_rules'] ?? null,
                    ];
                }

                foreach ($translations as $locale => $translation) {
                    $description = $this->combineDescription(
                        $translation['description'],
                        $translation['resolution_rules']
                    );

                    $bet->translations()->create([
                        'locale' => $locale,
                        'title' => $translation['question'],
                        'description' => $description,
                    ]);
                }

                /*
                 * Outcomes and their translations.
                 */
                $outcomes = collect($data['outcomes'])
                    ->sortBy('sort_order')
                    ->values();

                foreach ($outcomes as $index => $outcomeData) {
                    $answer = $bet->answers()->create([
                        'sort_order' => $index,
                    ]);

                    $answerTitles = [
                        $data['source_locale'] => $outcomeData['title'],
                    ];

                    foreach ($outcomeData['translations'] ?? [] as $translation) {
                        $answerTitles[$translation['locale']] = $translation['title'];
                    }

                    foreach ($answerTitles as $locale => $title) {
                        $answer->translations()->create([
                            'locale' => $locale,
                            'title' => $title,
                        ]);
                    }
                }

                /*
                 * Resolution sources.
                 */
                foreach (array_values(array_unique($data['sources'] ?? [])) as $index => $url) {
                    $bet->sources()->create([
                        'url' => $url,
                        'sort_order' => $index + 1,
                    ]);
                }

                /*
                 * Store image using the same directory as the existing UI.
                 */
                if ($imageBytes !== null) {
                    $newImagePath = 'bets/'
                        . now()->format('Y/m')
                        . '/'
                        . (string) \Illuminate\Support\Str::uuid()
                        . '.'
                        . $imageExtension;

                    if (! Storage::disk('public')->put($newImagePath, $imageBytes)) {
                        throw new RuntimeException('Unable to store imported image.');
                    }

                    $bet->image_path = $newImagePath;
                    $bet->save();
                }

                /*
                 * Link external market ID to local Bet.
                 */
                DB::table('content_market_imports')->insert([
                    'source_market_id' => $data['source_market_id'],
                    'bet_id' => $bet->id,
                    'last_imported_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [
                    'created' => true,
                    'bet_id' => $bet->id,
                ];
            });

            return response()->json([
                'message' => $result['created']
                    ? 'Market imported successfully.'
                    : 'Market already imported.',
                'source_market_id' => $data['source_market_id'],
                'bet_id' => $result['bet_id'],
                'created' => $result['created'],
                'status' => 'draft',
            ], $result['created'] ? 201 : 200);
        } catch (\Throwable $e) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $e;
        }
    }

    private function findOrCreateCountry(array $data): Country
    {
        return Country::firstOrCreate(
            ['code' => strtoupper($data['iso2'])],
            ['name' => $data['name']]
        );
    }

    private function combineDescription(
        ?string $description,
        ?string $resolutionRules
    ): ?string {
        $description = trim($description ?? '');
        $resolutionRules = trim($resolutionRules ?? '');

        if ($resolutionRules !== '') {
            $description .= ($description !== '' ? "\n\n" : '')
                . "Resolution Rules:\n"
                . $resolutionRules;
        }

        if (mb_strlen($description) > 20000) {
            throw ValidationException::withMessages([
                'description' => 'Description and resolution rules exceed 20,000 characters.',
            ]);
        }

        return $description !== '' ? $description : null;
    }
}

<?php

use App\Enums\BetStatus;
use App\Jobs\SendMarketCancelledToMerchant;
use App\Jobs\SendMarketResolvedToMerchant;
use App\Models\Bet;
use App\Models\Category;
use App\Models\Country;
use App\Models\CountryGroup;
use App\Services\AiTranslationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\ProcessAutomaticMarketDelivery;

new class extends Component
{
    use WithFileUploads;

    public $image = null;
    public string $finishAt = '';
    public ?int $betId = null;
    public ?string $existingImagePath = null;
    public bool $editMode = false;

    public array $categoryIds = [];
    public string $categorySearch = '';
    public bool $showCreateCategory = false;
    public string $newCategoryName = '';

    public string $sourceLocale = 'en';
    public string $sourceLanguageChoice = 'en';
    public string $newLocale = '';
    public array $translations = [];
    public array $aiTranslatedLocales = [];
    public ?string $translationError = null;
    public ?string $translationNotice = null;

    public array $answers = [];
    public array $sources = [''];

    public bool $isGlobal = true;
    public array $countryIds = [];
    public array $countryGroupIds = [];
    public string $countrySearch = '';

    public ?int $finalAnswerId = null;

    public array $availableLanguages = [
        'en' => 'English',
        'es' => 'Spanish',
        'de' => 'German',
        'fr' => 'French',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'pl' => 'Polish',
        'nl' => 'Dutch',
        'cs' => 'Czech',
        'sk' => 'Slovak',
        'hu' => 'Hungarian',
        'bg' => 'Bulgarian',
        'el' => 'Greek',
        'tr' => 'Turkish',
        'ru' => 'Russian',
        'uk' => 'Ukrainian',
        'ar' => 'Arabic',
    ];

    public string $supervisorStatus = '';

    public function mount(?int $betId = null): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && (
                $user->isContentManager()
                || $user->isContentSupervisor()
                || $user->isAdmin()
            ),
            403
        );

        $this->betId = $betId;
        $this->editMode = $betId !== null;

        if ($this->editMode) {
            $this->loadBet();
            return;
        }

        $this->initializeNewBet();
    }

    private function initializeNewBet(): void
    {
        $this->sourceLocale = 'en';
        $this->sourceLanguageChoice = 'en';
        $this->translations = [
            'en' => [
                'title' => '',
                'description' => '',
            ],
        ];
        $this->answers = [
            ['titles' => ['en' => '']],
            ['titles' => ['en' => '']],
        ];
        $this->sources = [''];
    }

    private function loadBet(): void
    {
        $bet = Bet::query()
            ->with([
                'translations',
                'answers.translations',
                'sources',
                'categories',
                'countries',
                'countryGroups',
                'aiResolution.suggestedAnswer.translations',
                'aiResolution.sourceChecks.suggestedAnswer.translations',
            ])
            ->findOrFail($this->betId);

        $this->authorizeEditableBet($bet);

        $this->sourceLocale = $bet->source_locale ?: 'en';
        $this->sourceLanguageChoice = $this->sourceLocale;
        $this->finishAt = $bet->finish_at?->format('Y-m-d\TH:i') ?? '';
        $this->existingImagePath = $bet->image_path;

        $this->translations = [];

        foreach ($bet->translations as $translation) {
            $this->translations[$translation->locale] = [
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',
            ];
        }

        if (! isset($this->translations[$this->sourceLocale])) {
            $this->translations[$this->sourceLocale] = [
                'title' => '',
                'description' => '',
            ];
        }

        $this->answers = [];

        foreach ($bet->answers->sortBy('sort_order') as $answer) {
            $titles = [];

            foreach ($answer->translations as $translation) {
                $titles[$translation->locale] = $translation->title ?? '';
            }

            foreach (array_keys($this->translations) as $locale) {
                $titles[$locale] ??= '';
            }

            $this->answers[] = ['titles' => $titles];
        }

        while (count($this->answers) < 2) {
            $titles = [];

            foreach (array_keys($this->translations) as $locale) {
                $titles[$locale] = '';
            }

            $this->answers[] = ['titles' => $titles];
        }

        $this->sources = $bet->sources
            ->sortBy('sort_order')
            ->pluck('url')
            ->values()
            ->toArray();

        if ($this->sources === []) {
            $this->sources = [''];
        }

        $this->categoryIds = $bet->categories
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $this->countryIds = $bet->countries
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $this->countryGroupIds = $bet->countryGroups
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $this->isGlobal = $this->countryIds === [] && $this->countryGroupIds === [];

        $status = $bet->status instanceof BetStatus
            ? $bet->status
            : BetStatus::tryFrom((string) $bet->status);

        $this->supervisorStatus = $status?->value ?? '';

        if ($status === BetStatus::RESOLVING) {
            $this->finalAnswerId = $bet->aiResolution?->suggested_answer_id;
        } elseif ($status === BetStatus::RESOLVED) {
            $this->finalAnswerId = $bet->winning_answer_id;
        }
    }

    private function authorizeEditableBet(Bet $bet): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        /*
        |--------------------------------------------------------------------------
        | ADMIN / SUPERVISOR
        |--------------------------------------------------------------------------
        |
        | Могут редактировать любой Bet.
        |
        */

        if (
            $user->isAdmin()
            || $user->isContentSupervisor()
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CONTENT MANAGER
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $user->isContentManager()
            && $bet->created_by_user_id === $user->id,
            403
        );

        $status = $bet->status instanceof BetStatus
            ? $bet->status
            : BetStatus::tryFrom((string) $bet->status);

        abort_unless(
            in_array(
                $status,
                [
                    BetStatus::DRAFT,
                    BetStatus::REJECTED,
                ],
                true
            ),
            403,
            'This bet can no longer be edited.'
        );
    }

    public function applySourceLanguage(): void
    {
        $locale = trim($this->sourceLanguageChoice);

        if ($locale === '' || ! array_key_exists($locale, $this->availableLanguages)) {
            $this->addError('sourceLanguageChoice', 'Invalid source language.');
            return;
        }

        if ($locale === $this->sourceLocale) {
            return;
        }

        if (count($this->translations) > 1) {
            $this->sourceLanguageChoice = $this->sourceLocale;
            $this->addError(
                'sourceLanguageChoice',
                'Remove additional translations before changing the original language.'
            );
            return;
        }

        if ($this->hasSourceContent()) {
            $this->sourceLanguageChoice = $this->sourceLocale;
            $this->addError(
                'sourceLanguageChoice',
                'Choose the original language before entering content.'
            );
            return;
        }

        $oldLocale = $this->sourceLocale;
        unset($this->translations[$oldLocale]);

        $this->sourceLocale = $locale;
        $this->sourceLanguageChoice = $locale;
        $this->translations = [
            $locale => [
                'title' => '',
                'description' => '',
            ],
        ];

        foreach ($this->answers as $index => $answer) {
            $this->answers[$index]['titles'] = [$locale => ''];
        }

        $this->aiTranslatedLocales = [];
        $this->translationError = null;
        $this->translationNotice = null;
        $this->resetErrorBag('sourceLanguageChoice');
    }

    private function hasSourceContent(): bool
    {
        $source = $this->translations[$this->sourceLocale] ?? [];

        if (
            trim((string) ($source['title'] ?? '')) !== '' ||
            trim((string) ($source['description'] ?? '')) !== ''
        ) {
            return true;
        }

        foreach ($this->answers as $answer) {
            if (trim((string) ($answer['titles'][$this->sourceLocale] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    public function addLanguage(): void
    {
        $locale = $this->validatedNewLocale();

        if ($locale === null) {
            return;
        }

        $this->addLocaleToForm($locale);

        if ($this->hasSourceContent()) {
            $this->translateLanguage($locale);
        } else {
            $this->translationNotice =
                $this->availableLanguages[$locale]
                . ' was added. Fill the original content, then click AI Translate.';
        }

        $this->newLocale = '';
        $this->resetErrorBag('newLocale');
    }

    public function addLanguageWithoutTranslation(): void
    {
        $locale = $this->validatedNewLocale();

        if ($locale === null) {
            return;
        }

        $this->addLocaleToForm($locale);
        $this->newLocale = '';
        $this->resetErrorBag('newLocale');
    }

    private function validatedNewLocale(): ?string
    {
        $this->translationError = null;
        $this->translationNotice = null;

        $locale = trim($this->newLocale);

        if ($locale === '') {
            $this->addError('newLocale', 'Select a language.');
            return null;
        }

        if (! array_key_exists($locale, $this->availableLanguages)) {
            $this->addError('newLocale', 'Invalid language.');
            return null;
        }

        if ($locale === $this->sourceLocale || isset($this->translations[$locale])) {
            $this->addError('newLocale', 'This language has already been added.');
            return null;
        }

        return $locale;
    }

    private function addLocaleToForm(string $locale): void
    {
        $this->translations[$locale] = [
            'title' => '',
            'description' => '',
        ];

        foreach ($this->answers as $index => $answer) {
            $this->answers[$index]['titles'][$locale] = '';
        }
    }

    public function translateLanguage(string $targetLocale): void
    {
        $this->translationError = null;
        $this->translationNotice = null;

        if (
            $targetLocale === $this->sourceLocale ||
            ! isset($this->translations[$targetLocale]) ||
            ! array_key_exists($targetLocale, $this->availableLanguages)
        ) {
            return;
        }

        $source = $this->translations[$this->sourceLocale] ?? null;

        if (! $source) {
            $this->translationError = 'Original language content was not found.';
            return;
        }

        $sourceTitle = trim((string) ($source['title'] ?? ''));
        $sourceDescription = trim((string) ($source['description'] ?? ''));
        $answerTitles = [];

        foreach ($this->answers as $answer) {
            $answerTitles[] = trim((string) ($answer['titles'][$this->sourceLocale] ?? ''));
        }

        $hasAnswerContent = collect($answerTitles)->contains(fn ($title) => $title !== '');

        if ($sourceTitle === '' && $sourceDescription === '' && ! $hasAnswerContent) {
            $this->translationError =
                'Fill the original content before requesting an AI translation.';
            return;
        }

        try {
            /** @var AiTranslationService $translator */
            $translator = app(AiTranslationService::class);

            $translated = $translator->translateMarket(
                $this->sourceLocale,
                $targetLocale,
                $sourceTitle,
                $sourceDescription,
                $answerTitles
            );

            $this->translations[$targetLocale]['title'] =
                trim((string) ($translated['title'] ?? ''));

            $this->translations[$targetLocale]['description'] =
                trim((string) ($translated['description'] ?? ''));

            $translatedAnswers = $translated['answers'] ?? [];

            foreach ($this->answers as $answerIndex => $answer) {
                $this->answers[$answerIndex]['titles'][$targetLocale] =
                    trim((string) ($translatedAnswers[$answerIndex] ?? ''));
            }

            if (! in_array($targetLocale, $this->aiTranslatedLocales, true)) {
                $this->aiTranslatedLocales[] = $targetLocale;
            }

            $this->translationNotice =
                ($this->availableLanguages[$targetLocale] ?? strtoupper($targetLocale))
                . ' translation generated. Please review it before submitting.';
        } catch (\Throwable $e) {
            report($e);
            $this->translationError =
                'AI translation is temporarily unavailable. You can fill this language manually.';
        }
    }

    public function removeLanguage(string $locale): void
    {
        if ($locale === $this->sourceLocale) {
            return;
        }

        unset($this->translations[$locale]);

        foreach ($this->answers as $index => $answer) {
            unset($this->answers[$index]['titles'][$locale]);
        }

        $this->aiTranslatedLocales = array_values(array_filter(
            $this->aiTranslatedLocales,
            fn ($item) => $item !== $locale
        ));

        $this->translationError = null;
        $this->translationNotice = null;
    }

    public function addAnswer(): void
    {
        $titles = [];

        foreach (array_keys($this->translations) as $locale) {
            $titles[$locale] = '';
        }

        $this->answers[] = ['titles' => $titles];
    }

    public function removeAnswer(int $index): void
    {
        if (count($this->answers) <= 2 || ! isset($this->answers[$index])) {
            return;
        }

        unset($this->answers[$index]);
        $this->answers = array_values($this->answers);
    }

    public function openCreateCategory(): void
    {
        $this->showCreateCategory = true;
        $this->newCategoryName = '';
        $this->resetErrorBag('newCategoryName');
    }

    public function cancelCreateCategory(): void
    {
        $this->showCreateCategory = false;
        $this->newCategoryName = '';
        $this->resetErrorBag('newCategoryName');
    }

    public function createCategory(): void
    {
        $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($this->newCategoryName);
        $slug = Str::slug($name);

        if ($slug === '') {
            $this->addError('newCategoryName', 'Unable to generate a valid category slug.');
            return;
        }

        $category = Category::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'is_active' => true,
            ]
        );

        $selected = array_map('intval', $this->categoryIds);

        if (! in_array((int) $category->id, $selected, true)) {
            $selected[] = (int) $category->id;
        }

        $this->categoryIds = array_values(array_unique($selected));
        $this->categorySearch = '';
        $this->newCategoryName = '';
        $this->showCreateCategory = false;
        $this->resetErrorBag('newCategoryName');
    }

    public function addSource(): void
    {
        if (count($this->sources) < 3) {
            $this->sources[] = '';
        }
    }

    public function removeSource(int $index): void
    {
        if (count($this->sources) <= 1 || ! isset($this->sources[$index])) {
            return;
        }

        unset($this->sources[$index]);
        $this->sources = array_values($this->sources);
    }

    public function updatedIsGlobal(bool $value): void
    {
        if ($value) {
            $this->countryIds = [];
            $this->countryGroupIds = [];
            $this->countrySearch = '';
            $this->resetErrorBag('geography');
        }
    }

    public function removeCountry(int $countryId): void
    {
        $this->countryIds = array_values(array_filter(
            $this->countryIds,
            fn ($id) => (int) $id !== $countryId
        ));
    }

    public function saveDraft()
    {
        /*
         * Save Draft разрешён:
         *
         * - при создании нового market;
         * - при редактировании market со статусом DRAFT;
         * - при редактировании REJECTED market.
         *
         * Нельзя через обычную форму откатывать:
         *
         * PENDING_REVIEW
         * APPROVED
         * PUBLISHED
         * RESOLVED
         */

        if ($this->betId) {
            $bet = Bet::query()->findOrFail($this->betId);

            $status = $bet->status instanceof \BackedEnum
                ? $bet->status
                : BetStatus::tryFrom((string) $bet->status);

            if (! in_array(
                $status,
                [
                    BetStatus::DRAFT,
                    BetStatus::REJECTED,
                ],
                true
            )) {
                $this->addError(
                    'workflow',
                    'This market can no longer be saved as a draft.'
                );

                return null;
            }
        }

        return $this->saveBet(BetStatus::DRAFT);
    }

    public function submitForReview()
    {
        /*
         * Submit for Review разрешён:
         *
         * - для нового market;
         * - DRAFT;
         * - REJECTED после исправлений.
         *
         * Нельзя таким способом откатывать
         * APPROVED / PUBLISHED / RESOLVED.
         */

        if ($this->betId) {
            $bet = Bet::query()->findOrFail($this->betId);

            $status = $bet->status instanceof \BackedEnum
                ? $bet->status
                : BetStatus::tryFrom((string) $bet->status);

            if (! in_array(
                $status,
                [
                    BetStatus::DRAFT,
                    BetStatus::REJECTED,
                ],
                true
            )) {
                $this->addError(
                    'workflow',
                    'This market cannot be submitted for review from its current status.'
                );

                return null;
            }
        }

        return $this->saveBet(BetStatus::PENDING_REVIEW);
    }

    public function saveChanges()
    {
        if (! $this->betId) {
            abort(404);
        }

        $bet = Bet::query()->findOrFail($this->betId);

        $this->authorizeEditableBet($bet);

        $status = $bet->status instanceof BetStatus
            ? $bet->status
            : BetStatus::tryFrom((string) $bet->status);

        /*
         * Обычное редактирование без изменения workflow
         * разрешаем только для APPROVED и PUBLISHED.
         */
        abort_unless(
            in_array(
                $status,
                [
                    BetStatus::APPROVED,
                    BetStatus::PUBLISHED,
                ],
                true
            ),
            422,
            'This market cannot be edited in its current status.'
        );

        return $this->saveBet(
            $status,
            preserveWorkflow: true
        );
    }

    protected function draftRules(): array
    {
        $rules = [
            'finishAt' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:5120'],
            'categoryIds' => ['array'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
            'translations' => ['array'],
            'answers' => ['array'],
            'sources' => ['array', 'max:3'],
            'countryIds' => ['array'],
            'countryIds.*' => ['integer', 'exists:countries,id'],
            'countryGroupIds' => ['array'],
            'countryGroupIds.*' => ['integer', 'exists:country_groups,id'],
        ];

        foreach ($this->translations as $locale => $translation) {
            $rules["translations.$locale.title"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$locale.description"] = ['nullable', 'string', 'max:20000'];
        }

        foreach ($this->answers as $answerIndex => $answer) {
            foreach (array_keys($this->translations) as $locale) {
                $rules["answers.$answerIndex.titles.$locale"] = ['nullable', 'string', 'max:500'];
            }
        }

        foreach ($this->sources as $sourceIndex => $source) {
            $rules["sources.$sourceIndex"] = ['nullable', 'url', 'max:2048'];
        }

        return $rules;
    }

    protected function submitRules(): array
    {
        $rules = [
            'finishAt' => ['required', 'date', 'after:now'],
            'image' => [
                $this->existingImagePath ? 'nullable' : 'required',
                'image',
                'max:5120',
            ],
            'categoryIds' => ['required', 'array', 'min:1'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
            'translations' => ['required', 'array', 'min:1'],
            'answers' => ['required', 'array', 'min:2'],
            'sources' => ['required', 'array', 'min:1', 'max:3'],
            'countryIds' => ['array'],
            'countryIds.*' => ['integer', 'exists:countries,id'],
            'countryGroupIds' => ['array'],
            'countryGroupIds.*' => ['integer', 'exists:country_groups,id'],
        ];

        foreach ($this->translations as $locale => $translation) {
            $rules["translations.$locale.title"] = ['required', 'string', 'max:500'];
            $rules["translations.$locale.description"] = ['required', 'string', 'max:20000'];
        }

        foreach ($this->answers as $answerIndex => $answer) {
            foreach (array_keys($this->translations) as $locale) {
                $rules["answers.$answerIndex.titles.$locale"] = ['required', 'string', 'max:500'];
            }
        }

        foreach ($this->sources as $sourceIndex => $source) {
            $rules["sources.$sourceIndex"] = $sourceIndex === 0
                ? ['required', 'url', 'max:2048']
                : ['nullable', 'url', 'max:2048'];
        }

        return $rules;
    }

    protected function saveBet(
        BetStatus $status,
        bool $preserveWorkflow = false
    )
    {
        $isDraft = $status === BetStatus::DRAFT;

        $this->validate($isDraft ? $this->draftRules() : $this->submitRules());

        if (
            ! $isDraft &&
            ! $this->isGlobal &&
            empty($this->countryIds) &&
            empty($this->countryGroupIds)
        ) {
            $this->addError('geography', 'Select at least one country or country group.');
            return null;
        }

        $this->categoryIds = array_values(array_unique(array_map('intval', $this->categoryIds)));
        $this->countryIds = array_values(array_unique(array_map('intval', $this->countryIds)));
        $this->countryGroupIds = array_values(array_unique(array_map('intval', $this->countryGroupIds)));

        $newImagePath = null;
        $oldImagePath = $this->existingImagePath;

        if ($this->image) {
            $newImagePath = $this->image->store(
                'bets/' . now()->format('Y/m'),
                'public'
            );
        }

        try {
            $bet = DB::transaction(function () use ($status, $newImagePath, $preserveWorkflow) {
                if ($this->betId) {
                    $bet = Bet::query()
                        ->lockForUpdate()
                        ->findOrFail($this->betId);

                    $this->authorizeEditableBet($bet);
                } else {
                    $bet = new Bet();
                    $bet->created_by_user_id = auth()->id();
                }

                $bet->status = $status;
                $bet->source_locale = $this->sourceLocale;
                $bet->finish_at = filled($this->finishAt)
                    ? $this->finishAt
                    : null;

                /*
                 * winning_answer_id очищаем только в обычном
                 * draft/review workflow.
                 *
                 * Save Changes не должен вмешиваться
                 * в settlement state.
                 */
                if (! $preserveWorkflow) {
                    $bet->winning_answer_id = null;
                }

                if ($newImagePath !== null) {
                    $bet->image_path = $newImagePath;
                }

                // Once a creator edits/resubmits a rejected market, the previous review state is cleared.
                $user = auth()->user();

                /*
                |--------------------------------------------------------------------------
                | REVIEW METADATA
                |--------------------------------------------------------------------------
                */

                if (
                    ! $preserveWorkflow
                    && $status === BetStatus::PENDING_REVIEW
                ) {

                    $bet->approved_at = null;
                    $bet->rejected_at = null;

                    /*
                     * Manager отправляет Bet на review.
                     * Supervisor ещё не назначен.
                     */
                    if ($user->isContentManager()) {
                        $bet->supervisor_user_id = null;
                    }
                }

                if (
                    ! $preserveWorkflow
                    && $status === BetStatus::DRAFT
                ) {

                    $bet->approved_at = null;
                    $bet->rejected_at = null;

                    if ($user->isContentManager()) {
                        $bet->supervisor_user_id = null;
                    }
                }

                $bet->save();

                $bet->translations()->delete();
                $bet->answers()->delete();
                $bet->sources()->delete();

                foreach ($this->translations as $locale => $translation) {
                    $title = trim((string) ($translation['title'] ?? ''));
                    $description = trim((string) ($translation['description'] ?? ''));

                    if ($title === '' && $description === '') {
                        continue;
                    }

                    $bet->translations()->create([
                        'locale' => $locale,
                        'title' => $title,
                        'description' => $description !== '' ? $description : null,
                    ]);
                }

                $answerSortOrder = 0;

                foreach ($this->answers as $answerData) {
                    $titles = $answerData['titles'] ?? [];
                    $hasContent = collect($titles)->contains(
                        fn ($title) => trim((string) $title) !== ''
                    );

                    if (! $hasContent) {
                        continue;
                    }

                    $answer = $bet->answers()->create([
                        'sort_order' => $answerSortOrder,
                    ]);

                    foreach ($titles as $locale => $title) {
                        $title = trim((string) $title);

                        if ($title === '') {
                            continue;
                        }

                        $answer->translations()->create([
                            'locale' => $locale,
                            'title' => $title,
                        ]);
                    }

                    $answerSortOrder++;
                }

                $sourceSortOrder = 1;

                foreach ($this->sources as $url) {
                    $url = trim((string) $url);

                    if ($url === '') {
                        continue;
                    }

                    $bet->sources()->create([
                        'url' => $url,
                        'sort_order' => $sourceSortOrder,
                    ]);

                    $sourceSortOrder++;
                }

                $bet->categories()->sync($this->categoryIds);

                if ($this->isGlobal) {
                    $bet->countries()->sync([]);
                    $bet->countryGroups()->sync([]);
                } else {
                    $bet->countries()->sync($this->countryIds);
                    $bet->countryGroups()->sync($this->countryGroupIds);
                }

                return $bet;
            });
        } catch (\Throwable $e) {
            if ($newImagePath && Storage::disk('public')->exists($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $e;
        }

        if (
            $newImagePath &&
            $oldImagePath &&
            $oldImagePath !== $newImagePath &&
            Storage::disk('public')->exists($oldImagePath)
        ) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $this->betId = $bet->id;
        $this->existingImagePath = $bet->image_path;
        $this->editMode = true;

        $message = match (true) {
            $preserveWorkflow => 'Market changes saved successfully.',
            $status === BetStatus::DRAFT => 'Bet saved as draft.',
            $status === BetStatus::PENDING_REVIEW => 'Bet submitted for review.',
            default => 'Market saved successfully.',
        };

        session()->flash(
            'status',
            $message
        );

        return redirect()->route('content.bets.index');
    }

    public function confirmResolution(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && (
                $user->isAdmin()
                || $user->isContentSupervisor()
            ),
            403
        );

        $this->validate([
            'finalAnswerId' => ['required', 'integer'],
        ]);

        $merchantBetIds = DB::transaction(function () use ($user) {
            $bet = Bet::query()
                ->with(['answers', 'aiResolution'])
                ->lockForUpdate()
                ->findOrFail($this->betId);

            $status = $bet->status instanceof BetStatus
                ? $bet->status
                : BetStatus::tryFrom((string) $bet->status);

            abort_unless(
                $status === BetStatus::RESOLVING,
                422,
                'This market is not awaiting resolution.'
            );

            $answer = $bet->answers
                ->firstWhere('id', (int) $this->finalAnswerId);

            abort_unless(
                $answer,
                422,
                'The selected answer does not belong to this market.'
            );

            $bet->update([
                'winning_answer_id' => $answer->id,
                'status' => BetStatus::RESOLVED,
                'resolved_at' => now(),
                'supervisor_user_id' => $user->id,
            ]);

            /*
             * Send the final settlement only to merchants
             * that successfully received this market.
             *
             * We only collect IDs inside the transaction.
             * HTTP/queue work happens after COMMIT.
             */
            return $bet->merchantBets()
                ->whereNotNull('delivered_at')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        });

        /*
         * Dispatch AFTER the resolution transaction has committed.
         *
         * SendMarketResolvedToMerchant itself is idempotent,
         * so an already delivered market.resolved callback
         * will never be sent twice.
         */
        foreach ($merchantBetIds as $merchantBetId) {
            SendMarketResolvedToMerchant::dispatch($merchantBetId);
        }

        session()->flash(
            'status',
            'Market resolved successfully. Merchant callbacks have been queued.'
        );

        $this->redirect(
            route('content.bets.index'),
            navigate: true
        );
    }

    public function with(): array
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->when(trim($this->categorySearch) !== '', function ($query) {
                $search = trim($this->categorySearch);

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(100)
            ->get();

        $selectedCategories = empty($this->categoryIds)
            ? collect()
            : Category::query()
                ->whereIn('id', $this->categoryIds)
                ->orderBy('name')
                ->get();

        $countries = Country::query()
            ->when(trim($this->countrySearch) !== '', function ($query) {
                $search = trim($this->countrySearch);

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', $search . '%');
                });
            })
            ->orderBy('name')
            ->get();

        $selectedCountries = empty($this->countryIds)
            ? collect()
            : Country::query()
                ->whereIn('id', $this->countryIds)
                ->orderBy('name')
                ->get();

        $countryGroups = CountryGroup::query()
            ->orderBy('name')
            ->get();

        $currentBet = $this->betId
            ? Bet::query()
                ->with([
                    'translations',
                    'answers.translations',
                    'aiResolution.suggestedAnswer.translations',
                    'aiResolution.sourceChecks.suggestedAnswer.translations',
                ])
                ->find($this->betId)
            : null;

        return compact(
            'categories',
            'selectedCategories',
            'countries',
            'selectedCountries',
            'countryGroups',
            'currentBet'
        );
    }


    public function changeStatus(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && (
                $user->isAdmin()
                || $user->isContentSupervisor()
            ),
            403
        );

        $this->validate([
            'supervisorStatus' => [
                'required',
                'string',
            ],
        ]);

        $status = BetStatus::tryFrom(
            $this->supervisorStatus
        );

        if (! $status) {
            $this->addError(
                'supervisorStatus',
                'Invalid status.'
            );

            return;
        }

        /*
         * A resolved market may only be created through
         * the Final settlement panel, where the winning
         * answer is explicitly selected.
         */
        if ($status === BetStatus::RESOLVED) {
            $this->addError(
                'supervisorStatus',
                'Use the Final settlement panel to select the winning answer and resolve the market.'
            );

            return;
        }

        $merchantBetIdsToCancel = [];
        $isPublishing = false;
        $statusChanged = false;

        $result = DB::transaction(function () use (
            $status,
            &$merchantBetIdsToCancel,
            &$isPublishing,
            &$statusChanged
        ) {
            /*
             * Lock the market while changing its workflow status.
             * This prevents a supervisor action from racing with
             * automatic PUBLISHED -> RESOLVING processing.
             */
            $bet = Bet::query()
                ->lockForUpdate()
                ->findOrFail($this->betId);

            $previousStatus = $bet->status instanceof BetStatus
                ? $bet->status
                : BetStatus::tryFrom((string) $bet->status);

            if (! $previousStatus) {
                return [
                    'ok' => false,
                    'message' => 'The market has an invalid current status.',
                ];
            }

            /*
             * Once resolution has started, cancellation is forbidden.
             */
            if (
                $status === BetStatus::CANCELLED
                && in_array(
                    $previousStatus,
                    [
                        BetStatus::RESOLVING,
                        BetStatus::RESOLVED,
                    ],
                    true
                )
            ) {
                return [
                    'ok' => false,
                    'message' =>
                        'A resolving or resolved market cannot be cancelled.',
                ];
            }

            /*
             * Cancellation is a controlled workflow transition.
             *
             * APPROVED:
             * the market may be cancelled before publication.
             *
             * PUBLISHED:
             * the market may be cancelled and every merchant that
             * already received it must receive market.cancelled.
             *
             * CANCELLED:
             * repeated update is a no-op and must not resend callbacks.
             */
            if (
                $status === BetStatus::CANCELLED
                && ! in_array(
                    $previousStatus,
                    [
                        BetStatus::APPROVED,
                        BetStatus::PUBLISHED,
                        BetStatus::CANCELLED,
                    ],
                    true
                )
            ) {
                return [
                    'ok' => false,
                    'message' =>
                        'Only an approved or published market can be cancelled.',
                ];
            }

            /*
             * Do not perform workflow side effects when the status
             * has not actually changed.
             */
            if ($previousStatus === $status) {
                return [
                    'ok' => true,
                    'message' => 'Bet status is already up to date.',
                ];
            }

            $statusChanged = true;

            $isPublishing =
                $status === BetStatus::PUBLISHED
                && $previousStatus !== BetStatus::PUBLISHED;

            /*
             * Collect recipients before changing the Bet status.
             * Only merchants that actually received the market
             * should receive market.cancelled.
             */
            if (
                $status === BetStatus::CANCELLED
                && $previousStatus === BetStatus::PUBLISHED
            ) {
                $merchantBetIdsToCancel = $bet->merchantBets()
                    ->whereNotNull('delivered_at')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            $bet->status = $status;
            $bet->supervisor_user_id = auth()->id();

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            if ($status === BetStatus::APPROVED) {
                $bet->approved_at = now();
                $bet->rejected_at = null;
            }

            if ($status === BetStatus::REJECTED) {
                $bet->rejected_at = now();
                $bet->approved_at = null;
            }

            if ($status === BetStatus::PUBLISHED) {
                $bet->published_at ??= now();
            }

            if (
                in_array(
                    $status,
                    [
                        BetStatus::DRAFT,
                        BetStatus::PENDING_REVIEW,
                    ],
                    true
                )
            ) {
                $bet->approved_at = null;
                $bet->rejected_at = null;
            }

            $bet->save();

            return [
                'ok' => true,
                'message' => $status === BetStatus::CANCELLED
                    ? 'Market cancelled successfully.'
                    : 'Bet status updated.',
            ];
        });

        if (! $result['ok']) {
            $this->addError(
                'supervisorStatus',
                $result['message']
            );

            return;
        }

        /*
         * Auto Delivery starts only after a real transition
         * into PUBLISHED.
         */
        if ($statusChanged && $isPublishing) {
            ProcessAutomaticMarketDelivery::dispatch(
                $this->betId
            )->afterCommit();
        }

        /*
         * Notify only merchants that had already received
         * the market before it was cancelled.
         *
         * The job itself is also idempotent.
         */
        if (
            $statusChanged
            && $status === BetStatus::CANCELLED
        ) {
            foreach ($merchantBetIdsToCancel as $merchantBetId) {
                SendMarketCancelledToMerchant::dispatch(
                    $merchantBetId
                )->afterCommit();
            }
        }

        session()->flash(
            'status',
            $result['message']
        );
    }


    public function approveBet(): void
    {
        $this->supervisorStatus =
            BetStatus::APPROVED->value;

        $this->changeStatus();
    }


    public function rejectBet(): void
    {
        $this->supervisorStatus =
            BetStatus::REJECTED->value;

        $this->changeStatus();
    }

    public function deleteBet()
    {
        $user = auth()->user();

        abort_unless(
            $user
            && (
                $user->isAdmin()
                || $user->isContentSupervisor()
            ),
            403
        );

        $bet = Bet::query()
            ->findOrFail($this->betId);

        $imagePath = $bet->image_path;

        DB::transaction(function () use ($bet) {

            $bet->winning_answer_id = null;
            $bet->save();

            $bet->delete();
        });

        if (
            $imagePath
            && Storage::disk('public')->exists($imagePath)
        ) {
            Storage::disk('public')->delete($imagePath);
        }

        session()->flash(
            'status',
            'Bet deleted permanently.'
        );

        return redirect()->route(
            'content.bets.index'
        );
    }
};
?>


<div class="mx-auto max-w-[1500px] pb-28">
    <style>
        .wr-form-card {
            background: var(--wr-panel);
            border-color: var(--wr-border);
            box-shadow: var(--wr-shadow);
        }

        .wr-form-input {
            background: var(--wr-input);
            color: var(--wr-text);
            border-color: var(--wr-border);
        }

        .wr-form-input::placeholder {
            color: var(--wr-muted-soft);
        }

        .wr-form-input:focus {
            outline: none;
            border-color: rgba(163, 230, 53, .6);
            box-shadow: 0 0 0 3px rgba(163, 230, 53, .08);
        }

        .wr-form-muted {
            color: var(--wr-muted);
        }

        .wr-form-soft {
            color: var(--wr-text-soft);
        }

        .wr-form-title {
            color: var(--wr-text);
        }
    </style>
    <div class="mb-8 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-400">Prediction Markets</div>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-[var(--wr-text)]">
                {{ $editMode ? 'Edit Bet' : 'Create Bet' }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--wr-muted)]">
                @if($editMode)
                    Continue editing this saved draft. You can save it again or submit it for supervisor review.
                @else
                    Create the market in your preferred language, add AI-assisted translations, define geography,
                    categories, outcomes and resolution sources.
                @endif
            </p>
        </div>

        <a href="{{ route('content.bets.index') }}"
           class="inline-flex items-center justify-center rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-5 py-3 text-sm font-bold text-[var(--wr-text-soft)] transition hover:border-lime-400/40 hover:text-lime-500">
            ← Back to Bets
        </a>
    </div>

    @if(
        $editMode
        && $currentBet
        && in_array(
            $currentBet->status,
            [
                \App\Enums\BetStatus::RESOLVING,
                \App\Enums\BetStatus::RESOLVED,
            ],
            true
        )
        && (
            auth()->user()->isContentSupervisor()
            || auth()->user()->isAdmin()
        )
    )
        @php
            $resolution = $currentBet->aiResolution;
            $isResolved = $currentBet->status === \App\Enums\BetStatus::RESOLVED;

            $answerTitle = function ($answer) use ($currentBet) {
                if (! $answer) {
                    return null;
                }

                $translation = $answer->translations
                    ->firstWhere('locale', $currentBet->source_locale)
                    ?? $answer->translations->firstWhere('locale', 'en')
                    ?? $answer->translations->first();

                return $translation?->title ?? ('Answer #' . $answer->id);
            };
        @endphp

        <section class="wr-form-card mb-7 overflow-hidden rounded-3xl border border-lime-400/25">
            <div class="border-b border-[var(--wr-border)] bg-gradient-to-r from-lime-400/[0.08] to-transparent px-6 py-6 lg:px-8">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-400">
                            {{ $isResolved ? 'Settlement completed' : 'AI Resolution' }}
                        </div>
                        <h2 class="mt-2 text-2xl font-black text-[var(--wr-text)]">
                            {{ $isResolved ? 'Final market result' : 'Review AI findings & settle market' }}
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--wr-muted)]">
                            {{ $isResolved
                                ? 'This market has been resolved. The winning answer below is now the official result.'
                                : 'Review the AI recommendation and every checked source. You can accept the suggested answer or select another answer before final settlement.' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div class="wr-form-input rounded-2xl border  px-4 py-3">
                            <div class="text-[10px] font-black uppercase tracking-wider text-[var(--wr-muted)]">Market status</div>
                            <div class="mt-1 text-sm font-black {{ $isResolved ? 'text-lime-400' : 'text-amber-300' }}">
                                {{ $currentBet->status->label() }}
                            </div>
                        </div>

                        @if($resolution)
                            <div class="wr-form-input rounded-2xl border  px-4 py-3">
                                <div class="text-[10px] font-black uppercase tracking-wider text-[var(--wr-muted)]">AI status</div>
                                <div class="mt-1 text-sm font-black {{ $resolution->status === 'completed' ? 'text-lime-400' : 'text-amber-300' }}">
                                    {{ strtoupper(str_replace('_', ' ', $resolution->status)) }}
                                </div>
                            </div>

                            <div class="wr-form-input rounded-2xl border  px-4 py-3">
                                <div class="text-[10px] font-black uppercase tracking-wider text-[var(--wr-muted)]">Confidence</div>
                                <div class="mt-1 text-xl font-black text-[var(--wr-text)]">
                                    {{ number_format((float) ($resolution->confidence ?? 0), 0) }}%
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-8 p-6 lg:p-8">
                @if(! $resolution)
                    <div class="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-5">
                        <div class="text-sm font-black text-amber-300">AI result is not available.</div>
                        <div class="mt-2 text-sm leading-6 text-[var(--wr-muted)]">
                            The resolution job may still be processing or no AI resolution record exists for this market.
                        </div>
                    </div>
                @elseif($resolution->suggested_answer_id)
                    <div class="grid gap-5 xl:grid-cols-[1fr_auto]">
                        <div class="rounded-2xl border border-lime-400/25 bg-lime-400/[0.06] p-5">
                            <div class="text-xs font-black uppercase tracking-[0.14em] text-lime-400">AI suggested winner</div>
                            <div class="mt-2 text-2xl font-black text-[var(--wr-text)]">
                                {{ $answerTitle($resolution->suggestedAnswer) }}
                            </div>
                            @if($resolution->summary)
                                <p class="mt-3 max-w-4xl text-sm leading-6 text-[var(--wr-text-soft)]">{{ $resolution->summary }}</p>
                            @endif
                        </div>

                        <div class="wr-form-input flex min-w-[180px] flex-col justify-center rounded-2xl border  p-5 text-center">
                            <div class="text-[10px] font-black uppercase tracking-wider text-[var(--wr-muted)]">Recommendation</div>
                            <div class="mt-2 text-lg font-black text-lime-400">Ready for review</div>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-5">
                        <div class="text-sm font-black text-amber-300">Automatic winner determination was unsuccessful.</div>
                        <div class="mt-2 text-sm leading-6 text-[var(--wr-text-soft)]">
                            {{ $resolution->summary ?: 'Manual review is required. Please inspect the evidence and select the winning answer manually.' }}
                        </div>
                    </div>
                @endif

                @if($resolution && $resolution->sourceChecks->isNotEmpty())
                    <div>
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-black uppercase tracking-[0.14em] text-[var(--wr-muted)]">Evidence</div>
                                <h3 class="mt-1 text-lg font-black text-[var(--wr-text)]">Sources checked</h3>
                            </div>
                            <div class="wr-form-input rounded-lg border  px-3 py-2 text-xs font-bold text-[var(--wr-muted)]">
                                {{ $resolution->sourceChecks->count() }} source check(s)
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach($resolution->sourceChecks as $check)
                                <article class="wr-form-input rounded-2xl border  p-5">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($check->is_success)
                                                    <span class="rounded-full bg-lime-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-lime-400">Result found</span>
                                                @else
                                                    <span class="rounded-full bg-amber-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-amber-300">No result</span>
                                                @endif
                                                <span class="rounded-full bg-[var(--wr-panel-secondary)] px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-[var(--wr-text-soft)]">
                                                    {{ $check->source_type === 'google' ? 'Google Search' : 'Provided source' }}
                                                </span>
                                            </div>

                                            @if($check->title)
                                                <div class="mt-3 text-sm font-black text-[var(--wr-text)]">{{ $check->title }}</div>
                                            @endif

                                            @if($check->url && $check->url !== 'google-search')
                                                <a href="{{ $check->url }}" target="_blank" rel="noopener noreferrer"
                                                   class="mt-2 block break-all text-sm font-semibold text-lime-400 hover:underline">
                                                    {{ $check->url }} ↗
                                                </a>
                                            @endif
                                        </div>

                                        <div class="flex shrink-0 gap-3">
                                            <div class="min-w-[115px] rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-3 py-2.5">
                                                <div class="text-[9px] font-black uppercase tracking-wider text-[var(--wr-muted)]">AI answer</div>
                                                <div class="mt-1 text-sm font-black {{ $check->suggestedAnswer ? 'text-[var(--wr-text)]' : 'text-[var(--wr-muted)]' }}">
                                                    {{ $check->suggestedAnswer ? $answerTitle($check->suggestedAnswer) : '—' }}
                                                </div>
                                            </div>
                                            <div class="min-w-[100px] rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-3 py-2.5">
                                                <div class="text-[9px] font-black uppercase tracking-wider text-[var(--wr-muted)]">Confidence</div>
                                                <div class="mt-1 text-sm font-black text-[var(--wr-text)]">{{ number_format((float) $check->confidence, 0) }}%</div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($check->interpretation)
                                        <div class="mt-4 border-t border-[var(--wr-border-soft)] pt-4">
                                            <div class="text-[10px] font-black uppercase tracking-wider text-[var(--wr-muted)]">Interpretation</div>
                                            <p class="mt-1 text-sm leading-6 text-[var(--wr-text-soft)]">{{ $check->interpretation }}</p>
                                        </div>
                                    @endif

                                    @if($check->evidence)
                                        <div class="mt-4 rounded-xl border border-sky-400/15 bg-sky-400/[0.05] p-4">
                                            <div class="text-[10px] font-black uppercase tracking-wider text-sky-300">Evidence</div>
                                            <p class="mt-1 text-sm leading-6 text-slate-200">{{ $check->evidence }}</p>
                                        </div>
                                    @endif

                                    @if($check->error)
                                        <div class="mt-4 rounded-xl border border-amber-400/15 bg-amber-400/[0.05] px-4 py-3 text-sm text-amber-300">
                                            {{ $check->error }}
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-t border-[var(--wr-border)] pt-7">
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">Final settlement</div>
                    <h3 class="mt-2 text-xl font-black text-[var(--wr-text)]">
                        {{ $isResolved ? 'Official winning answer' : 'Select winning answer' }}
                    </h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--wr-muted)]">
                        {{ $isResolved
                            ? 'The result is locked because the market has already been resolved.'
                            : 'AI is advisory only. The answer selected here becomes the official market result after confirmation.' }}
                    </p>

                    <div class="mt-5 grid gap-3 lg:grid-cols-2">
                        @foreach($currentBet->answers->sortBy('sort_order') as $answer)
                            @php
                                $isAiSuggestion = $resolution && $resolution->suggested_answer_id === $answer->id;
                                $isFinalWinner = $isResolved && $currentBet->winning_answer_id === $answer->id;
                                $isSelected = (int) $finalAnswerId === (int) $answer->id;
                            @endphp

                            <label class="rounded-2xl border p-4 transition
                                {{ $isSelected ? 'border-lime-400 bg-lime-400/10' : 'border-[var(--wr-border)] bg-[var(--wr-input)]' }}
                                {{ $isResolved ? 'cursor-default' : 'cursor-pointer hover:border-lime-400/30' }}">
                                <div class="flex items-center gap-3">
                                    <input type="radio"
                                           wire:model.live="finalAnswerId"
                                           value="{{ $answer->id }}"
                                           @disabled($isResolved)
                                           class="h-4 w-4 accent-lime-400">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-black text-[var(--wr-text)]">{{ $answerTitle($answer) }}</div>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            @if($isAiSuggestion)
                                                <span class="rounded-md bg-sky-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-sky-300">AI suggested</span>
                                            @endif
                                            @if($isFinalWinner)
                                                <span class="rounded-md bg-lime-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-lime-400">Official winner</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('finalAnswerId')
                    <div class="mt-3 text-sm font-bold text-red-400">{{ $message }}</div>
                    @enderror

                    @if(! $isResolved)
                        <div class="mt-7 flex flex-col gap-3 rounded-2xl border border-lime-400/20 bg-lime-400/[0.04] p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-black text-[var(--wr-text)]">Ready to settle?</div>
                                <div class="mt-1 text-xs leading-5 text-[var(--wr-muted)]">
                                    This action records the winning answer and changes the market status to Resolved.
                                </div>
                            </div>
                            <button type="button"
                                    wire:click="confirmResolution"
                                    wire:confirm="Resolve this market with the selected winning answer?"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmResolution"
                                    class="inline-flex min-w-[230px] items-center justify-center rounded-xl bg-lime-400 px-6 py-3 text-sm font-black text-[#06111f] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50">
                                <span wire:loading.remove wire:target="confirmResolution">✓ Confirm & Resolve Market</span>
                                <span wire:loading wire:target="confirmResolution">Resolving...</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if(
    $editMode
    && (
        auth()->user()->isContentSupervisor()
        || auth()->user()->isAdmin()
    )
)

        <section
            class="
            mb-7
            rounded-3xl
            border border-lime-400/20
            bg-[var(--wr-panel)]
            p-6
        "
        >

            <div
                class="
                flex
                flex-col
                gap-5
                xl:flex-row
                xl:items-end
                xl:justify-between
            "
            >

                <div>
                    <div
                        class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.16em]
                        text-lime-400
                    "
                    >
                        Supervisor
                    </div>

                    <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">
                        Review & status
                    </h2>

                    <p class="mt-1 text-sm text-[var(--wr-muted)]">
                        Review, edit, approve, reject or change the workflow status.
                    </p>
                </div>


                <div class="flex flex-wrap items-end gap-3">

                    <div>
                        <label
                            class="
                            mb-2
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wide
                            text-[var(--wr-muted)]
                        "
                        >
                            Status
                        </label>

                        <select
                            wire:model="supervisorStatus"
                            class="
                            rounded-xl
                            border border-[var(--wr-border)]
                            bg-[var(--wr-input)]
                            px-4
                            py-3
                            text-sm
                            font-bold
                            text-[var(--wr-text)]
                            outline-none
                            focus:border-lime-400/50
                        "
                        >
                            @foreach(\App\Enums\BetStatus::cases() as $status)
                                @if($status !== \App\Enums\BetStatus::RESOLVED)
                                    <option value="{{ $status->value }}">
                                        {{ $status->label() }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>


                    <button
                        type="button"
                        wire:click="changeStatus"
                        class="
                        rounded-xl
                        border border-[var(--wr-border)]
                        bg-[var(--wr-input)]
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-[var(--wr-text)]
                    "
                    >
                        Update status
                    </button>


                    <button
                        type="button"
                        wire:click="approveBet"
                        wire:confirm="Approve this bet?"
                        class="
                        rounded-xl
                        bg-lime-400
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-[#06111f]
                    "
                    >
                        ✓ Approve
                    </button>


                    <button
                        type="button"
                        wire:click="rejectBet"
                        wire:confirm="Reject this bet?"
                        class="
                        rounded-xl
                        border border-red-400/30
                        bg-red-400/10
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-red-300
                    "
                    >
                        Reject
                    </button>


                    <button
                        type="button"
                        wire:click="deleteBet"
                        wire:confirm="Permanently delete this bet and its image? This action cannot be undone."
                        class="
                        rounded-xl
                        border border-red-500/30
                        bg-red-500/10
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-red-400
                    "
                    >
                        Delete
                    </button>

                </div>

            </div>

        </section>

    @endif


    @if($errors->any())
        <div class="mb-7 rounded-2xl border border-red-400/20 bg-red-400/10 px-5 py-4">
            <div class="text-sm font-black text-red-300">Please check the form.</div>
            <div class="mt-1 text-xs text-red-300/70">Some required fields are missing or contain invalid information.</div>
        </div>
    @endif

    <div class="space-y-7">
        <section class="wr-form-card rounded-3xl border p-6 lg:p-8">
            <div class="mb-7">
                <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">General</div>
                <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">Market settings</h2>
                <p class="mt-1 text-sm text-[var(--wr-muted)]">Set the closing date and upload the main market image.</p>
            </div>

            <div class="grid gap-7 xl:grid-cols-2">
                <div>
                    <label for="finishAt" class="mb-2 block text-sm font-bold text-[var(--wr-text-soft)]">
                        Finish date & time <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[var(--wr-muted)]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v3M16 2v3M3.5 9h17M5.5 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                            </svg>
                        </div>
                        <input id="finishAt" type="datetime-local" wire:model="finishAt"
                               class="wr-form-input w-full cursor-pointer rounded-2xl border  py-4 pl-12 pr-4 text-sm font-semibold text-[var(--wr-text)] outline-none transition [color-scheme:light_dark] focus:border-lime-400/60 focus:ring-4 focus:ring-lime-400/5">
                    </div>
                    <p class="mt-2 text-xs text-[var(--wr-muted-soft)]">Drafts may be saved without a finish date.</p>
                    @error('finishAt') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-[var(--wr-text-soft)]">
                        Market image <span class="text-red-400">*</span>
                    </label>

                    <label for="bet-image"
                           class="group relative flex min-h-[150px] cursor-pointer items-center justify-center overflow-hidden rounded-2xl border border-dashed border-[#2c4965] bg-[var(--wr-input)] p-5 transition hover:border-lime-400/60 hover:bg-lime-400/[0.025]">
                        @if($image)
                            <div class="flex w-full items-center gap-5">
                                <div class="h-24 w-36 shrink-0 overflow-hidden rounded-xl border border-[var(--wr-border)] bg-[var(--wr-page)]">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Image preview" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-black text-[var(--wr-text)]">New image selected</div>
                                    <div class="mt-1 text-xs text-[var(--wr-muted)]">Click anywhere here to replace it.</div>
                                    <div class="mt-3 inline-flex rounded-lg bg-lime-400/10 px-3 py-1.5 text-xs font-black text-lime-400">Change image</div>
                                </div>
                            </div>
                        @elseif($existingImagePath)
                            <div class="flex w-full items-center gap-5">
                                <div class="h-24 w-36 shrink-0 overflow-hidden rounded-xl border border-[var(--wr-border)] bg-[var(--wr-page)]">
                                    <img src="{{ asset('storage/' . $existingImagePath) }}" alt="Current market image" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-black text-[var(--wr-text)]">Current image</div>
                                    <div class="mt-1 text-xs text-[var(--wr-muted)]">You do not need to upload it again.</div>
                                    <div class="mt-3 inline-flex rounded-lg bg-lime-400/10 px-3 py-1.5 text-xs font-black text-lime-400">Replace image</div>
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-lime-400/10 text-lime-400 transition group-hover:bg-lime-400 group-hover:text-[#06111f]">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0-4 4m4-4 4 4M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5" />
                                    </svg>
                                </div>
                                <div class="mt-4 text-sm font-black text-[var(--wr-text)]">Upload market image</div>
                                <div class="mt-1 text-xs text-[var(--wr-muted)]">PNG, JPG or WebP · maximum 5 MB</div>
                                <div class="mt-4 inline-flex rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-2 text-xs font-black text-lime-400">Choose file</div>
                            </div>
                        @endif

                        <input id="bet-image" type="file" wire:model="image" accept="image/jpeg,image/png,image/webp" class="hidden">
                    </label>

                    <div wire:loading wire:target="image" class="mt-3 text-xs font-bold text-lime-400">Uploading image...</div>
                    @error('image') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="wr-form-card rounded-3xl border p-6 lg:p-8">
            <div class="mb-7">
                <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">Content</div>
                <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">Market content & translations</h2>
                <p class="mt-1 max-w-3xl text-sm text-[var(--wr-muted)]">
                    Choose the language in which the market is originally written. Additional languages can be generated with AI and then edited manually.
                </p>
            </div>

            <div class="mb-7 rounded-2xl border border-lime-400/15 bg-[var(--wr-input)] p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-xl">
                        <label class="mb-2 block text-sm font-black text-[var(--wr-text)]">Original content language</label>
                        <p class="text-xs leading-5 text-[var(--wr-muted)]">Select this before entering content. AI translations are generated from this language.</p>
                    </div>
                    <div class="flex w-full gap-2 lg:w-auto">
                        <select wire:model="sourceLanguageChoice"
                                class="min-w-0 flex-1 rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3 text-sm font-semibold text-[var(--wr-text)] outline-none focus:border-lime-400/50 lg:w-56">
                            @foreach($availableLanguages as $locale => $language)
                                <option value="{{ $locale }}">{{ $language }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="applySourceLanguage"
                                class="rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-3 text-sm font-black text-lime-400 transition hover:bg-lime-400 hover:text-[#06111f]">
                            Apply
                        </button>
                    </div>
                </div>
                @error('sourceLanguageChoice') <div class="mt-3 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
            </div>

            <div class="wr-form-input mb-7 flex flex-col gap-4 rounded-2xl border  p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-sm font-black text-[var(--wr-text)]">Add translation</div>
                    <div class="mt-1 text-xs text-[var(--wr-muted)]">AI will translate title, description and all answer options.</div>
                </div>
                <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                    <select wire:model="newLocale"
                            class="min-w-0 flex-1 rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3 text-sm font-semibold text-[var(--wr-text)] outline-none focus:border-lime-400/50 lg:w-52">
                        <option value="">Select language...</option>
                        @foreach($availableLanguages as $locale => $language)
                            @if(! isset($translations[$locale]))
                                <option value="{{ $locale }}">{{ $language }}</option>
                            @endif
                        @endforeach
                    </select>
                    <button type="button" wire:click="addLanguage" wire:loading.attr="disabled" wire:target="addLanguage,translateLanguage"
                            class="inline-flex min-w-[180px] items-center justify-center rounded-xl bg-lime-400 px-4 py-3 text-sm font-black text-[#06111f] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="addLanguage">✦ Add & AI Translate</span>
                        <span wire:loading wire:target="addLanguage">Translating...</span>
                    </button>
                    <button type="button" wire:click="addLanguageWithoutTranslation"
                            class="rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3 text-sm font-bold text-[var(--wr-text-soft)] transition hover:border-[var(--wr-border-strong)] hover:text-[var(--wr-text)]">
                        Add manually
                    </button>
                </div>
            </div>

            @error('newLocale') <div class="-mt-4 mb-5 text-xs font-bold text-red-400">{{ $message }}</div> @enderror

            @if($translationNotice)
                <div class="mb-5 rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-3 text-sm font-semibold text-lime-300">✦ {{ $translationNotice }}</div>
            @endif

            @if($translationError)
                <div class="mb-5 rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm font-semibold text-amber-300">{{ $translationError }}</div>
            @endif

            <div class="space-y-5">
                @foreach($translations as $locale => $translation)
                    <div wire:key="translation-{{ $locale }}"
                         class="rounded-2xl border {{ $locale === $sourceLocale ? 'border-lime-400/25' : 'border-[var(--wr-border)]' }} bg-[var(--wr-input)] p-5">
                        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-lime-400/10 px-2 text-xs font-black uppercase text-lime-400">{{ $locale }}</div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-black text-[var(--wr-text)]">{{ $availableLanguages[$locale] ?? strtoupper($locale) }}</span>
                                        @if($locale === $sourceLocale)
                                            <span class="rounded-md bg-lime-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-lime-400">Original</span>
                                        @elseif(in_array($locale, $aiTranslatedLocales, true))
                                            <span class="rounded-md bg-sky-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-sky-300">AI suggestion</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($locale !== $sourceLocale)
                                <div class="flex gap-2">
                                    <button type="button" wire:click="translateLanguage('{{ $locale }}')" wire:loading.attr="disabled"
                                            class="rounded-lg border border-sky-400/20 bg-sky-400/10 px-3 py-2 text-xs font-black text-sky-300 transition hover:bg-sky-400/20 disabled:opacity-50">
                                        ✦ AI Translate
                                    </button>
                                    <button type="button" wire:click="removeLanguage('{{ $locale }}')"
                                            class="rounded-lg border border-red-400/15 bg-red-400/5 px-3 py-2 text-xs font-black text-red-300 transition hover:bg-red-400/10">
                                        Remove
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">Title</label>
                                <input type="text" wire:model="translations.{{ $locale }}.title"
                                       class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3.5 text-sm text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                                       placeholder="Market title">
                                @error("translations.$locale.title") <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">Description</label>
                                <textarea rows="5" wire:model="translations.{{ $locale }}.description"
                                          class="w-full resize-y rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3.5 text-sm leading-6 text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                                          placeholder="Explain the event and what this market asks."></textarea>
                                @error("translations.$locale.description") <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="wr-form-card rounded-3xl border p-6 lg:p-8">
            <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">Answers</div>
                    <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">Possible outcomes</h2>
                    <p class="mt-1 text-sm text-[var(--wr-muted)]">At least two answers are required before review.</p>
                </div>
                <button type="button" wire:click="addAnswer"
                        class="rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-2.5 text-sm font-black text-lime-400 transition hover:bg-lime-400 hover:text-[#06111f]">
                    + Add answer
                </button>
            </div>

            <div class="space-y-5">
                @foreach($answers as $answerIndex => $answer)
                    <div wire:key="answer-{{ $answerIndex }}" class="wr-form-input rounded-2xl border  p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="text-sm font-black text-[var(--wr-text)]">Answer {{ $answerIndex + 1 }}</div>
                            @if(count($answers) > 2)
                                <button type="button" wire:click="removeAnswer({{ $answerIndex }})" class="text-xs font-bold text-red-300 hover:text-red-200">Remove</button>
                            @endif
                        </div>

                        <div class="grid gap-4 xl:grid-cols-2">
                            @foreach($translations as $locale => $translation)
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">
                                        {{ $availableLanguages[$locale] ?? strtoupper($locale) }}
                                    </label>
                                    <input type="text" wire:model="answers.{{ $answerIndex }}.titles.{{ $locale }}"
                                           class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3 text-sm text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                                           placeholder="Answer text">
                                    @error("answers.$answerIndex.titles.$locale") <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="wr-form-card rounded-3xl border p-6 lg:p-8">
            <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">Categories</div>
                    <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">Content categories</h2>
                    <p class="mt-1 text-sm text-[var(--wr-muted)]">Choose one or more categories or create a new one.</p>
                </div>
                <button type="button" wire:click="openCreateCategory"
                        class="rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-2.5 text-sm font-black text-lime-400 transition hover:bg-lime-400 hover:text-[#06111f]">
                    + New Category
                </button>
            </div>

            @if($selectedCategories->isNotEmpty())
                <div class="mb-5 flex flex-wrap gap-2">
                    @foreach($selectedCategories as $selectedCategory)
                        <span class="rounded-lg border border-lime-400/15 bg-lime-400/10 px-3 py-1.5 text-xs font-bold text-lime-300">{{ $selectedCategory->name }}</span>
                    @endforeach
                </div>
            @endif

            @if($showCreateCategory)
                <div class="mb-5 rounded-2xl border border-lime-400/15 bg-[var(--wr-input)] p-5">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="text" wire:model="newCategoryName" wire:keydown.enter.prevent="createCategory"
                               class="min-w-0 flex-1 rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-4 py-3 text-sm text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                               placeholder="Category name">
                        <button type="button" wire:click="createCategory" class="rounded-xl bg-lime-400 px-4 py-3 text-sm font-black text-[#06111f] hover:bg-lime-300">Create & Select</button>
                        <button type="button" wire:click="cancelCreateCategory" class="rounded-xl border border-[var(--wr-border)] px-4 py-3 text-sm font-bold text-[var(--wr-text-soft)] hover:text-[var(--wr-text)]">Cancel</button>
                    </div>
                    @error('newCategoryName') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                </div>
            @endif

            <input type="search" wire:model.live.debounce.300ms="categorySearch"
                   class="wr-form-input mb-4 w-full rounded-xl border  px-4 py-3 text-sm text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                   placeholder="Search categories...">

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @forelse($categories as $category)
                    <label wire:key="category-{{ $category->id }}"
                           class="wr-form-input flex cursor-pointer items-center gap-3 rounded-xl border  px-4 py-3 text-sm font-bold text-[var(--wr-text-soft)] transition hover:border-lime-400/30">
                        <input type="checkbox" wire:model="categoryIds" value="{{ $category->id }}" class="rounded border-slate-600 bg-[var(--wr-input)] text-lime-400 focus:ring-lime-400/20">
                        <span>{{ $category->name }}</span>
                    </label>
                @empty
                    <div class="text-sm text-[var(--wr-muted)]">No categories found.</div>
                @endforelse
            </div>

            @error('categoryIds') <div class="mt-3 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
        </section>

        <section class="wr-form-card rounded-3xl border p-6 lg:p-8">
            <div class="mb-7">
                <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">Geography</div>
                <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">Market availability</h2>
                <p class="mt-1 text-sm text-[var(--wr-muted)]">Global means no country restrictions. Otherwise choose countries and/or country groups.</p>
            </div>

            <label class="wr-form-input mb-6 flex cursor-pointer items-center justify-between rounded-2xl border  p-5">
                <div>
                    <div class="text-sm font-black text-[var(--wr-text)]">Global market</div>
                    <div class="mt-1 text-xs text-[var(--wr-muted)]">Available to all countries.</div>
                </div>
                <input type="checkbox" wire:model.live="isGlobal" class="h-5 w-5 rounded border-slate-600 bg-[var(--wr-input)] text-lime-400 focus:ring-lime-400/20">
            </label>

            @if(!$isGlobal)
                <div class="space-y-7">
                    <div>
                        <div class="mb-3 text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">Quick country groups</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($countryGroups as $group)
                                <label wire:key="country-group-{{ $group->id }}"
                                       class="wr-form-input cursor-pointer rounded-xl border  px-4 py-2.5 text-sm font-bold text-[var(--wr-text-soft)] transition hover:border-lime-400/30 has-[:checked]:border-lime-400/30 has-[:checked]:bg-lime-400/10 has-[:checked]:text-lime-300">
                                    <input type="checkbox" wire:model="countryGroupIds" value="{{ $group->id }}" class="sr-only">
                                    {{ $group->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">Selected countries</div>
                        @if($selectedCountries->isNotEmpty())
                            <div class="mb-4 flex flex-wrap gap-2">
                                @foreach($selectedCountries as $country)
                                    <button type="button" wire:click="removeCountry({{ $country->id }})"
                                            class="inline-flex items-center gap-2 rounded-lg border border-lime-400/15 bg-lime-400/10 px-3 py-2 text-xs font-bold text-lime-300">
                                        {{ $country->code }} · {{ $country->name }} <span class="text-lime-200/60">×</span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="mb-4 text-sm text-[var(--wr-muted)]">No individual countries selected.</div>
                        @endif

                        <input type="search" wire:model.live.debounce.300ms="countrySearch"
                               class="wr-form-input mb-4 w-full rounded-xl border  px-4 py-3 text-sm text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                               placeholder="Search by country name or ISO code...">

                        <div class="grid max-h-80 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($countries as $country)
                                <label wire:key="country-{{ $country->id }}"
                                       class="wr-form-input flex cursor-pointer items-center gap-3 rounded-xl border  px-4 py-3 text-sm text-[var(--wr-text-soft)] transition hover:border-lime-400/30">
                                    <input type="checkbox" wire:model="countryIds" value="{{ $country->id }}" class="rounded border-slate-600 bg-[var(--wr-input)] text-lime-400 focus:ring-lime-400/20">
                                    <span class="w-8 text-xs font-black uppercase text-lime-400">{{ $country->code }}</span>
                                    <span class="font-semibold">{{ $country->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @error('geography') <div class="mt-4 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
        </section>

        <section class="wr-form-card rounded-3xl border p-6 lg:p-8">
            <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">Resolution</div>
                    <h2 class="mt-2 text-xl font-black text-[var(--wr-text)]">Sources</h2>
                    <p class="mt-1 text-sm text-[var(--wr-muted)]">Add up to three authoritative links. Source #1 is required for review.</p>
                </div>
                @if(count($sources) < 3)
                    <button type="button" wire:click="addSource"
                            class="rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-2.5 text-sm font-black text-lime-400 transition hover:bg-lime-400 hover:text-[#06111f]">
                        + Add source
                    </button>
                @endif
            </div>

            <div class="space-y-3">
                @foreach($sources as $sourceIndex => $source)
                    <div wire:key="source-{{ $sourceIndex }}" class="flex gap-2">
                        <div class="wr-form-input flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border  text-xs font-black text-lime-400">{{ $sourceIndex + 1 }}</div>
                        <div class="min-w-0 flex-1">
                            <input type="url" wire:model="sources.{{ $sourceIndex }}"
                                   class="wr-form-input w-full rounded-xl border  px-4 py-3.5 text-sm text-[var(--wr-text)] outline-none focus:border-lime-400/50"
                                   placeholder="https://...">
                            @error("sources.$sourceIndex") <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                        </div>
                        @if(count($sources) > 1)
                            <button type="button" wire:click="removeSource({{ $sourceIndex }})"
                                    class="h-12 rounded-xl border border-red-400/15 bg-red-400/5 px-4 text-xs font-black text-red-300 hover:bg-red-400/10">Remove</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    @if(
        ! $editMode
        || ! $currentBet
        || ! in_array(
            $currentBet->status,
            [\App\Enums\BetStatus::RESOLVING, \App\Enums\BetStatus::RESOLVED],
            true
        )
    )
        <div class="fixed bottom-4 left-4 right-4 z-40 lg:left-[calc(278px+2rem)]">
            <div class="mx-auto flex max-w-[1500px] flex-col gap-4 rounded-2xl border border-[#2c4965] bg-[#081726]/95 p-4 shadow-2xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-[0.16em] text-[var(--wr-muted)]">Created by</div>
                    <div class="mt-1 text-sm font-bold text-[var(--wr-text-soft)]">{{ auth()->user()->email }}</div>
                </div>

                <div class="flex gap-3">
                    @php
                        $currentStatus = $currentBet?->status;

                        if (is_string($currentStatus)) {
                            $currentStatus = \App\Enums\BetStatus::tryFrom($currentStatus);
                        }

                        $showDraftActions =
                            ! $currentBet
                            || in_array(
                                $currentStatus,
                                [
                                    \App\Enums\BetStatus::DRAFT,
                                    \App\Enums\BetStatus::REJECTED,
                                ],
                                true
                            );

                        $showSaveChanges =
                            $currentBet
                            && in_array(
                                $currentStatus,
                                [
                                    \App\Enums\BetStatus::APPROVED,
                                    \App\Enums\BetStatus::PUBLISHED,
                                ],
                                true
                            );
                    @endphp

                    @if($showDraftActions)
                        <button
                            type="button"
                            wire:click="saveDraft"
                            wire:loading.attr="disabled"
                            wire:target="saveDraft,submitForReview"
                            class="inline-flex items-center justify-center rounded-xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-6 py-3 text-sm font-black text-[var(--wr-text)] transition hover:border-[var(--wr-border-strong)] disabled:opacity-50"
                        >
            <span wire:loading.remove wire:target="saveDraft">
                Save Draft
            </span>

                            <span wire:loading wire:target="saveDraft">
                Saving...
            </span>
                        </button>

                        <button
                            type="button"
                            wire:click="submitForReview"
                            wire:loading.attr="disabled"
                            wire:target="saveDraft,submitForReview"
                            class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-6 py-3 text-sm font-black text-[#06111f] transition hover:bg-lime-300 disabled:opacity-50"
                        >
            <span wire:loading.remove wire:target="submitForReview">
                Submit for Review →
            </span>

                            <span wire:loading wire:target="submitForReview">
                Submitting...
            </span>
                        </button>
                    @endif

                    @if($showSaveChanges)
                        <button
                            type="button"
                            wire:click="saveChanges"
                            wire:loading.attr="disabled"
                            wire:target="saveChanges"
                            class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-6 py-3 text-sm font-black text-[#06111f] transition hover:bg-lime-300 disabled:opacity-50"
                        >
            <span wire:loading.remove wire:target="saveChanges">
                Save Changes
            </span>

                            <span wire:loading wire:target="saveChanges">
                Saving...
            </span>
                        </button>
                    @endif
                </div>


            </div>
        </div>
    @endif
</div>

<?php

namespace Database\Seeders;

use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\Category;
use App\Models\Country;
use App\Models\CountryGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoBetSeeder extends Seeder
{
    private array $languages = [
        'en',
        'it',
        'es',
        'de',
        'fr',
        'ro',
        'ru',
        'ar',
    ];

    private array $content = [

        'en' => [
            [
                'title' => 'Will Bitcoin trade above $150,000 before the end of 2026?',
                'description' => 'This market resolves Yes if Bitcoin trades above $150,000 before the specified market closing date.',
            ],
            [
                'title' => 'Will Real Madrid win their next league match?',
                'description' => 'This market resolves based on the official result of Real Madrid\'s next scheduled league match.',
            ],
            [
                'title' => 'Will the ECB cut interest rates at its next meeting?',
                'description' => 'This market resolves based on the official European Central Bank monetary policy decision.',
            ],
            [
                'title' => 'Will Ethereum trade above $8,000 this year?',
                'description' => 'This market resolves Yes if Ethereum reaches a market price above $8,000 before the end of the year.',
            ],
            [
                'title' => 'Will Apple announce a new iPhone model this month?',
                'description' => 'Resolution will be based on an official product announcement from Apple.',
            ],
        ],

        'it' => [
            [
                'title' => 'Bitcoin supererà i 150.000 dollari entro la fine del 2026?',
                'description' => 'Il mercato si risolve con Sì se Bitcoin supera i 150.000 dollari prima della data di chiusura.',
            ],
            [
                'title' => 'Il Real Madrid vincerà la prossima partita di campionato?',
                'description' => 'Il mercato sarà risolto in base al risultato ufficiale della prossima partita di campionato del Real Madrid.',
            ],
        ],

        'es' => [
            [
                'title' => '¿Bitcoin cotizará por encima de 150.000 dólares antes de finales de 2026?',
                'description' => 'El mercado se resolverá como Sí si Bitcoin supera los 150.000 dólares antes de la fecha de cierre.',
            ],
            [
                'title' => '¿Ganará el Real Madrid su próximo partido de liga?',
                'description' => 'El mercado se resolverá según el resultado oficial del próximo partido de liga del Real Madrid.',
            ],
        ],

        'de' => [
            [
                'title' => 'Wird Bitcoin vor Ende 2026 über 150.000 US-Dollar gehandelt?',
                'description' => 'Der Markt wird mit Ja aufgelöst, wenn Bitcoin vor Marktschluss über 150.000 US-Dollar gehandelt wird.',
            ],
            [
                'title' => 'Wird Real Madrid sein nächstes Ligaspiel gewinnen?',
                'description' => 'Die Auflösung erfolgt anhand des offiziellen Ergebnisses des nächsten Ligaspiels von Real Madrid.',
            ],
        ],

        'fr' => [
            [
                'title' => 'Le Bitcoin dépassera-t-il 150 000 dollars avant la fin de 2026 ?',
                'description' => 'Le marché sera résolu Oui si le Bitcoin dépasse 150 000 dollars avant la date de clôture.',
            ],
            [
                'title' => 'Le Real Madrid remportera-t-il son prochain match de championnat ?',
                'description' => 'La résolution sera basée sur le résultat officiel du prochain match de championnat du Real Madrid.',
            ],
        ],

        'ro' => [
            [
                'title' => 'Va depăși Bitcoin pragul de 150.000 de dolari până la sfârșitul anului 2026?',
                'description' => 'Piața se va încheia cu Da dacă Bitcoin depășește 150.000 de dolari înainte de data de închidere.',
            ],
            [
                'title' => 'Va câștiga Real Madrid următorul meci de campionat?',
                'description' => 'Piața va fi soluționată pe baza rezultatului oficial al următorului meci al lui Real Madrid.',
            ],
        ],

        'ru' => [
            [
                'title' => 'Будет ли Bitcoin стоить выше $150 000 до конца 2026 года?',
                'description' => 'Рынок будет закрыт с результатом «Да», если Bitcoin превысит $150 000 до даты завершения рынка.',
            ],
            [
                'title' => 'Выиграет ли Real Madrid следующий матч чемпионата?',
                'description' => 'Результат рынка определяется официальным результатом следующего матча Real Madrid в чемпионате.',
            ],
        ],

        'ar' => [
            [
                'title' => 'هل سيتجاوز سعر بيتكوين 150,000 دولار قبل نهاية عام 2026؟',
                'description' => 'يتم حسم السوق بنعم إذا تجاوز سعر بيتكوين 150,000 دولار قبل تاريخ إغلاق السوق.',
            ],
            [
                'title' => 'هل سيفوز ريال مدريد في مباراته القادمة في الدوري؟',
                'description' => 'يتم حسم السوق بناءً على النتيجة الرسمية لمباراة ريال مدريد القادمة في الدوري.',
            ],
        ],
    ];

    private array $answers = [
        'en' => ['Yes', 'No'],
        'it' => ['Sì', 'No'],
        'es' => ['Sí', 'No'],
        'de' => ['Ja', 'Nein'],
        'fr' => ['Oui', 'Non'],
        'ro' => ['Da', 'Nu'],
        'ru' => ['Да', 'Нет'],
        'ar' => ['نعم', 'لا'],
    ];


    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        $contentManagers = User::factory()
            ->count(100)
            ->contentManager()
            ->create();

        $supervisors = User::factory()
            ->count(5)
            ->contentSupervisor()
            ->create();


        /*
        |--------------------------------------------------------------------------
        | CATEGORIES
        |--------------------------------------------------------------------------
        */

        $categories = collect([
            ['name' => 'Sports', 'slug' => 'sports'],
            ['name' => 'Crypto', 'slug' => 'crypto'],
            ['name' => 'Politics', 'slug' => 'politics'],
            ['name' => 'Business', 'slug' => 'business'],
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => 'Entertainment', 'slug' => 'entertainment'],
            ['name' => 'Economy', 'slug' => 'economy'],
            ['name' => 'Esports', 'slug' => 'esports'],
        ])->map(function ($category) {
            return Category::firstOrCreate(
                [
                    'slug' => $category['slug'],
                ],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );
        });


        /*
        |--------------------------------------------------------------------------
        | GEO
        |--------------------------------------------------------------------------
        */

        $countries = Country::query()
            ->get();

        $countryGroups = CountryGroup::query()
            ->get();

        if ($countries->isEmpty()) {
            $this->command?->warn(
                'Countries table is empty. Run CountriesSeeder first.'
            );
        }

        if ($countryGroups->isEmpty()) {
            $this->command?->warn(
                'Country groups table is empty. Run CountryGroupsSeeder first.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | BETS
        |--------------------------------------------------------------------------
        */

        $statuses = [
            BetStatus::DRAFT,
            BetStatus::DRAFT,

            BetStatus::PENDING_REVIEW,
            BetStatus::PENDING_REVIEW,

            BetStatus::APPROVED,

            BetStatus::REJECTED,

            BetStatus::PUBLISHED,
            BetStatus::PUBLISHED,

            BetStatus::RESOLVING,

            BetStatus::RESOLVED,
            BetStatus::RESOLVED,

            BetStatus::CANCELLED,
        ];


        for ($i = 1; $i <= 300; $i++) {

            DB::transaction(function () use (
                $contentManagers,
                $supervisors,
                $categories,
                $countries,
                $countryGroups,
                $statuses
            ) {

                /*
                |--------------------------------------------------------------------------
                | BASIC BET
                |--------------------------------------------------------------------------
                */

                $creator = $contentManagers->random();

                $status = fake()->randomElement(
                    $statuses
                );

                $needsSupervisor = in_array(
                    $status,
                    [
                        BetStatus::APPROVED,
                        BetStatus::REJECTED,
                        BetStatus::PUBLISHED,
                        BetStatus::RESOLVING,
                        BetStatus::RESOLVED,
                    ],
                    true
                );

                $supervisor = $needsSupervisor
                    ? $supervisors->random()
                    : null;


                $sourceLocale = fake()->randomElement(
                    $this->languages
                );


                $finishAt = fake()->dateTimeBetween(
                    '-3 months',
                    '+6 months'
                );


                $bet = Bet::factory()->create([
                    'created_by_user_id' =>
                        $creator->id,

                    'supervisor_user_id' =>
                        $supervisor?->id,

                    'status' =>
                        $status,

                    'source_locale' =>
                        $sourceLocale,

                    'finish_at' =>
                        $finishAt,

                    'approved_at' =>
                        in_array(
                            $status,
                            [
                                BetStatus::APPROVED,
                                BetStatus::PUBLISHED,
                                BetStatus::RESOLVING,
                                BetStatus::RESOLVED,
                            ],
                            true
                        )
                            ? fake()->dateTimeBetween(
                            '-4 months',
                            'now'
                        )
                            : null,

                    'rejected_at' =>
                        $status === BetStatus::REJECTED
                            ? fake()->dateTimeBetween(
                            '-4 months',
                            'now'
                        )
                            : null,

                    'published_at' =>
                        in_array(
                            $status,
                            [
                                BetStatus::PUBLISHED,
                                BetStatus::RESOLVING,
                                BetStatus::RESOLVED,
                            ],
                            true
                        )
                            ? fake()->dateTimeBetween(
                            '-3 months',
                            'now'
                        )
                            : null,

                    'resolved_at' =>
                        $status === BetStatus::RESOLVED
                            ? fake()->dateTimeBetween(
                            '-2 months',
                            'now'
                        )
                            : null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | LANGUAGES
                |--------------------------------------------------------------------------
                |
                | Every Bet receives between 1 and 4 translations.
                | source_locale is always included.
                |
                */

                $translationLocales = collect(
                    $this->languages
                )
                    ->reject(
                        fn ($locale) =>
                            $locale === $sourceLocale
                    )
                    ->shuffle()
                    ->take(
                        fake()->numberBetween(
                            0,
                            3
                        )
                    )
                    ->prepend(
                        $sourceLocale
                    )
                    ->unique()
                    ->values();


                foreach (
                    $translationLocales
                    as $locale
                ) {

                    $content =
                        $this->randomContent(
                            $locale
                        );

                    $bet->translations()->create([
                        'locale' => $locale,

                        'title' =>
                            $content['title'],

                        'description' =>
                            $content['description'],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | ANSWERS
                |--------------------------------------------------------------------------
                */

                $yesAnswer =
                    $bet->answers()->create([
                        'sort_order' => 1,
                    ]);

                $noAnswer =
                    $bet->answers()->create([
                        'sort_order' => 2,
                    ]);


                foreach (
                    $translationLocales
                    as $locale
                ) {

                    $localizedAnswers =
                        $this->answers[$locale]
                        ?? $this->answers['en'];

                    $yesAnswer
                        ->translations()
                        ->create([
                            'locale' => $locale,
                            'title' =>
                                $localizedAnswers[0],
                        ]);

                    $noAnswer
                        ->translations()
                        ->create([
                            'locale' => $locale,
                            'title' =>
                                $localizedAnswers[1],
                        ]);
                }


                /*
                |--------------------------------------------------------------------------
                | WINNING ANSWER
                |--------------------------------------------------------------------------
                */

                if (
                    $status ===
                    BetStatus::RESOLVED
                ) {

                    $bet->winning_answer_id =
                        fake()->boolean()
                            ? $yesAnswer->id
                            : $noAnswer->id;

                    $bet->save();
                }


                /*
                |--------------------------------------------------------------------------
                | CATEGORIES
                |--------------------------------------------------------------------------
                */

                $categoryIds =
                    $categories
                        ->shuffle()
                        ->take(
                            fake()->numberBetween(
                                1,
                                min(
                                    3,
                                    $categories->count()
                                )
                            )
                        )
                        ->pluck('id');

                $bet->categories()
                    ->sync(
                        $categoryIds
                    );


                /*
                |--------------------------------------------------------------------------
                | GEO
                |--------------------------------------------------------------------------
                |
                | 25% Global
                | 35% country groups
                | 40% individual countries
                |
                */

                $geoType =
                    fake()->randomElement([
                        'global',
                        'global',
                        'country',
                        'country',
                        'country',
                        'group',
                        'group',
                    ]);


                if (
                    $geoType === 'country'
                    && $countries->isNotEmpty()
                ) {

                    $countryIds =
                        $countries
                            ->random(
                                min(
                                    fake()->numberBetween(
                                        1,
                                        5
                                    ),
                                    $countries->count()
                                )
                            );

                    /*
                     * Collection::random(1) can return
                     * an object rather than a collection.
                     */
                    $countryIds =
                        collect($countryIds)
                            ->pluck('id');

                    $bet->countries()
                        ->sync(
                            $countryIds
                        );
                }


                if (
                    $geoType === 'group'
                    && $countryGroups->isNotEmpty()
                ) {

                    $groups =
                        $countryGroups
                            ->shuffle()
                            ->take(
                                fake()->numberBetween(
                                    1,
                                    min(
                                        2,
                                        $countryGroups->count()
                                    )
                                )
                            )
                            ->pluck('id');

                    $bet->countryGroups()
                        ->sync(
                            $groups
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | SOURCES
                |--------------------------------------------------------------------------
                */

                $sourceCount =
                    fake()->numberBetween(
                        1,
                        3
                    );

                for (
                    $sourceIndex = 1;
                    $sourceIndex <= $sourceCount;
                    $sourceIndex++
                ) {

                    $bet->sources()->create([
                        'url' =>
                            fake()->url(),

                        'sort_order' =>
                            $sourceIndex,
                    ]);
                }
            });
        }


        $this->command?->info(
            'Demo content successfully created.'
        );

        $this->command?->info(
            'Content Managers: 100'
        );

        $this->command?->info(
            'Content Supervisors: 5'
        );

        $this->command?->info(
            'Bets: 300'
        );

        $this->command?->info(
            'Password for generated users: password'
        );
    }


    private function randomContent(
        string $locale
    ): array {

        $contents =
            $this->content[$locale]
            ?? $this->content['en'];

        return fake()->randomElement(
            $contents
        );
    }
}

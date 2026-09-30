<?php

use App\Enums\UserRole;
use App\Http\Controllers\Front\PageController;
use App\Models\Bet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
|
| Public website.
|
*/

Route::get('/', [PageController::class, 'index'])
    ->name('home');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED USERS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD / ROLE REDIRECT
    |--------------------------------------------------------------------------
    |
    | Common entry point after authentication.
    |
    | Each role is redirected to its own working area.
    |
    */

    Route::get('/dashboard', function () {

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string)$user->role);


        return match ($role) {

            /*
             * Administrator.
             */
            UserRole::ADMIN =>
            view('dashboards.dashboard'),

            /*
             * Ordinary registered user.
             */
            UserRole::USER =>
            view('user.dashboard'),

            /*
             * Content team.
             */
            UserRole::CONTENT_MANAGER,
            UserRole::CONTENT_SUPERVISOR =>
            redirect()->route('content.markets.index'),

            /*
             * Financial Manager.
             */
            UserRole::FINANCIAL_MANAGER =>
            redirect()->route('finance.dashboard'),

            /*
             * Merchant.
             */
            UserRole::MERCHANT =>
            redirect()->route('merchant.dashboard'),

            /*
             * Unknown / invalid role.
             */
            default =>
            abort(403),
        };
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    |
    | Access:
    | - Admin
    |
    */

    Route::middleware([
        'role:' . UserRole::ADMIN->value,
    ])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            /*
             * CMS Pages
             */
            Route::view('/pages', 'admin.pages')
                ->name('pages.index');

            /*
             * Users
             */
            Route::view('/users', 'admin.users')
                ->name('users.index');

            /*
             * User Merchants
             */
            Route::get(
                '/users/{user}/merchants',
                function (\App\Models\User $user) {

                    return view('admin.merchants.user', [
                        'userId' => $user->id,
                    ]);
                }
            )->name('merchants.user');

            Route::view(
                '/account-applications',
                'admin.account-applications'
            )->name('account-applications');
        });


    /*
    |--------------------------------------------------------------------------
    | CONTENT
    |--------------------------------------------------------------------------
    |
    | Access:
    | - Admin
    | - Content Manager
    | - Content Supervisor
    |
    | Admin is allowed automatically by RoleMiddleware.
    |
    */

    Route::middleware([
        'role:'
        . UserRole::CONTENT_MANAGER->value
        . ','
        . UserRole::CONTENT_SUPERVISOR->value,
    ])
        ->prefix('content')
        ->name('content.')
        ->group(function () {

            /*
             * Prediction Markets
             */
            Route::view('/markets', 'content.markets')
                ->name('markets.index');

            /*
             * Bets
             */
            Route::view('/bets', 'content.bets.index')
                ->name('bets.index');

            Route::view('/bets/create', 'content.bets.create')
                ->name('bets.create');

            Route::get('/bets/{bet}/edit', function (Bet $bet) {

                return view('content.bets.edit', [
                    'betId' => $bet->id,
                ]);

            })->name('bets.edit');
        });


    /*
    |--------------------------------------------------------------------------
    | FINANCE
    |--------------------------------------------------------------------------
    |
    | Access:
    | - Admin
    | - Financial Manager
    |
    */

    Route::middleware([
        'role:' . UserRole::FINANCIAL_MANAGER->value,
    ])
        ->prefix('finance')
        ->name('finance.')
        ->group(function () {

            /*
             * Finance Dashboard
             */
            Route::view('/dashboard', 'financial.dashboard')
                ->name('dashboard');

            /*
             * Merchants
             */
            Route::view('/merchants', 'financial.merchants')
                ->name('merchants');

            /*
             * Transactions
             */
            Route::view('/transactions', 'financial.transactions')
                ->name('transactions');

            /*
             * Billing Plans
             */
            Route::view('/billing-plans', 'financial.billing-plans')
                ->name('billing-plans');
        });


    /*
    |--------------------------------------------------------------------------
    | MERCHANT
    |--------------------------------------------------------------------------
    |
    | Access:
    | - Merchant
    | - Admin
    |
    | Admin is allowed automatically by RoleMiddleware.
    |
    */

    Route::middleware([
        'role:' . UserRole::MERCHANT->value,
        'current.merchant',
    ])
        ->prefix('merchant')
        ->name('merchant.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Merchant Dashboard
            |--------------------------------------------------------------------------
            |
            | Dashboard is the account-level overview.
            |
            | It may show aggregated statistics across ALL merchants
            | belonging to the authenticated user.
            |
            */

            Route::view('/dashboard', 'merchant.dashboard')
                ->name('dashboard');


            /*
            |--------------------------------------------------------------------------
            | Switch Current Merchant
            |--------------------------------------------------------------------------
            */

            Route::post('/switch/{merchant}', function (
                \App\Models\Merchant          $merchant,
                \App\Services\CurrentMerchant $currentMerchant
            ) {
                $currentMerchant->set(
                    $merchant,
                    auth()->user()
                );

                /*
                 * Return to the page from which the merchant
                 * was switched.
                 */
                return back();

            })->name('switch');


            /*
|--------------------------------------------------------------------------
| Switch To All Merchants
|--------------------------------------------------------------------------
|
| Clears the current merchant selection.
|
| The Merchant Dashboard will then display aggregated data
| across all merchants belonging to the authenticated user.
|
*/

            Route::post('/switch-all', function (
                \App\Services\CurrentMerchant $currentMerchant
            ) {
                $currentMerchant->all();

                return redirect()->route('merchant.dashboard');

            })->name('switch-all');


            /*
            |--------------------------------------------------------------------------
            | Markets
            |--------------------------------------------------------------------------
            |
            | Must use CurrentMerchant rather than the first merchant
            | belonging to the user.
            |
            */

            Route::view('/markets', 'merchant.markets')
                ->name('markets');


            Route::get('/markets/{bet}', function (\App\Models\Bet $bet) {
                $status = $bet->status instanceof \BackedEnum
                    ? $bet->status->value
                    : $bet->status;

                abort_unless(
                    in_array($status, [
                        \App\Enums\BetStatus::PUBLISHED->value,
                        \App\Enums\BetStatus::RESOLVING->value,
                        \App\Enums\BetStatus::RESOLVED->value,
                    ], true),
                    404
                );

                return view('merchant.market-show', [
                    'bet' => $bet,
                ]);
            })->name('markets.show');


            Route::view('/auto-delivery', 'merchant.auto-delivery')
                ->name('auto-delivery');
            /*
            |--------------------------------------------------------------------------
            | API Tokens
            |--------------------------------------------------------------------------
            */

            Route::view('/api-tokens', 'merchant.api-tokens')
                ->name('api-tokens');


            /*
            |--------------------------------------------------------------------------
            | Endpoints
            |--------------------------------------------------------------------------
            */

            Route::view('/webhooks', 'merchant.webhooks')
                ->name('webhooks');

            // Route::view('/endpoints', 'merchant.endpoints')
            //     ->name('endpoints');


            /*
            |--------------------------------------------------------------------------
            | Usage
            |--------------------------------------------------------------------------
            */

            // Route::view('/usage', 'merchant.usage')
            //     ->name('usage');


            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            */

            Route::view('/billing', 'merchant.billing')
                ->name('billing');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    |
    | Profile / password / account settings.
    |
    */

    require __DIR__ . '/settings.php';


    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION
    |--------------------------------------------------------------------------
    |
    | Login / Register / Password Reset etc.
    |
    */

    require __DIR__ . '/auth.php';


    /*
    |--------------------------------------------------------------------------
    | TEST MERCHANT WEBHOOK
    |--------------------------------------------------------------------------
    |
    | Local endpoint used for market-delivery testing.
    |
    */

    Route::post('/test/merchant-webhook', function (Request $request) {

        Log::info('TEST MERCHANT WEBHOOK RECEIVED', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook received successfully.',
        ]);

    })->name('test.merchant-webhook');


    /*
    |--------------------------------------------------------------------------
    | TEST RESOLUTION CALLBACK
    |--------------------------------------------------------------------------
    |
    | Local endpoint used for market-resolution callback testing.
    |
    */

    Route::post('/test/resolution-callback', function (Request $request) {

        Log::info('TEST RESOLUTION CALLBACK RECEIVED', [
            'payload' => $request->all(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Resolution callback received',
            'event' => $request->input('event'),
            'market_id' => $request->input('market.id'),
            'winning_answer_id' =>
                $request->input('market.winning_answer_id'),
        ]);

    })->name('test.resolution-callback');


    /*
    |--------------------------------------------------------------------------
    | CMS PAGES FALLBACK
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | This must always remain the LAST route.
    |
    | Examples:
    |
    | /about-us
    | /privacy-policy
    | /terms
    | /contacts
    |
    | These URLs are resolved through the pages table using alias.
    |
    */

    Route::fallback(function () {

        $alias = request()->path();

        return app(PageController::class)->show($alias);
    });



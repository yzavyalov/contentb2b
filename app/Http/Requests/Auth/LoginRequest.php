<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Login protection
    |--------------------------------------------------------------------------
    |
    | 1. Email + IP:
    |    5 failed attempts -> 5 minute lockout.
    |
    | 2. IP:
    |    20 failed attempts -> 10 minute lockout.
    |
    */

    private const ACCOUNT_MAX_ATTEMPTS = 5;

    private const ACCOUNT_DECAY_SECONDS = 300;

    private const IP_MAX_ATTEMPTS = 20;

    private const IP_DECAY_SECONDS = 600;


    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * Validation rules.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ];
    }


    /**
     * Attempt to authenticate.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        /*
         * Проверяем оба лимита ДО Auth::attempt().
         */
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(
            $this->only('email', 'password'),
            $this->boolean('remember')
        )) {

            /*
             * Неудачная попытка увеличивает оба счётчика.
             */

            RateLimiter::hit(
                $this->accountThrottleKey(),
                self::ACCOUNT_DECAY_SECONDS
            );

            RateLimiter::hit(
                $this->ipThrottleKey(),
                self::IP_DECAY_SECONDS
            );

            throw ValidationException::withMessages([
                /*
                 * Не сообщаем, существует ли такой email.
                 */
                'email' => __('auth.failed'),
            ]);
        }


        /*
         * Успешный вход.
         *
         * Сбрасываем лимит конкретного account + IP.
         *
         * Глобальный IP limiter намеренно НЕ очищаем:
         * успешный вход в один аккаунт не должен позволять
         * сбросить историю перебора других аккаунтов.
         */
        RateLimiter::clear(
            $this->accountThrottleKey()
        );
    }


    /**
     * Ensure login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        /*
         * ---------------------------------------------------------
         * GLOBAL IP LIMIT
         * ---------------------------------------------------------
         *
         * Защищает от перебора большого количества email
         * с одного IP.
         */

        if (
            RateLimiter::tooManyAttempts(
                $this->ipThrottleKey(),
                self::IP_MAX_ATTEMPTS
            )
        ) {

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn(
                $this->ipThrottleKey()
            );

            Log::warning('Login IP rate limit exceeded.', [
                'ip' => $this->ip(),
                'seconds_remaining' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' =>
                    'Too many login attempts. Please try again in '
                    . $seconds
                    . ' seconds.',
            ]);
        }


        /*
         * ---------------------------------------------------------
         * ACCOUNT + IP LIMIT
         * ---------------------------------------------------------
         *
         * Защищает конкретный аккаунт от перебора пароля
         * с конкретного IP.
         */

        if (
            RateLimiter::tooManyAttempts(
                $this->accountThrottleKey(),
                self::ACCOUNT_MAX_ATTEMPTS
            )
        ) {

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn(
                $this->accountThrottleKey()
            );

            Log::warning('Login account rate limit exceeded.', [
                /*
                 * Пароль никогда не логируем.
                 */
                'email' => Str::lower(
                    (string) $this->input('email')
                ),

                'ip' => $this->ip(),

                'seconds_remaining' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' =>
                    'Too many login attempts. Please try again in '
                    . $seconds
                    . ' seconds.',
            ]);
        }
    }


    /**
     * Rate limiter key for one email from one IP.
     */
    public function accountThrottleKey(): string
    {
        return 'login:account:'
            . Str::transliterate(
                Str::lower(
                    (string) $this->input('email')
                )
            )
            . '|'
            . $this->ip();
    }


    /**
     * Global login limiter for one IP.
     */
    public function ipThrottleKey(): string
    {
        return 'login:ip:' . $this->ip();
    }


    /**
     * Keep compatibility with Laravel's standard LoginRequest API.
     */
    public function throttleKey(): string
    {
        return $this->accountThrottleKey();
    }


}

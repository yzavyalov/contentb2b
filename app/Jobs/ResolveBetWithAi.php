<?php

namespace App\Jobs;

use App\Models\Bet;
use App\Models\BetAiResolution;
use App\Services\BetResolutionAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ResolveBetWithAi implements ShouldQueue
{
    use Queueable;

    public int $timeout = 240;

    public int $tries = 2;

    public function __construct(
        public int $betId
    ) {
    }

    public function handle(
        BetResolutionAiService $service
    ): void {

        $bet = Bet::query()
            ->with([
                'translations',
                'answers.translations',
                'sources',
            ])
            ->findOrFail(
                $this->betId
            );

        $service->resolve(
            $bet
        );
    }


    public function failed(
        Throwable $exception
    ): void {

        BetAiResolution::updateOrCreate(
            [
                'bet_id' =>
                    $this->betId,
            ],
            [
                'suggested_answer_id' =>
                    null,

                'confidence' =>
                    0,

                'summary' =>
                    'Automatic winner determination was unsuccessful. '
                    . 'Manual review is required.',

                'status' =>
                    'failed',

                'completed_at' =>
                    now(),
            ]
        );


        logger()->error(
            'Bet AI resolution failed',
            [
                'bet_id' =>
                    $this->betId,

                'error' =>
                    $exception->getMessage(),
            ]
        );
    }
}

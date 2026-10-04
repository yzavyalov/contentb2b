<?php

namespace App\Console\Commands;

use App\Enums\BetStatus;
use App\Jobs\ResolveBetWithAi;
use App\Jobs\SendMarketResolvingToMerchant;
use App\Models\Bet;
use App\Models\MerchantBet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessFinishedBets extends Command
{
    protected $signature = 'bets:process-finished';

    protected $description =
        'Move finished published markets to resolving and start resolution processing';

    public function handle(): int
    {
        $processed = 0;

        Bet::query()
            ->where('status', BetStatus::PUBLISHED->value)
            ->whereNotNull('finish_at')
            ->where('finish_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($bets) use (&$processed) {
                foreach ($bets as $bet) {
                    try {
                        $didProcess = DB::transaction(function () use ($bet) {
                            /*
                             * Atomic state transition.
                             *
                             * The WHERE conditions are repeated intentionally.
                             * If another scheduler/worker already moved this Bet
                             * to resolving, this UPDATE affects zero rows.
                             */
                            $updated = Bet::query()
                                ->whereKey($bet->id)
                                ->where('status', BetStatus::PUBLISHED->value)
                                ->whereNotNull('finish_at')
                                ->where('finish_at', '<=', now())
                                ->update([
                                    'status' => BetStatus::RESOLVING->value,
                                    'updated_at' => now(),
                                ]);

                            if ($updated !== 1) {
                                return false;
                            }

                            /*
                             * Start AI analysis.
                             *
                             * ResolveBetWithAi only prepares an AI recommendation.
                             * It must NOT resolve the Bet itself.
                             */
                            ResolveBetWithAi::dispatch($bet->id);

                            /*
                             * Notify only merchants that actually received
                             * this market.
                             */
                            MerchantBet::query()
                                ->where('bet_id', $bet->id)
                                ->whereNotNull('delivered_at')
                                ->select('id')
                                ->orderBy('id')
                                ->chunkById(100, function ($merchantBets) {
                                    foreach ($merchantBets as $merchantBet) {
                                        SendMarketResolvingToMerchant::dispatch(
                                            $merchantBet->id
                                        );
                                    }
                                });

                            return true;
                        });

                        if ($didProcess) {
                            $processed++;

                            $this->info(
                                "Bet #{$bet->id}: published -> resolving"
                            );
                        }
                    } catch (Throwable $e) {
                        report($e);

                        $this->error(
                            "Bet #{$bet->id}: {$e->getMessage()}"
                        );
                    }
                }
            });

        $this->info(
            "Finished. Markets moved to resolving: {$processed}"
        );

        return self::SUCCESS;
    }
}

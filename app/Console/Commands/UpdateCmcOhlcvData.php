<?php

namespace App\Console\Commands;

use App\ObserverOhlcv;
use App\Services\SleepService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateCmcOhlcvData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:cmc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    const OHLCV_INTERVALS = [
        '1d' => 'daily',
        '1w' => 'weekly',
        '30d' => 'monthly',
        '1h' => 'hourly',
        '4h' => '4h',
        '12h' => '12h'
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $notSavedData = ObserverOhlcv::query()->where('exchange', 'cmc')->where('fixed', false)->get();

        foreach ($notSavedData as $value) {

            $interval = !empty(self::OHLCV_INTERVALS[$value->interval]) ? self::OHLCV_INTERVALS[$value->interval] : null;
            if($interval) {
                $this->info("crypto:ohlcv call for " . $value->base_id . '/' . $value->quote_id);
                $this->info("from " . $value->time_start . ' to ' . $value->time_end);
                $this->info("table  ohlcv_cmc_" . $value->interval);
                Log::info("crypto:ohlcv call for " . $value->base_id . '/' . $value->quote_id);
                Log::info("from " . $value->time_start . ' to ' . $value->time_end);
                Log::info("table  ohlcv_cmc_" . $value->interval);
                Artisan::call('crypto:ohlcv', [
                    "--base_id" => $value->base_id,
                    "--quote_id" => $value->quote_id,
                    "--time_end" => $value->time_end,
                    "--time_start" => $value->time_start,
                    "--interval" => $interval,
                ]);
                sleep(0.7);
            }
            $value->fixed = true;
            $value->save();

        }

        $this->info("Finish");
        Log::info("Finish");

        $sleep = new SleepService();
        sleep($sleep->intervalSleep('everyMonth'));
    }
}

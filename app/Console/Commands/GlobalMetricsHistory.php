<?php

namespace App\Console\Commands;

use App\Services\GlobalMetricService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GlobalMetricsHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'global:historical {--interval=} {--time_start=} {--time_end=} {--count=} {--convert=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill the table global_metrics_historical_quotes.';

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
        $convert = !empty($this->option('convert')) ? $this->option('convert') : 'USD';
        $timeEnd = !empty($this->option('time_end')) ? $this->option('time_end') : date('Y-m-d 23:59', strtotime("-1 day"));
        $timeStart = !empty($this->option('time_start')) ? $this->option('time_start') : date('Y-m-d H:i', strtotime($timeEnd . ' -1 day'));
        $interval = !empty($this->option('interval')) ? $this->option('interval') : 'hourly';
        $count = !empty($this->option('count')) ? $this->option('count') : 10;
        $gmService = new GlobalMetricService();
        $result = $gmService->getGmHistoricalApiData($interval, $timeEnd, $timeStart, $convert, $count);
        $gmService->saveGmHistoricalRequests($timeStart, $timeEnd, $convert, $interval, $count);
        if ($result['status']['error_code'] == 0) {
            $gmService->saveGmHistoricalData( $result['data'], $convert, $interval, $count);
        } else {
            $this->info($result['status']['error_message']);
            Log::info($this->description . ' ' . $result['status']['error_message']);
        }
        if ($interval === 'hourly') {
            sleep(Config::get('commands_sleep.global_historical_hourly'));
        } elseif ($interval === 'daily') {
            sleep(Config::get('commands_sleep.global_historical_daily'));
        } elseif ($interval === 'weekly') {
            sleep(Config::get('commands_sleep.global_historical_weekly'));
        }
    }

}

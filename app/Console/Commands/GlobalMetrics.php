<?php

namespace App\Console\Commands;

use App\GlobalMetricsQuote;
use App\Services\CoinBaseService;
use DateTime;
use App\Services\SleepService;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\GlobalMetric;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GlobalMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'global:metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $query = [];
        // $converts = GlobalMetric::$symbols;
        // $convert = implode(',', $converts);
        $convert = 'USD';
        if ($convert !== '') {
            $query = ['convert' => $convert];
        }

        $client = new Client();

        $response = $client->get(env('API_COIN') . 'global-metrics/quotes/latest',
            [
                'headers' => ['X-CMC_PRO_API_KEY' => env('API_COIN_KEY')],
                'query' => $query,
            ]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'global-metrics/quotes/latest');

        $globalmetric = GlobalMetric::first();

        if (!$globalmetric) {
            $globalmetric = new GlobalMetric();
        }

        $globalmetric->active_cryptocurrencies = isset($result['data']['active_cryptocurrencies']) ? $result['data']['active_cryptocurrencies'] : null;
        $globalmetric->active_market_pairs = isset($result['data']['active_market_pairs']) ? $result['data']['active_market_pairs'] : null;
        $globalmetric->active_exchanges = isset($result['data']['active_exchanges']) ? $result['data']['active_exchanges'] : null;
        $globalmetric->eth_dominance = isset($result['data']['eth_dominance']) ? $result['data']['eth_dominance'] : null;
        $globalmetric->btc_dominance = isset($result['data']['btc_dominance']) ? $result['data']['btc_dominance'] : null;

        if (isset($result['data']['last_updated'])) {
            $lastUpdated = new DateTime($result['data']['last_updated']);
            $globalmetric->last_updated = $lastUpdated->format('Y-m-d H:i:s');
        } else {
            $globalmetric->last_updated = null;
        }


        if ($globalmetric->save()) {

            if (isset($result['data']['quote'])) {
//                    $bulkInsert = [];
//                    $nowTime = date('Y-m-d H:i:s');

                foreach ($result['data']['quote'] as $symbol => $quote) {

                    if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                        $this->info('Done for ' . $symbol);
                    } else {
                        if (!empty($result['status'])) {
                            $this->info('Fails for  ' . $symbol . ' ' . $result['status']['error_message']);
                            Log::info($this->description . ' fails for  ' . $symbol . ' ' . $result['status']['error_message']);
                        } else {
                            $this->info('Fails for  ' . $symbol);
                            Log::info($this->description . ' fails for  ' .$symbol);
                        }
                    }

                    $globalMetricsQuote = GlobalMetricsQuote::firstOrNew(['symbol' => $symbol]);
                    $globalMetricsQuote->global_metric_id = $globalmetric->id;
                    $globalMetricsQuote->symbol = $symbol;
                    $globalMetricsQuote->total_market_cap = $quote['total_market_cap'];
                    $globalMetricsQuote->total_volume_24h = $quote['total_volume_24h'];
                    $globalMetricsQuote->last_updated = $quote['last_updated'];

                    if (isset($result['data']['last_updated'])) {
                        $lastUpdated = new DateTime($quote['last_updated']);
                        $globalMetricsQuote->last_updated = $lastUpdated->format('Y-m-d H:i:s');
                    } else {
                        $globalMetricsQuote->last_updated = null;
                    }

                    $globalMetricsQuote->save();
                }

//                    if (!empty($bulkInsert)) {
//
//                        foreach (array_chunk($bulkInsert, 250) as $bulkInsertItem) {
//                            GlobalMetricQuote::insert($bulkInsertItem);
//                        }
//
//                    }

            }
            Log::info('global:metrics saved for date ' . date('Y-m-d'));
        }
        Log::info('global:metrics executed at ' . date('Y-m-d'));
        $sleep = new SleepService;
        sleep($sleep->intervalSleep('everyTenMinute'));
        
    }
}

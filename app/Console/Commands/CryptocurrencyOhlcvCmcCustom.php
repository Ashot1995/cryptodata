<?php

namespace App\Console\Commands;

use App\Services\SleepService;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\Services\CurrencyOhlcvService;
use App\Services\MarketPairService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use phpDocumentor\Reflection\DocBlock\Description;

class CryptocurrencyOhlcvCmcCustom extends Command
{
    const INTERVAL_HOURLY = "hourly";
    const INTERVAL_4H = "4h";
    const INTERVAL_12H = "12h";
    const INTERVAL_DAILY = "daily";
    const INTERVAL_WEEKLY = "weekly";
    const INTERVAL_MONTHLY = "monthly";
    const INTERVAL_MODELS = [
        'daily' => 'OhlcvModels\ohlcv_cmc_1d',
        'weekly' => 'OhlcvModels\ohlcv_cmc_1w',
        'monthly' => 'OhlcvModels\ohlcv_cmc_30d',
        'hourly' => 'OhlcvModels\ohlcv_cmc_1h',
        '4h' => 'OhlcvModels\ohlcv_cmc_4h',
        '12h' => 'OhlcvModels\ohlcv_cmc_12h'
    ];

    const TIME_SEPARATORS = [
        'monthly' => 60 * 60 * 24 * 30,
        'weekly' => 60 * 60 * 24 * 7,
        'daily' => 60 * 60 * 24,
        '12h' => 60 * 60 * 12,
        '4h' => 60 * 60 * 4,
        'hourly' => 60 * 60,
    ];

    const DEFAULT_COINS_QUOTES_SYMBOL = 'USD';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:ohlcv {--base_symbol=} {--quote_symbol=} {--base_id=} {--quote_id=} {--time_end=} {--time_start=} {--interval=} {--pairs_convert=no} {--rewrite=no}';

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
        $service = new CurrencyOhlcvService();
        $marketPairService = new MarketPairService();

        if (!empty($this->option('base_symbol')) && !empty($this->option('base_id'))) {
            $this->info('contains a conflict between exclusive peers [base_symbol, base_id]');
        } elseif (!empty($this->option('base_symbol'))) {
            $cryptName = $this->option('base_symbol');
            $currency = Cryptocurrency::select('cryptocurrency_id', 'id', 'symbol')->where('symbol', $cryptName)->first();
        } elseif (!empty($this->option('base_id'))) {
            $baseId = $this->option('base_id');
            $currency = Cryptocurrency::select('cryptocurrency_id', 'id', 'symbol')->where('cryptocurrency_id', $baseId)->first();
        } else {
            $this->info('must select at least one of [base_id, base_symbol]');
            return;
        }

        if (!empty($this->option('quote_symbol')) && !empty($this->option('quote_id'))) {
            $this->info('contains a conflict between exclusive peers [base_symbol, quote_id]');
        } elseif (!empty($this->option('quote_symbol'))) {
            $quoteSymbol = $this->option('quote_symbol');
            $quote = Cryptocurrency::where('symbol', $quoteSymbol)->first();
        } elseif (!empty($this->option('quote_id'))) {
            $quoteId = $this->option('quote_id');
            $quote = Cryptocurrency::where('cryptocurrency_id', $quoteId)->first();
        } else {
            $quote = Cryptocurrency::where('symbol', self::DEFAULT_COINS_QUOTES_SYMBOL)->first();
        }

        if (!$quote || !$currency) {
            return;
        }

        $pairsConvert = $this->option('pairs_convert');
        $rewrite = $this->option('rewrite');

        $interval = !empty($this->option('interval')) ? $this->option('interval') : self::INTERVAL_DAILY;
        if ($interval == self::INTERVAL_HOURLY || $interval == self::INTERVAL_4H || $interval == self::INTERVAL_12H) {
            $timePeriod = self::INTERVAL_HOURLY;
        } else {
            $timePeriod = self::INTERVAL_DAILY;
        }
        $timeEnd = !empty($this->option('time_end')) ? date('Y-m-d  23:59:59', strtotime($this->option('time_end'))) : date('Y-m-d 23:59:59', strtotime("-1 day"));

        if ($interval == self::INTERVAL_WEEKLY) {
            $timeStart = !empty($this->option('time_start')) ? date('Y-m-d  23:59:59', strtotime($this->option('time_start') . ' -1 day')) : date('Y-m-d H:i:s', strtotime($timeEnd . "-1 week"));
        } elseif ($interval == self::INTERVAL_MONTHLY) {
            $timeStart = !empty($this->option('time_start')) ? date('Y-m-d  23:59:59', strtotime($this->option('time_start') . ' -1 month')) : date('Y-m-d H:i:s', strtotime($timeEnd . "-1 month"));
        } else {
            $timeStart = !empty($this->option('time_start')) ? date('Y-m-d  23:59:59', strtotime($this->option('time_start') . ' -1 day')) : date('Y-m-d H:i:s', strtotime($timeEnd . "-1 day"));
        }

        if (!array_key_exists($interval, self::INTERVAL_MODELS)) {
            $this->info("not allowed interval value");
            return false;
        }

        if ($pairsConvert != "no") {
            $marketPairs = $marketPairService->getPairsByCurrency($currency->cryptocurrency_id);

            foreach ($marketPairs as $pair) {
                //check if exist data for pair $pair->coin1_symbol . ' / ' . $pair->coin2_symbol and count is ok
                $pairOhlcv = $service->getOhlcvForPair($pair->string1_id, $pair->string2_id, $timeStart, $timeEnd, self::INTERVAL_MODELS[$interval]);
                $quotePair = Cryptocurrency::where('symbol', $pair->coin2_symbol)->first();
                if (!$quotePair) {
                    $this->info('Fails for pair ' . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol . ' , there is no currency for ' . $pair->coin2_symbol);
                    continue;
                }

                $quoteId = $quotePair->cryptocurrency_id;

                $rowsCount = $this->calculateDateDifference($timeStart, $timeEnd, $interval);


                if ($rewrite == "no" && $pairOhlcv->count() && $rowsCount == $pairOhlcv->count()) {
                    $this->info("skip for " . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol);
                    continue;
                }

                $result = $service->getCryptoCurrencyOhlcvApiData($currency->id, $pair->coin1_symbol, $timePeriod, $interval, $timeEnd, $timeStart, $pair->coin2_symbol);
                if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                    $service->saveCryptoCurrencyOhlcvData($result['data']['quotes'], self::INTERVAL_MODELS[$interval], $currency->cryptocurrency_id, $quoteId, $pair->coin2_symbol);
                    $this->info('Done for ' . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol);
                } else if (!empty($result['status'])) {
                    $this->info('Fails for pair ' . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol . ' ' . $result['status']['error_message']);
                    Log::info($this->description . ' fails for pair ' . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol . ' ' . $result['status']['error_message']);
                } else {
                    $this->info('Fails for pair ' . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol);
                    Log::info($this->description . ' fails for pair ' . $pair->coin1_symbol . ' / ' . $pair->coin2_symbol);
                }

                if ($interval !== self::INTERVAL_HOURLY) {
                    sleep(1);
                }
            }
        } else {

            if (!$quote) {
                return false;
            }

            $quoteId = $quote->cryptocurrency_id;
            $pairOhlcv = $service->getOhlcvForPair($currency->id, $quoteId, $timeStart, $timeEnd, self::INTERVAL_MODELS[$interval]);
            $rowsCount = $this->calculateDateDifference($timeStart, $timeEnd, $interval);

            if ($rewrite == "no" && $pairOhlcv->count() && $rowsCount == $pairOhlcv->count()) {
                $this->info("skip for " . $currency->symbol . "/" . $quote->symbol);
                return;
            }

            $result = $service->getCryptoCurrencyOhlcvApiData($currency->id, $currency->symbol, $timePeriod, $interval, $timeEnd, $timeStart, $quote->symbol);

            if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                $service->saveCryptoCurrencyOhlcvData($result['data']['quotes'], self::INTERVAL_MODELS[$interval], $currency->cryptocurrency_id, $quoteId, $quote->symbol);
                $this->info('Done for ' . $currency->symbol . ' / ' . $quote->symbol);
            } else if (!empty($result['status'])) {
                $this->info('cryptocurrency_id ' . $currency->cryptocurrency_id . ' ' . $result['status']['error_message']);
                Log::info($this->description . ' fails for pair ' . $currency->symbol . ' / ' . $quote->symbol . ' ' . $result['status']['error_message']);
            }

            sleep(1);
        }

    }

    protected function calculateDateDifference($timeStart, $timeEnd, $interval)
    {
        $firstDate = strtotime($timeStart);
        $lastDate = strtotime($timeEnd);
        $datediff = $lastDate - $firstDate;

        if ($datediff < 0) {
            return false;
        }

        $dateCount = $datediff / self::TIME_SEPARATORS[$interval];
        return $dateCount;
    }

}

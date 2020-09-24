<?php

namespace App\Console\Commands;

use App\Http\DateFormat\DateFormat;
use App\ObserverOhlcv;
use App\Services\CryptoCurrencyService;
use App\Services\MarketPairService;
use App\Services\SleepService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class ObserveOhlcv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'observe:ohlcv {--time_start=} {--time_end=} {--exchange=} {--interval=} {--pairs_convert=no} {--base_symbol=} {--quote_symbol=}';

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

    const EXCHANGE_NAMES = ['binance', 'bitfinex', 'bitstamp', 'bittrex', 'coinbase', 'huobi_pro', 'okex', 'poloniex', 'cmc'];
    const TIME_FRAMES = ['30d', '1w', '1d', '12h', '4h', '1h', '30m', '15m', '5m', '1m'];
    const CMC_TIME_FRAMES = ['30d', '1w', '1d', '12h', '4h', '1h'];
    const DEFAULT_TABLE_PREFIX = 'ohlcv_';
    const DEFAULT_MODEL_PATH = 'App\OhlcvModels';
    const TIME_SEPARATORS = [
        '30d' => 60 * 60 * 24 * 30,
        '1w' => 60 * 60 * 24 * 7,
        '1d' => 60 * 60 * 24,
        '12h' => 60 * 60 * 12,
        '4h' => 60 * 60 * 4,
        '1h' => 60 * 60,
        '30m' => 60 * 30,
        '15m' => 60 * 15,
        '5m' => 60 * 5,
        '1m' => 60
    ];
    const TIME_INCREASE = [
        '30d' => "1 month",
        '1w' => "1 week",
        '1d' => "1 day",
        '12h' => "12 hours",
        '4h' => "4 hours",
        '1h' => "1 hour",
        '30m' => "30 minutes",
        '15m' => "15 minutes",
        '5m' => "5 minutes",
        '1m' => "1 minutes"
    ];
    const DATE_FORMATES = [
        '30d' => "%Y-%m-%d 00:00:00",
        '1w' => "%Y-%m-%d 00:00:00",
        '1d' => "%Y-%m-%d 00:00:00",
        '12h' => "%Y-%m-%d %H:00:00",
        '4h' => "%Y-%m-%d %H:00:00",
        '1h' => "%Y-%m-%d %H:00:00",
        '30m' => "%Y-%m-%d %H:%i:00",
        '15m' => "%Y-%m-%d %H:%i:00",
        '5m' => "%Y-%m-%d %H:%i:00",
        '1m' => "%Y-%m-%d %H:%i:00"
    ];
    const DEFAULT_COINS_QUOTES_SYMBOL = 'USD';

    public function handle()
    {
        $timeStart = $this->option('time_start');
        $timeEnd = $this->option('time_end');
        $exchange = $this->option('exchange');

        if (!in_array($exchange, self::EXCHANGE_NAMES)) {
            $this->info("exchange does not exist");
            return;
        }

        $interval = $this->option('interval');

        if ($exchange == "cmc" && !in_array($interval, self::CMC_TIME_FRAMES)) {
            $this->info("wrong interval");
            return;
        } elseif (!in_array($interval, self::TIME_FRAMES)) {
            $this->info("wrong interval");
            return;
        }

        $pairsConvert = $this->option('pairs_convert');

        $baseCryptocurrency = !empty($this->option('base_symbol')) ? $this->option('base_symbol') : null;
        $quoteCryptocurrency = !empty($this->option('quote_symbol')) ? $this->option('quote_symbol') : null;

        $cryptoService = new CryptoCurrencyService();
        $marketPairService = new MarketPairService();
        $marketStartTimeArr = Config::get('exchangesStartTime.' . $exchange);
        $marketStartTime = isset($marketStartTimeArr[$interval]) ? $marketStartTimeArr[$interval] : DateFormat::DATE_FORMAT;

        $timeStartObj = new Carbon($timeStart);

        if ($interval == "1w") {
            $timeStart = $timeStartObj->startOfWeek()->addDays($marketStartTime)->format("Y-m-d 00:00:00");
        } elseif ($interval == "30d" && $marketStartTime == "first day") {
            $timeStart = $timeStartObj->startOfMonth()->format("Y-m-d 00:00:00");
        } elseif ($interval == "30d" && $marketStartTime == "last day") {
            $timeStart = $timeStartObj->endOfMonth()->format("Y-m-d 00:00:00");
        } else {
            $timeStart = $timeStartObj->format($marketStartTime);
        }

        $timeEnd = Carbon::createFromFormat(DateFormat::DATE_FORMAT, $timeEnd)->format("Y-m-d 23:59:59");

        $newArr = [];
        $lastDate = $timeStart;
        $newArr[] = $lastDate;

        while ($lastDate < $timeEnd) {
            $newArr[] = $lastDate;
            $lastDate = date(DateFormat::DATE_TIME_FORMAT, strtotime($lastDate . self::TIME_INCREASE[$interval]));
        }
        $modelName = self::DEFAULT_MODEL_PATH . '\\' . self::DEFAULT_TABLE_PREFIX . $exchange . '_' . $interval;
        $count = $this->calculateDateDifference($timeStart, $timeEnd, $interval);
        $quote = $cryptoService->getCryptoCurrencyBySymbol(self::DEFAULT_COINS_QUOTES_SYMBOL);

        if (!$count) {
            $this->info('time start must be higher than time end');
            return;
        }

        if (!$baseCryptocurrency) {
            if ($exchange == 'cmc') {
                $cryptocurrencies = $cryptoService->getAllCurrencies();

                foreach ($cryptocurrencies as $cryptocurrency) {
//                $firstData = strtotime($timeStart);
//                $lastData = strtotime($timeEnd);
//                $firstData2 = strtotime($cryptocurrency->first_historical_data);
//                $lastData2 = strtotime($cryptocurrency->last_historical_data);
//
//                if ($firstData < $firstData2) {
//                    $timeStart = $cryptocurrency->first_historical_data;
//                    $firstData = strtotime($timeStart);
//                }
//
//                if ($lastData > $lastData2) {
//                    $timeEnd = $cryptocurrency->last_historical_data;
//                    $lastData = strtotime($timeEnd);
//                }

//                $datediff = $lastData - $firstData;
//
//                if ($datediff < 0) {
//                    continue;
//                }

//                $count = $datediff / self::TIME_SEPARATORS[$interval];

                    if ($pairsConvert != "no") {
                        $marketPairs = $marketPairService->getPairsByCurrency($cryptocurrency->cryptocurrency_id);

                        $doForDefault = true;
                        foreach ($marketPairs as $pair) {
                            $this->observeForPair($pair->coin1_symbol, $pair->coin2_symbol, $modelName, $exchange, $pair->string1_id, $pair->string2_id, $timeStart, $timeEnd, $count, $interval, $newArr);

                            if ($pair->coin2_symbol == self::DEFAULT_COINS_QUOTES_SYMBOL) {
                                $doForDefault = false;
                            }
                        }
                        if ($doForDefault) {
                            $this->observeForPair($cryptocurrency->symbol, $quote->symbol, $modelName, $exchange, $cryptocurrency->cryptocurrency_id, $quote->cryptocurrency_id, $timeStart, $timeEnd, $count, $interval, $newArr);
                        }
                    } else {

                        if ($quoteCryptocurrency) {
                            $quote = $cryptoService->getCryptoCurrencyBySymbol($quoteCryptocurrency);

                        } else {
                            $quote = $cryptoService->getCryptoCurrencyBySymbol(self::DEFAULT_COINS_QUOTES_SYMBOL);
                        }

                        if (!$quote) {
                            $this->info("cryptocurrency with this quote_symbol = " . $quoteCryptocurrency . " is absent");
                            Log::info("cryptocurrency with this quote_symbol = " . $quoteCryptocurrency . " is absent");
                            return false;
                        }

                        $this->observeForPair($cryptocurrency->symbol, $quote->symbol, $modelName, $exchange, $cryptocurrency->cryptocurrency_id, $quote->cryptocurrency_id, $timeStart, $timeEnd, $count, $interval, $newArr);
                    }
                }
            } else {

                $exchanges = \ccxt\Exchange::$exchanges;

                if (!in_array($exchange, $exchanges)) {
                    dd('Обменника с таким именем не существует');
                }

                $exchangeClass = "\\ccxt\\" . $exchange;
                $exchangeObj = new $exchangeClass();


                // Получаю список всех валют торгуемых на бирже
                $cryptocurrencies_stock = $exchangeObj->load_markets();

                foreach ($cryptocurrencies_stock as $key => $value) {
                    $cryptocurrency = $cryptoService->getCryptoCurrencyBySymbol($value['base']);
                    $quote = $cryptoService->getCryptoCurrencyBySymbol($value['quote']);
                    if (!$cryptocurrency) {
                        $this->info("cryptocurrency with this symbol = " . $value['base'] . " is absent");
                        Log::info("cryptocurrency with this symbol = " . $value['base'] . " is absent");
                        continue;
                    }
                    if (!$quote) {
                        $this->info("cryptocurrency with this symbol = " . $value['quote'] . " is absent");
                        Log::info("cryptocurrency with this symbol = " . $value['quote'] . " is absent");
                        continue;
                    }
                    $this->observeForPair($cryptocurrency->symbol, $quote->symbol, $modelName, $exchange, $cryptocurrency->cryptocurrency_id, $quote->cryptocurrency_id, $timeStart, $timeEnd, $count, $interval, $newArr);
                }
            }
        } else {
            $cryptocurrency = $cryptoService->getCryptoCurrencyBySymbol($baseCryptocurrency);
            if ($pairsConvert != "no" && $exchange == 'cmc') {
                $marketPairs = $marketPairService->getPairsByCurrency($cryptocurrency->cryptocurrency_id);

                foreach ($marketPairs as $pair) {
                    $this->observeForPair($pair->coin1_symbol, $pair->coin2_symbol, $modelName, $exchange, $pair->string1_id, $pair->string2_id, $timeStart, $timeEnd, $count, $interval, $newArr);
                }
            } else {

                if ($quoteCryptocurrency) {
                    $quote = $cryptoService->getCryptoCurrencyBySymbol($quoteCryptocurrency);

                }

                if (!$quote) {
                    $this->info("cryptocurrency with this quote_symbol = " . $quoteCryptocurrency . " is absent");
                    Log::info("cryptocurrency with this quote_symbol = " . $quoteCryptocurrency . " is absent");
                    return false;
                }

                $this->observeForPair($cryptocurrency->symbol, $quote->symbol, $modelName, $exchange, $cryptocurrency->cryptocurrency_id, $quote->cryptocurrency_id, $timeStart, $timeEnd, $count, $interval, $newArr);
            }

        }

        $this->info('Finish');
        Log::info("Finish");

        $sleep = new SleepService();
        sleep($sleep->intervalSleep('everyMonth'));
    }

    protected function large_array_diff($b, $a)
    {
        $at = array();
        foreach ($a as $i)
            $at[$i] = 1;

        $d = array();

        foreach ($b as $i)
            if (!isset($at[$i]))
                $d[] = $i;

        return $d;
    }

    protected function flip_isset_diff($b, $a)
    {
        $at = array_flip($a);
        $d = array();
        foreach ($b as $i)
            if (!isset($at[$i]))
                $d[] = $i;
        return $d;
    }

    private function observeForPair($coin1Symbol, $coin2Symbol, $modelName, $exchange, $string1Id, $string2Id, $timeStart, $timeEnd, $count, $interval, $newArr)
    {
        $notSavedCoins = [];
        $notSavedCoins['symbol'] = $coin1Symbol;
        $notSavedCoins['base_id'] = $string1Id;
        $notSavedCoins['quote_id'] = $string2Id;
        $notSavedCoins['convert'] = $coin2Symbol;
        $ohlcvData = $modelName::where('base_id', $string1Id)
            ->select(DB::raw("DATE_FORMAT(timestamp, '" . self::DATE_FORMATES[$interval] . "') as timestamp"))
            ->where('quote_id', $string2Id)
            ->whereBetween('timestamp', [$timeStart, $timeEnd])
            ->orderBy('timestamp')
            ->pluck('timestamp')->toArray();

        if (count($ohlcvData) == 0) {
            $notSavedCoins['date']['timeStart'] = $timeStart;
            $notSavedCoins['date']['timeEnd'] = $timeEnd;
            ObserverOhlcv::updateOrCreate([
                    'base_id' => $string1Id,
                    'quote_id' => $string2Id,
                    'time_start' => $timeStart,
                    'time_end' => $timeEnd,
                    'exchange' => $exchange,
                    'interval' => $interval
                ]
            );
        } elseif (count($ohlcvData) == round($count)) {
            $notSavedCoins['date'] = "All data exist for this period";
        } else {
            $notSavedData = $this->large_array_diff($newArr, $ohlcvData);

            if (count($notSavedData) > 0) {
                $notSavedDataIntervals = $this->convertToIntervals($notSavedData, $interval);
                $notSavedCoins['date'] = $notSavedDataIntervals;

                foreach ($notSavedDataIntervals as $dateInterval) {
                    ObserverOhlcv::updateOrCreate([
                            'base_id' => $string1Id,
                            'quote_id' => $string2Id,
                            'time_start' => $dateInterval['time_start'],
                            'time_end' => $dateInterval['time_end'],
                            'exchange' => $exchange,
                            'interval' => $interval
                        ]
                    );
                }
            } else {
                $notSavedCoins['date'] = "duplicated data";
            }
        }

        dump($notSavedCoins);
        Log::info($notSavedCoins);


    }

    protected function convertToIntervals($notSavedData, $interval)
    {
        $intervals = [];
        $intervalArr ['time_start'] = $notSavedData[0];

        foreach ($notSavedData as $key => $value) {

            if (isset($notSavedData[$key + 1])) {
                $count = $this->calculateDateDifference($notSavedData[$key], $notSavedData[$key + 1], $interval);

                if ($count > 1) {
                    $intervalArr['time_end'] = $notSavedData[$key];
                    $intervals[] = $intervalArr;
                    $intervalArr = [];
                    $intervalArr ['time_start'] = $notSavedData[$key + 1];
                }
            } else if ($value == end($notSavedData)) {
                $intervalArr['time_end'] = $notSavedData[$key];
                $intervals[] = $intervalArr;
            }
        }
        return $intervals;
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

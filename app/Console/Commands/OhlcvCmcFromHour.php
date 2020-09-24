<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\OhlcvModels\ohlcv_cmc_1h;
use App\Services\CryptoCurrencyService;
use Illuminate\Support\Facades\Config;
use App\Services\SleepService;

class OhlcvCmcFromHour extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ohlcv:build {--table=cmc}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Build OHLCV from hour candle';

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
     * TN-384
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cryptoService = new CryptoCurrencyService;
        $quote_id = $cryptoService->getIdCurrencyByTicker('USD');
        $table = $this->option('table');
        $base_timeframe = '1h';
        $coins = Cryptocurrency::orderBy('cryptocurrency_id')->pluck('cryptocurrency_id')->toArray();
        $timeframes = [
            '4h' => 4,
            '12h' => 12,
            '1d' => 24,
            '1w' => 168,
            '30d' => 672,
        ];
        foreach ($coins as $base_id) {
            dump($base_id);
            foreach ($timeframes as $timeframe => $countHourCandle) {
                dump($timeframe);
                $className = 'App\OhlcvModels\ohlcv_' . $table . '_' . $timeframe;
                $baseClassName = 'App\OhlcvModels\ohlcv_' . $table . '_' . $base_timeframe;
                // $className::where('base_id', $base_id)->where('quote_id', $quote_id)->whereDate('timestamp', '>=', '2019-02-25')->delete();
                // dd();
                $lastDateTimeframeDb = $className::where('base_id', $base_id)->where('quote_id', $quote_id)->select('timestamp')->orderBy('timestamp', 'desc')->first();
                // dump($lastDateTimeframeDb);
                if ($lastDateTimeframeDb) {
                    $lastDateTimeframe = date('Y-m-d H:i:s', strtotime($lastDateTimeframeDb->timestamp . " +{$countHourCandle} hour"));
                    $lastDateHourData = $baseClassName::where('base_id', $base_id)
                        ->where('quote_id', $quote_id)
                        ->whereDate('timestamp', '>=', date('Y-m-d', strtotime($lastDateTimeframe)))
                        ->select('open', 'high', 'close', 'low', 'timestamp', 'market_cap')
                        ->orderBy('timestamp', 'asc')
                        ->get();
                } else {
                    $lastDateHourData = $baseClassName::where('base_id', $base_id)
                        ->where('quote_id', $quote_id)
                        ->select('open', 'high', 'close', 'low', 'timestamp', 'market_cap')
                        ->orderBy('timestamp', 'asc')
                        ->get();
                    if ($lastDateHourData->count() > 0) {
                        $lastDateTimeframe = $lastDateHourData->first()->timestamp;
                        if (($timeframe == '1w') && (date('w', strtotime($lastDateTimeframe)) != '1')) {
                            foreach ($lastDateHourData as $key => $value) {
                                if (date('w', strtotime($value->timestamp)) == '1') {
                                    $lastDateTimeframe = $value->timestamp;
                                    break;
                                }
                            }
                        }
                        if (($timeframe == '1d') && (date('H', strtotime($lastDateTimeframe)) != '00')) {
                            foreach ($lastDateHourData as $key => $value) {
                                if (date('H', strtotime($lastDateTimeframe)) == '00') {
                                    $lastDateTimeframe = $value->timestamp;
                                    break;
                                }
                            }
                        }
                        if (($timeframe == '30d') && (date('d', strtotime($lastDateTimeframe)) != '01')) {
                            foreach ($lastDateHourData as $key => $value) {
                                if (date('d', strtotime($value->timestamp)) == '01') {
                                    $lastDateTimeframe = $value->timestamp;
                                    break;
                                }
                            }
                        }
                    }
                }
                
                if ($lastDateHourData->count() > 0) {
                    $lastDateHourArray = $lastDateHourData->toArray();

                    $ohlcvArray = [];
                    foreach ($lastDateHourArray as $key => $value) {
                        if (strtotime($value['timestamp']) >= strtotime($lastDateTimeframe)) {
                            $ohlcvArray[] = $value;
                        }
                    }
                    if (count($ohlcvArray) >= $countHourCandle) {
                        $candlesArray = [];
                        $countCandles = floor(count($ohlcvArray) / $countHourCandle);
                        for ($i = 0; $i < $countCandles; $i++) {
                            if ($i == 0) {
                                $time = strtotime($lastDateTimeframe);
                            }
                            if ($timeframe == '30d') {
                                $countHourCandle = (int)date('t', $time) * 24;
                            }
                            $nextTime = strtotime(date('Y-m-d H:i:s', $time) . "+{$countHourCandle} hour");
                            $timeframeArray = [];
                            foreach ($ohlcvArray as $key => $value) {
                                if ((strtotime($value['timestamp']) >= $time) && (strtotime($value['timestamp']) < $nextTime)) {
                                    $timeframeArray[] = $value;
                                }
                            }
                            if (count($timeframeArray) == $countHourCandle) {
                                $open = $timeframeArray[0]['open'];
                                $timestamp = $timeframeArray[$countHourCandle - 1]['timestamp'];
                                $close = $timeframeArray[$countHourCandle - 1]['close'];
                                $market_cap = $timeframeArray[$countHourCandle - 1]['market_cap'];
                                $low = (float)$timeframeArray[0]['low'];
                                $high = (float)$timeframeArray[0]['high'];
                                foreach ($timeframeArray as $key => $value) {
                                    if ((float)$value['high'] > $high) {
                                        $high = (float)$value['high'];
                                    }
                                    if ((float)$value['low'] < $low) {
                                        $low = (float)$value['low'];
                                    }
                                }
                                $candlesArray[] = [
                                    'base_id' => $base_id,
                                    'quote_id' => $quote_id,
                                    'open' => $open,
                                    'close' => $close,
                                    'low' => $low,
                                    'high' => $high,
                                    'market_cap' => $market_cap,
                                    'timestamp' => $timestamp,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                            }

                            $time = $nextTime;
                        }
                        $className::insert($candlesArray);
                        dump($candlesArray);
                    }
                }

            }
        }
        $sleep = new SleepService;
        $this->info('stop succesfull update tn indexes ' . date('H:i:s d-m-Y'));

        sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.ohlcv_build')));
    }
}

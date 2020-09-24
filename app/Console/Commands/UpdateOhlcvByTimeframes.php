<?php

namespace App\Console\Commands;

use App\CcxtOhlcv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Cryptocurrency;
use App\ExchangePairQuotes;
use App\UndefinedTicker;
use App\Services\SleepService;

class UpdateOhlcvByTimeframes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:exchange_ohlcv_by_timeframe {--exchangeName=binance} {--pause=0} {--interval=1m}';

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

        $pairs = ExchangePairQuotes::all();
        $interval_array = [
            '1m' => '1m',
            '5m'=> '5m',    
            '15m'=> '15m',
            '30m'=> '30m',
            '1h'=> '1h',
            '4h'=> '4h',
            '12h'=> '12h',
            '1d'=> '1d',
            '1w'=> '1w',
            '30d'=> '1M',
        ];
        $cryptocurrency_array = [];
        $cryptocurrencies = Cryptocurrency::select('cryptocurrency_id', 'symbol')->get();
        foreach ($cryptocurrencies as $key => $cryptocurrency) {
            $cryptocurrency_array[$cryptocurrency->cryptocurrency_id] = $cryptocurrency->symbol;
        }

        $exchangeName = $this->option('exchangeName');
        $defaultInterval = $this->option('interval');
        
        $exchanges = \ccxt\Exchange::$exchanges;
        if (!in_array($exchangeName, $exchanges)) {
            dd('Обменника с таким именем не существует');
        }
        $exchangeClass = "\\ccxt\\" . $exchangeName;
        $exchange = new $exchangeClass(); 


        // Получаю список всех валют торгуемых на бирже
        $cryptocurrencies_stock = $exchange->load_markets();
        $cryptocurrencies_pairs_array = [];
        foreach ($cryptocurrencies_stock as $key => $value) {
            $cryptocurrencies_pairs_array[] = $key;
        }
        ////////////////////////////////////////////////

        $start = microtime(true);
        
        $exchangeNameClass = $exchangeName;
        if ($exchangeName == "huobipro") {
            $exchangeNameClass = 'huobi_pro';
        }
        $className = 'App\OhlcvModels\ohlcv_' . $exchangeNameClass . '_' .  array_search($defaultInterval, $interval_array);
        if (($defaultInterval === '1m') && (date('H') == '00') && (date('i') < 10)) {
            $className::whereDate('timestamp', '<', date('Y-m-d H:i:s', strtotime('-14 day')))->delete();
        }
        $formattedDataArray = [];
        foreach ($cryptocurrencies_pairs_array as $key_cryptocurrencies => $value_cryptocurrencies) {
            $this->info('Обрабатывается: '  .$value_cryptocurrencies. '  , '. $key_cryptocurrencies .  ' , всего валют: ' . count($cryptocurrencies_pairs_array). ' , время работы скрипта: ' . round(microtime(true) - $start, 2).' сек.');
            $symbol = $value_cryptocurrencies;

            if(!isset($exchange->markets[$symbol])) {
                $this->info('У обменника ' . $exchangeName . ' нету пары ' . $symbol);
                continue;
            }
            if (!$exchange->has('fetchOHLCV')) {
                dd('Обменник не содержит информацию о OHLCV');
                continue;
            }
            if (!isset($exchange->timeframes[$defaultInterval])) {
                dd('Такого временного интервала нет у обменника ' . $exchangeName);
                continue;
            }
                
            
            if ($defaultInterval) {

                $formattedData = $this->getDataForExchangeBySymbol($exchange, $symbol, $defaultInterval, $className, $cryptocurrency_array);
                if ($formattedData == false) {
                    continue;
                }
                
                // if (count($formattedData) >= 5000) {
                $className::updateOrCreate(['base_id' => $formattedData[0]['base_id'], 'quote_id' => $formattedData[0]['quote_id'], 'timestamp' => $formattedData[0]['timestamp']], $formattedData[0]);
                unset($formattedData[0]);
                if (count($formattedData) !== 0) {
                    $chuncks = array_chunk($formattedData, 5000);
                    foreach ($chuncks as $insert) {
                        $className::insert($insert);
                    }
                }
                $this->info('Сохранено записей в базу ' . count($formattedData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $defaultInterval);
                // }else{
                //     $formattedDataArray = array_merge($formattedDataArray, $formattedData);
                //     if (count($formattedDataArray) > 10000) {
                //         $chuncks = array_chunk($formattedDataArray, 5000);
                //         foreach ($chuncks as $insert) {
                //             $className::insert($insert);
                //         }
                //         $formattedDataArray = [];
                //     }
                // }
                
            }   
        }
        if (count($formattedDataArray)>0) {
            $chuncks = array_chunk($formattedDataArray, 5000);
            foreach ($chuncks as $insert) {
                $className::insert($insert);
            }
            $this->info('Сохранено записей в базу ' . count($formattedDataArray) . ' для всех пар  в обменнике ' . $exchange->name . ' с интервалом ' . $defaultInterval);
        }
        

        $sleep = new SleepService;
        switch ($defaultInterval) {
            case '1m':
                sleep($sleep->intervalSleep('everyEightHours'));
                break;
            case '5m':
                sleep($sleep->intervalSleep('everyDay') - 600);
                break;
            case '15m':
                sleep($sleep->intervalSleep('everyDay') + 85400);
                break;
            case '30m':
                sleep($sleep->intervalSleep('everyDay') + 86000 * 2);
                break;
            case '1h':
                sleep($sleep->intervalSleep('everyWeek'));
                break;
            case '4h':
                sleep($sleep->intervalSleep('everyWeek'));
                break;
            case '12h':
                sleep($sleep->intervalSleep('everyWeek'));
                break;
            case '1d':
                sleep($sleep->intervalSleep('everyWeek'));
                break;
            case '1w':
                sleep($sleep->intervalSleep('everyMonth'));
                break;
            case '1M':
                sleep($sleep->intervalSleep('everyMonth'));
                break;
            default:
                sleep($sleep->intervalSleep('everyDay'));
                break;
        }
    }

    protected function getDataForExchangeBySymbol($exchange, string $symbol, string $interval, string $className, array $cryptocurrency_array)
    {
        $formattedInsertData = [];
        $pause = $this->option('pause');
        try {
            $now = (int) (\Carbon\Carbon::now()->timestamp . '000');
            list($baseSymbolId, $quoteSymbolId) = explode('/', $symbol);
            $baseSymbolId = $this->getIdByTicker($baseSymbolId, $cryptocurrency_array);
            $quoteSymbolId = $this->getIdByTicker($quoteSymbolId, $cryptocurrency_array);
            if (($baseSymbolId === false) || ($quoteSymbolId === false)) {
                return false;
            }
            $since = $className::select('timestamp')
            ->where('base_id', $baseSymbolId)
            ->where('quote_id', $quoteSymbolId)
            ->orderBy('timestamp', 'DESC')
            ->first();
            if ($since) {
                $since = ((int)(strtotime($since->timestamp) . '000'));
            }else{
                if ($this->option('interval') === '1m') {
                    $since = (int) (\Carbon\Carbon::now()->subDay(14)->timestamp . '000');
                }else{
                    $since = (int) (\Carbon\Carbon::now()->subMonth(18)->timestamp . '000');
                }
            }

            $fullOhlyData = [];
            $count = 0;
            for ($startFrom = $since; $startFrom < $now; $startFrom = end($fullOhlyData)[0]) {
                try {
                    if ($this->option('exchangeName') == 'okex') {
                        $ohly = $exchange->fetchOHLCV($symbol, $interval, $startFrom);

                    }else{
                        $ohly = $exchange->fetchOHLCV($symbol, $interval, $startFrom, $exchange->rateLimit);
                    }
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    break;
                    return false;
                }
                $fullOhlyData = array_merge_recursive($fullOhlyData, $ohly);
                usleep ($exchange->rateLimit * $pause);
                if (count($ohly) < $exchange->rateLimit) {
                    break;
                }
                $this->info(count($fullOhlyData));

            }
            
            foreach ($fullOhlyData as $datum) {
                
                $formattedInsertData[] = [
                    'base_id' => $baseSymbolId,
                    'quote_id' => $quoteSymbolId,
                    'timestamp' => date('Y-m-d H:i:s', substr($datum[0], 0, 10)),
                    'open' => $datum[1],
                    'high' => $datum[2],
                    'low' => $datum[3],
                    'close' => $datum[4],
                    'volume' => $datum[5],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            unset($formattedInsertData[count($formattedInsertData) - 1]);
            $this->info('Собрано записей в колекцию ' . count($formattedInsertData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $interval);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $formattedInsertData;
    }
   
    protected function getIdByTicker(string $ticker, array $cryptocurrency_array)
    {
        $tickerId = array_search($ticker, $cryptocurrency_array);
        if ($tickerId === false) {
            
            UndefinedTicker::firstOrCreate([
                'ticker' => $ticker,
                'stock' => $this->option('exchangeName')
            ]);
            return false;
        }else{
            return $tickerId;
        }
    }
}
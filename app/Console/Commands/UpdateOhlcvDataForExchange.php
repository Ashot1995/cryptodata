<?php

namespace App\Console\Commands;

use App\CcxtOhlcv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Cryptocurrency;
use App\ExchangePairQuotes;
use App\UndefinedTicker;

class UpdateOhlcvDataForExchange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:exchange_ohlcv_full_data {--exchangeName=binance} {--pause=10} {--interval=1m} {--month=18}';

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
        $cryptocurrencies_stock_array = [];
        foreach ($cryptocurrencies_stock as $key => $value) {
            $cryptocurrencies_stock_array[] = $key;
        }
        ////////////////////////////////////////////////

        $start = microtime(true);
        
        $exchangeNameClass = $exchangeName;
        if ($exchangeName == "huobipro") {
            $exchangeNameClass = 'huobi_pro';
        }
        foreach ($cryptocurrencies_stock_array as $key_cryptocurrencies => $value_cryptocurrencies) {
            $this->info('Обрабатывается: '  .$value_cryptocurrencies. '  , '. $key_cryptocurrencies .  ' , всего валют: ' . count($cryptocurrencies_stock_array). ' , время работы скрипта: ' . round(microtime(true) - $start, 2).' сек.');
            $symbol = $value_cryptocurrencies;

            if(!isset($exchange->markets[$symbol])) {
                $this->info('У обменника ' . $exchangeName . ' нету пары ' . $symbol);
                continue;
            }
            if (!$exchange->has('fetchOHLCV')) {
                dd('Обменник не содержит информацию о OHLCV');
                continue;
            }
              // dd($exchange->timeframes);
            if (!isset($exchange->timeframes[$defaultInterval])) {
                dd('Такого временного интервала нет у обменника ' . $exchangeName);
                continue;
            }
                
            $className = 'App\OhlcvModels\ohlcv_' . $exchangeNameClass . '_' .  array_search($defaultInterval, $interval_array);
            if ($defaultInterval) {

                $formattedData = $this->getDataForExchangeBySymbol($exchange, $symbol, $defaultInterval);
                if ($formattedData == false) {
                    continue;
                }
                $formattedData = $this->findTickerId($formattedData, $cryptocurrency_array);
                if ($formattedData === false) {
                    continue;
                }

                $chuncks = array_chunk($formattedData, 5000);
                foreach ($chuncks as $insert) {
                    $className::insert($insert);
                }
                $this->info('Сохранено записей в базу ' . count($formattedData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $defaultInterval);
            } else {
                foreach ($exchange->timeframes as $interval) {

                    $formattedData = Cache::remember('ohlcv_' . $interval . '_data', 360, function () use ($exchange, $symbol, $interval) {
                        return $this->getDataForExchangeBySymbol($exchange, $symbol, $interval);
                    });

                    $formattedData = $this->findTickerId($formattedData, $cryptocurrency_array);
                    if ($formattedData === false) {
                        continue;
                    }

                    $chuncks = array_chunk($formattedData, 5000);
                    foreach ($chuncks as $insert) {
                        $className::insert($insert);
                    }
                    $this->info('Сохранено записей в базу ' . count($formattedData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $interval);
                }
            }
        }
    }

    protected function getDataForExchangeBySymbol($exchange, string $symbol, string $interval)
    {
        $formattedInsertData = [];
        $pause = $this->option('pause');
        try {
            $now = (int) (\Carbon\Carbon::now()->timestamp . '000');
            $since = (int) (\Carbon\Carbon::now()->subMonth((int)$this->option('month'))->timestamp . '000');
            list($baseSymbolId, $quoteSymbolId) = explode('/', $symbol);

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
                    // dd($this->info($e->getMessage()));
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
                ];
            }
            $this->info('Собрано записей в колекцию ' . count($formattedInsertData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $interval);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $formattedInsertData;
    }
    protected function findTickerId(array $formattedData, array $cryptocurrency_array){
        // Смена тикеров на ID
        if ((!empty($formattedData))&&(!empty($cryptocurrency_array))) {
            foreach ($formattedData as $key => $value) {
                $base_id = array_search($value['base_id'], $cryptocurrency_array);
                if ($base_id !== false) {
                    $formattedData[$key]['base_id'] = $base_id;
                }else{
                    
                    UndefinedTicker::firstOrCreate([
                        'ticker' => $value['base_id'],
                        'stock' => $this->option('exchangeName')
                    ]);
                    $this->info('Пара пропущена, тикер: '  . $value['base_id'] .  ' не найден в базе');
                    return false;
                }
                $quote_id = array_search($value['quote_id'], $cryptocurrency_array);
                if ($quote_id !== false) {
                    $formattedData[$key]['quote_id'] = $quote_id;
                }else{

                    UndefinedTicker::firstOrCreate([
                        'ticker' => $value['quote_id'],
                        'stock' => $this->option('exchangeName')    
                    ]);
                    $this->info('Пара пропущена, тикер: '  . $value['quote_id'] .  ' не найден в базе');
                    return false;
                }
            }
            return $formattedData;
        } 
    }
}
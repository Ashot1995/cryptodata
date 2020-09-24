<?php

namespace App\Console\Commands;


use App\UndefinedTicker;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\Services\SleepService;
use App\OhlcvPair;
use Illuminate\Support\Facades\Config;
use App\Services\CryptoCurrencyService;

class UpdateOhlcvPairs extends Command
{
	/**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ohlcv:pairs';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Update pairs by ohlcv tables and exchanges';

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
     // */
    const EXCHANGE_NAMES = ['Binance', 'Bitfinex', 'Bitstamp', 'Bittrex', 'Coinbase', 'Huobi Pro', 'OKEX', 'Poloniex', 'CMC'];

    const TIME_FRAMES = ['30d', '1w', '1d', '12h', '4h', '1h', '30m', '15m', '5m', '1m'];


    public function handle()
    {



        

        $cryptoService = new CryptoCurrencyService;
        $quote_id = $cryptoService->getIdCurrencyByTicker('USD');
        $start = time();
    	$nowDate = date('Y-m-d');
    	$nowTime = date('H:i:s');
    	$cryptocurrency_array = [];
        $cryptocurrencies = Cryptocurrency::select('cryptocurrency_id', 'symbol')->get();
        foreach ($cryptocurrencies as $key => $cryptocurrency) {
            $cryptocurrency_array[$cryptocurrency->cryptocurrency_id] = $cryptocurrency->symbol;
        }
    	foreach (self::EXCHANGE_NAMES as $exchange) {
            $data = [];
    		$timeFrames = ($exchange === 'CMC') ? array_slice(self::TIME_FRAMES, 0, 6) : self::TIME_FRAMES;
    		$exchangeName = ($exchange !== 'CMC') ? strtolower(str_replace(' ', '', $exchange)) : 'coinmarketcap';
            if ($exchange === 'CMC') {
                $classCMC = 'App\OhlcvModels\ohlcv_cmc_1d';
                // $cryptocurrencies_pairs_array = $classCMC::whereDate('timestamp', date('Y-m-d', strtotime('-1 month')))->select('base_id', 'quote_id')->get()->toArray();
                $cryptocurrencies_pairs_array = [];
                foreach ($cryptocurrency_array as $key => $value) {
                    $cryptocurrencies_pairs_array[] = [
                        'base_id' => $key,
                        'quote_id' => $quote_id
                    ];
                }
            }else{
                $exchanges = \ccxt\Exchange::$exchanges;
                $exchangeClass = "\\ccxt\\" . $exchangeName;
                $exchangeClass = new $exchangeClass(); 
                $cryptocurrencies_stock = $exchangeClass->load_markets();
                $cryptocurrencies_pairs_array = [];
                foreach ($cryptocurrencies_stock as $key => $value) {
                    $cryptocurrencies_pairs_array[] = $key;
                }
            }
        	foreach ($cryptocurrencies_pairs_array as $pair) {
                $exchange = strtolower(str_replace(' ', '_', $exchange));
                if ($exchange === 'cmc'){
                    $base_id = $pair['base_id'];
                    $quote_id = $pair['quote_id'];
                }else{
                    
                    list($base, $quote) = explode('/', $pair);
                    $base_id = $this->getIdByTicker($base, $cryptocurrency_array, $exchange);
                    $quote_id = $this->getIdByTicker($quote, $cryptocurrency_array, $exchange);
                    if (($base_id === false) || ($quote_id === false)) {
                        continue;
                    }
                }
        			
	            $data[] = 
                [
                    'base_id' => $base_id, 
                    'quote_id' => $quote_id, 
                    'exchange' => $exchange,
                    'created_at' => date('Y-m-d H:i:s'), 
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
        	}
            // dd($data);
            foreach ($data as $key_pair => $value_pair) {
                foreach ($timeFrames as $timeframe) {

                    $data[$key_pair][$timeframe . '_first_date'] = null;
                    $data[$key_pair][$timeframe . '_last_date'] = null;
                    $data[$key_pair][$timeframe] = false;
                }
            }

            if ($exchange === 'cmc') {
                foreach ($data as $key_pair => $value_pair) {
                    foreach ($timeFrames as $timeframe) {
                        $className = 'App\OhlcvModels\ohlcv_' . $exchange . '_' . $timeframe;
                        // dd($data);
                        $first_date = $className::where('base_id', $value_pair['base_id'])
                                     ->where('quote_id', $value_pair['quote_id'])
                                     ->select('timestamp')
                                     ->orderBy('timestamp', 'asc')
                                     ->first();
                        if (!$first_date) {
                            $data[$key_pair][$timeframe] = 0;
                            $data[$key_pair][$timeframe . '_first_date'] = null;
                            $data[$key_pair][$timeframe . '_last_date'] = null;
                            continue;
                        }
                        $last_date = $className::where('base_id', $value_pair['base_id'])
                                     ->where('quote_id', $value_pair['quote_id'])
                                     ->select('timestamp')
                                     ->orderBy('timestamp', 'desc')
                                     ->first();

                        $data[$key_pair][$timeframe] = 1;
                        $data[$key_pair][$timeframe . '_first_date'] = $first_date->timestamp;
                        $data[$key_pair][$timeframe . '_last_date'] = $last_date->timestamp;
                    }

                    dump($data[$key_pair]);
                    dump((float)time() - (float)$start);
                    $pair_db = OhlcvPair::where('exchange', $exchange)->where('base_id', $value_pair['base_id'])->where('quote_id', $value_pair['quote_id'])->first();
                    dump($value_pair['base_id']);

                    if ($pair_db) {
                        OhlcvPair::where('exchange', $exchange)->where('base_id', $value_pair['base_id'])->where('quote_id', $value_pair['quote_id'])->update($data[$key_pair]);
                    }else{
                        OhlcvPair::insert($data[$key_pair]);
                    }
                    
                }
                continue;
            }else{
                foreach ($timeFrames as $timeframe) {
                    $className = 'App\OhlcvModels\ohlcv_' . $exchange . '_' . $timeframe;
                    switch ($timeframe) {
                        case '1m':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-3 day')) . ' 01:00:00', date('Y-m-d', strtotime('-3 day')) . ' 01:03:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '5m':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-3 day')) . ' 01:00:00', date('Y-m-d', strtotime('-3 day')) . ' 01:15:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '15m':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-14 day')) . ' 01:00:00', date('Y-m-d', strtotime('-14 day')) . ' 01:30:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '30m':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-14 day')) . ' 01:00:00', date('Y-m-d', strtotime('-14 day')) . ' 02:00:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '1h':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-1 month')) . ' 01:00:00', date('Y-m-d', strtotime('-1 month')) . ' 05:05:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '4h':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-1 month')) . ' 00:00:00', date('Y-m-d', strtotime('-1 month')) . ' 22:05:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '12h':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-30 day')) . ' 00:00:00', date('Y-m-d', strtotime('-25 day')) . ' 00:00:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '1d':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-30 day')) . ' 00:00:00', date('Y-m-d', strtotime('-25 day')) . ' 00:00:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '1w':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-3 month')) . ' 00:00:00', date('Y-m-d', strtotime('-2 month')) . ' 00:00:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                        case '30d':
                            $db_data = $className::whereBetween('timestamp', [date('Y-m-d', strtotime('-7 month')) . ' 00:00:00', date('Y-m-d', strtotime('-2 month')) . ' 00:00:00'])
                                         ->select('base_id','quote_id')
                                         ->get()
                                         ->toArray();
                            break;
                    }
                    dump(count($db_data));
                    dump($exchange);
                    foreach ($data as $key_data => $value_data) {
                        foreach ($db_data as $key_db => $value_db) {
                            
                            if (($value_data['base_id'] == $value_db['base_id']) && ($value_data['quote_id'] == $value_db['quote_id'])) {
                                $data[$key_data][$timeframe] = true;
                            }

                            
                        }
                    }
                }
            }
            
            dump((float)time() - (float)$start);
            OhlcvPair::where('exchange', $exchange)->delete();
            OhlcvPair::insert($data);
    	}
        $this->info('FINISH');
        $sleep = new SleepService;
        sleep($sleep->intervalSleepEveryDayByTime('01:00'));
	}
	protected function getIdByTicker(string $ticker, array $cryptocurrency_array, string $exchange)
    {
        $tickerId = array_search($ticker, $cryptocurrency_array);
        if ($tickerId === false) {
            
            UndefinedTicker::firstOrCreate([
                'ticker' => $ticker,
                'stock' => $exchange
            ]);
            return false;
        }else{
            return $tickerId;
        }
    }

}
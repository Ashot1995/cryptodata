<?php

namespace App\Console\Commands;

use App\Services\TnIndexService;
use App\TnIndex;
use App\TopCryptocurrency;
use App\Cryptocurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Services\SleepService;
use DateTime;
use App\TnIndexCoin;
use App\CustomIndex;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\Services\CryptoCurrencyService;

class TnIndexesAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tnindexes:all {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */


    protected $description = 'Save to tn_indexes';

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
        
        $days = $this->option('days');
        ///////////////////////////////////////////////////////
        if ((int)$days > 0) {
            CustomIndex::whereDate('timestamp', '>', date('Y-m-d' ,strtotime('-'.$days.' day')))->delete();
        }
        ///////////////////////////////////////////////////////

        $cryptoService = new CryptoCurrencyService;
        $quote_id = $cryptoService->getIdCurrencyByTicker('USD');
        $dataDb = TnIndexCoin::with('Cryptocurrency')->get();
        $dataDb = $dataDb->groupBy('index_name');
        $indexesArray = [];
        if (count($dataDb) > 0) {
            foreach ($dataDb as $indexName => $indexCrypts) {
                $indexCryptsArray = [];
                foreach ($indexCrypts as $symbol) {
                    $indexCryptsArray[] = $symbol->cryptocurrency->cryptocurrency_id;
                }
                $indexesArray[$indexName] = $indexCryptsArray;
            }
        }
        foreach ($indexesArray as $indexName => $cryptoArray) {
            $last_date_db = CustomIndex::where('index_name', $indexName)->orderBy('timestamp', 'desc')->first();
            $sleep = new SleepService;
            $sleepTime = $sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.tn_indexes'));
            $lastUpdateCryptsDb = TnIndexCoin::where('index_name', $indexName)->orderBy('created_at', 'desc')->select('created_at as date')->first();
            if ($lastUpdateCryptsDb) {
                $lastUpdateCrypts = (int)strtotime($lastUpdateCryptsDb->date);
            }
            if (!$last_date_db || ((int)strtotime($last_date_db->created_at) < $lastUpdateCrypts) || ($sleepTime < 300)) {
                $last_tn = 100;
                if ($last_date_db) {
                    $last_date = $last_date_db->timestamp;
                    $last_tn = $last_date_db->value;
                }else{
                    $last_date_db = ohlcv_cmc_1d::whereIn('base_id', $cryptoArray)->where('quote_id', $quote_id)->orderBy('timestamp', 'asc')->first();
                    if ($last_date_db) {
                        $last_date = $last_date_db->timestamp;
                    }else{
                        continue;
                    }
                }
                $data_db = ohlcv_cmc_1d::whereIn('base_id', $cryptoArray)->where('quote_id', $quote_id)
                    ->whereDate('timestamp', '>=', date('Y-m-d', strtotime($last_date)))
                    ->select('close', 'timestamp', 'time_close', 'base_id', 'market_cap')
                    ->orderBy('timestamp', 'asc')
                    ->get();
                if ($data_db->count() > 0) {
                    $first_date = $data_db->groupBy('time_close')->last()[0]->timestamp;
                    $data_db =$data_db->groupBy('timestamp')->toArray();
                    $last_date_time = strtotime($last_date);
                    $first_date_time = strtotime($first_date);

                    $daysCount = (int)floor(((int)$first_date_time - (int)$last_date_time)/3600/24);
                    $count_crypts = count($cryptoArray);
                    $tn_array = [];
                    $array_keys = array_keys($data_db);
                    foreach ($data_db as $key => $value) {
                        $yesterday_index = array_search($key ,$array_keys ) - 1;
                        if ($yesterday_index >= 0) {
                            $yesterday_prices = $data_db[$array_keys[$yesterday_index]];
                            $min_data = ($count_crypts > 10) ? 10 : round($count_crypts / 2);
                            if ((count($value) >= $min_data) && (count($yesterday_prices) == count($value))) {
                                $summ_capitalization = 0;
                                foreach ($value as $coin) {
                                    $summ_capitalization += (float)$coin['market_cap'];
                                }
                                $date = date('Y-m-d 00:00:00', strtotime($key)+1000);
                                $summ_change = 0;
                                foreach ($value as $today) {
                                    foreach ($yesterday_prices as $yesterday) {
                                        if ($today['base_id'] == $yesterday['base_id']) {
                                            $weight = (float)$today['market_cap'] / $summ_capitalization;
                                            $change = (((float)$today['close'] - (float)$yesterday['close'])/(float)$yesterday['close']) * $weight;
                                            $summ_change += $change;
                                            
                                        }
                                    }
                                }

                                dump('last_tn :' . $last_tn);
                                dump(' 1 + $summ_change  : ' .  (1 + $summ_change) );

                                // $tn = $last_tn * ( 1 + $summ_change / $count_crypts );
                                $tn = $last_tn * ( 1 + $summ_change);

                                $last_tn = $tn;

                                dump('tn :' . $tn);
                                $tn_array[] = [
                                    'index_name' => $indexName,
                                    'value' => $tn,
                                    'timestamp' => $date,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                            }else{
                                $have_coins_array = [];
                                foreach ($value as $key1 => $value1) {
                                    $have_coins_array[] = $value1['base_id'];
                                }
                                $undefined_coins = Cryptocurrency::whereIn('cryptocurrency_id',$cryptoArray)->whereNotIn('cryptocurrency_id', $have_coins_array)->pluck('symbol')->toArray();
                                // dump($indexName);
                                // dump($key);
                                // dump($undefined_coins);

                            }
                        }
                    }
                    CustomIndex::insert($tn_array);
                }
            }
        }
        sleep(30);
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\GlobalCoefficient;
use App\OhlcvModels\ohlcv_cmc_1d;
use Illuminate\Support\Facades\Config;
use App\Services\SleepService;
use App\Services\CryptoCurrencyService;

class DailyAnnualizedReturn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'annualized:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save Annualized Return for month and year every day';

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

        $this->info('start succesfull update annualized:dayli ' . date('H:i:s d-m-Y'));
        $cryptoService = new CryptoCurrencyService;
        $quote_id = $cryptoService->getIdCurrencyByTicker('USD');
        $intervals = ['week', 'month', 'year'];
        $cryptocurrency_array = Cryptocurrency::pluck('cryptocurrency_id')->toArray();
        foreach ($cryptocurrency_array as $key => $base_id) {
            foreach ($intervals as $interval) {
                $ann_array = [];
                $last_date_db = GlobalCoefficient::where('base_id', $base_id)
                    ->where('quote_id', $quote_id)
                    ->where('interval', $interval)
                    ->orderBy('timestamp', 'desc')
                    ->first();


                if (!empty($last_date_db)) {
                    $last_date = date('Y-m-d', strtotime($last_date_db->timestamp));
                    $data_db = ohlcv_cmc_1d::where('base_id', $base_id)
                        ->where('quote_id', $quote_id)
                        ->whereDate('timestamp', '>=', date('Y-m-d', strtotime($last_date . ' -1 '. $interval)))
                        ->select('timestamp', 'close')
                        ->orderBy('timestamp', 'asc')
                        ->get();
                    if (count($data_db) != 0){
                        $data_db_array = $data_db->toArray();
                        
                        $last_date = date('Y-m-d', strtotime($data_db_array[0]['timestamp'] . '+1' . $interval));
                        $end_date = date('Y-m-d', strtotime($data_db_array[count($data_db_array) - 1]['timestamp']));
                    }else{
                        continue;
                    }
                }else{
                    $data_db = ohlcv_cmc_1d::where('base_id', $base_id)
                        ->where('quote_id', $quote_id)
                        ->select('timestamp', 'close')
                        ->orderBy('timestamp', 'asc')
                        ->get();
                    if (count($data_db) != 0){
                        $data_db_array = $data_db->toArray();
                        
                        $last_date = date('Y-m-d', strtotime($data_db_array[0]['timestamp'] . '+1' . $interval));
                        $end_date = date('Y-m-d', strtotime($data_db_array[count($data_db_array) - 1]['timestamp']));
                    }else{
                        continue;
                    }
                }
                $daysCount = (int)floor(((int)strtotime($end_date) - (int)strtotime($last_date))/3600/24);
                if ($daysCount > 0) {
                    for ($day=1; $day <= $daysCount; $day++) { 
                        $date = strtotime($last_date . '+' . $day . ' day');
                        $interval_date = date('Y-m-d', strtotime($last_date . '+' . ($day - 1) . ' day'));
                        $interval_date = strtotime($interval_date . ' -1 ' . $interval);
                      
                        
                        $close_array = [];
                        foreach ($data_db_array as $key => $value) {
                            if ( (strtotime($value['timestamp']) >= $interval_date) && (strtotime($value['timestamp']) < $date) ) {
                                $close_array[] = $value;
                            }
                        }
                        if (count($close_array) > 0) {
                            $mnoj = 1;
                            foreach ($close_array as $key_price => $close_price) {
                                if ($key_price != 0) {
                                    $mnoj *= (float)$close_price['close'] / (float)$close_array[$key_price - 1]['close'];
                                }
                            }
                            $ann_return = pow($mnoj, 1 / count($close_array) - 1) - 1;
                            $ann_array[] = [
                                'base_id' => $base_id,
                                'quote_id' => $quote_id,
                                'timestamp' => date('Y-m-d 00:00:00', $date),
                                'interval' => $interval,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'annualized_return' => $ann_return,
                            ];
                        }

                    }
                }
                    
                GlobalCoefficient::insert($ann_array);
            }
        }

        $sleep = new SleepService;
        $this->info('stop succesfull update annualized:dayli ' . date('H:i:s d-m-Y'));
        sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.annualized_dayli')));
    }

}

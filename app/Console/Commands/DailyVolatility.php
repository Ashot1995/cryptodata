<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\Coefficient;
use App\OhlcvModels\ohlcv_cmc_1d;
use Illuminate\Support\Facades\Config;
use App\GlobalCoefficient;
use App\Services\SleepService;
use App\Services\CryptoCurrencyService;

class DailyVolatility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'volatility:daily {--symbol=} {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save volatility for week, month and year every day';

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

        $this->info('start succesfull update volatility:dayli ' . date('H:i:s d-m-Y'));
        $symbol = $this->option('symbol', false);
        $days = $this->option('days');
        $cryptoService = new CryptoCurrencyService;
        if ($symbol) {
            $cryptocurrency_array =[];
            $cryptocurrency_array[] = $cryptoService->getIdCurrencyByTicker($symbol);
        }else{

            $cryptocurrency_array = Cryptocurrency::pluck('cryptocurrency_id')->toArray();
        }

        $quote_id = $cryptoService->getIdCurrencyByTicker('USD');
        $intervals = ['week', 'month', 'year'];
        foreach ($cryptocurrency_array as $key_currency => $base_id) {
            dump('Currency_id: '.  $base_id);
            dump('quote_id: '.  $quote_id);

            ///////////////////////////////////////////////////////
            if ((int)$days > 0) {
                Coefficient::where('cryptocurrency_id', $base_id)
                        ->where('convert', (string)$quote_id)
                        ->whereDate('c_date', '>', date('Y-m-d' ,strtotime('-'.$days.' day')))
                        ->delete();
            }
            ///////////////////////////////////////////////////////



            foreach ($intervals as $interval) {
                        dump('Currency_id: ');
                        dump($key_currency);
                        dump('interval: ');
                        dump($interval);
                $volatility_array = [];
                $last_date_db = Coefficient::where('cryptocurrency_id', $base_id)
                    ->where('convert', $quote_id)
                    ->where('interval', $interval)
                    ->orderBy('c_date', 'desc')
                    ->first();

                if (!empty($last_date_db)) {
                    $last_date = date('Y-m-d', strtotime($last_date_db->c_date));
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
                    $annualized_return_db = GlobalCoefficient::where('base_id', $base_id)
                            ->where('quote_id', $quote_id)
                            ->whereDate('timestamp', '>=', $last_date)
                            ->whereDate('timestamp', '<=', $end_date)
                            ->where('interval', $interval)
                            ->get();
                    if ($annualized_return_db->count() == 0) {
                        continue;
                    }
                    for ($day=1; $day <= $daysCount; $day++) { 
                        dump('Currency_id: ');
                        dump($key_currency);
                        dump('interval: ');
                        dump($interval);
                        $date = strtotime($last_date . '+' . $day . ' day');
                        $interval_date = date('Y-m-d', strtotime($last_date . '+' . ($day - 1) . ' day'));
                        $interval_date = strtotime($interval_date . ' -1 ' . $interval);
                      
                        
                        $close_array = [];
                        foreach ($data_db_array as $key => $value) {
                            if ( (strtotime($value['timestamp']) >= $interval_date) && (strtotime($value['timestamp']) < $date) ) {
                                $close_array[] = $value;
                            }
                        }
                        dump('DATE: ');
                        dump($end_date);
                        dump('Close price array');
                        dump($close_array);
                        if (count($close_array) > 3) {

                        	$return_array = [];
                        	$avg = 0;
                        	foreach ($close_array as $key => $value) {
                        		if ($key > 0) {
                        			$return = ((float)$value['close'] / (float)$close_array[$key - 1]['close']) - 1;
                        			$return_array[] = $return;
                        			$avg += $return;
                        		}
                        	}

                            $sqr_return_for_sortino_array = [];
                            $sqr_return_for_sortino_summ = 0;
                            foreach ($return_array as $key => $value) {
                                if ((float)$value < 0) {
                                    $sqr_return = pow((float)$value, 2);
                                    $sqr_return_for_sortino_array[] = $sqr_return;
                                    $sqr_return_for_sortino_summ += $sqr_return;
                                }
                            }
                            $dd = $sqr_return_for_sortino_summ / count($return_array);
                            $dd_annualized = $dd * sqrt(count($return_array));


                            dump('Return array');
                            dump($return_array);
                        	$avg = $avg / count($return_array);
                            dump('avg');
                            dump($avg);
                        	$m_return_array = [];
                        	foreach ($return_array as $key => $value) {
                        		$m_return_array[] = $avg - $value;
                        	}
                            dump('m_return_array');
                            dump($m_return_array);
                        	$sqr_m_return_array = [];
                        	$sum_sqr_m = 0;
                        	foreach ($m_return_array as $key => $value) {
                        		$sqr = pow($value, 2);
                        		$sqr_m_return_array[] = $sqr;
                        		$sum_sqr_m += $sqr;
                        	}
                            dump('sqr_m_return_array');
                            dump($sqr_m_return_array);
                            dump('sum_sqr_m');
                            dump($sum_sqr_m);

                            $sqrt_days = sqrt(count($return_array));
                        	$volatility = $sum_sqr_m / count($return_array) * $sqrt_days * 100;

                            dump('volatility');
                            dump($volatility);
                            $sharpe = null;
                            $sortino = null;
                            foreach ($annualized_return_db as $key => $annualized_return) {
                                if (date('Y-m-d', strtotime($annualized_return->timestamp)) == date('Y-m-d', $date)) {
                                    $sharpe = $volatility ? (float)$annualized_return->annualized_return / $volatility : 0;
                                    $sortino = $dd_annualized ? (float)$annualized_return->annualized_return / $dd_annualized : 0;
                                    dump('annualized_return');
                                    dump($annualized_return->annualized_return);
                                    break;
                                }
                            }

                            dump('sharpe');
                            dump($sharpe);


                            dump('sqr_return_for_sortino_array');
                            dump($sqr_return_for_sortino_array);
                            dump('sqr_return_for_sortino_summ');
                            dump($sqr_return_for_sortino_summ);
                            dump('dd');
                            dump($dd);
                            dump('dd_annualized');
                            dump($dd_annualized);
                            dump('sortino');
                            dump($sortino);


                            $volatility_array = [

                                'cryptocurrency_id' => $base_id,
                                'convert'           => $quote_id,
                                'c_date'            => date('Y-m-d 00:00:00', $date),
                                'interval'          => $interval,
                                'volatility'        => $volatility,
                                'sharpe'            => $sharpe,
                                'sortino'            => $sortino,
                            ];
                			Coefficient::updateOrCreate([
                					'cryptocurrency_id' => $base_id,
	                                'convert' => $quote_id,
	                                'c_date' => date('Y-m-d 00:00:00', $date),
	                                'interval' => $interval,
                				],
                				$volatility_array);
                        }

                    }

                }
                    
            }
        }

        $sleep = new SleepService;
        $this->info('stop succesfull update volatility:dayli ' . date('H:i:s d-m-Y'));
        sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.volatility_dayli')));
    }
    


}

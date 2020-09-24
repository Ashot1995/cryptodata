<?php

namespace App\Console\Commands;


use App\Http\DateFormat\DateFormat;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\Services\CorrelationService;
use App\Correlation;
use App\Services\SleepService;
use App\CorrelationCoin;


class CorrelationByInterval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:correlation {--interval=} {--first=} {--second=} {--date=}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Correlation data for period';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {   
        $lastUpdateTopCryptsDb = CorrelationCoin::orderBy('created_at', 'desc')->select('created_at as date')->first();
        if ($lastUpdateTopCryptsDb) {
            $lastUpdateTopCrypts = (int)strtotime($lastUpdateTopCryptsDb->date);
        }
        $lastUpdateCorrelationDb = Correlation::orderBy('created_at', 'desc')->select('created_at as date')->first();
        if ($lastUpdateCorrelationDb) {
            $lastUpdateCorrelation = (int)strtotime($lastUpdateCorrelationDb->date);
        }
        $sleep = new SleepService;
        $sleepTime = $sleep->intervalSleep('everyDay');
        if (($lastUpdateCorrelation < $lastUpdateTopCrypts) || ($sleepTime < 300)) {
            if ($this->option('first') && $this->option('second')) {
                $topCrypts = [$this->option('first'), $this->option('second')];
            }else{
                $topCrypts = CorrelationCoin::pluck('cryptocurrency_id')->toArray();
            }
            $intervals_array = CorrelationService::INTERVALS_CORRELATION;
            foreach ($intervals_array as $interval) {
                
                if ($this->option('interval') != '') {
                    $interval = $this->option('interval');
                }

                $start = time();
                $dateTo = !empty($this->option('date')) ? $this->option('date') : date(DateFormat::DATE_FORMAT,
                    strtotime("-1 day"));

                $dateFrom = $this->dateFromByInterval($interval,$dateTo);
                $daysCount = (int)floor(((int)strtotime($dateTo) - (int)strtotime($dateFrom))/3600/24);

                $usd = Cryptocurrency::where('symbol', 'USD')->pluck('cryptocurrency_id')->toArray();
                if ($usd) {
                    $usdId = $usd[0];
                }else{
                    $usdId = '2068';
                }
                $percente_change = [];
                $correlationService = new CorrelationService;
                $close_array = [];
                $best_dates = [];
                foreach ($topCrypts as $base_id) {
                    $data_db = ohlcv_cmc_1d::where('base_id', $base_id)
                    ->where('quote_id', $usdId)
                    ->whereDate('timestamp', '>=', $dateFrom)
                    ->whereDate('timestamp', '<=', $dateTo)
                    ->orderBy('timestamp', 'desc')
                    ->select('timestamp', 'close')
                    ->get();
                    $data_db_array = [];

                    if (count($data_db) > 0) {
                        $best_dates[$base_id] = $data_db[0]->timestamp;
                    }
                    $best_date = (int)time();
                    foreach ($best_dates as $key => $value) {
                        if ((int)strtotime($value) < $best_date) {
                            $best_date = strtotime($value);
                        }
                    }
                    $dateTo = date('Y-m-d', $best_date);
                    foreach ($data_db as $key => $value) {
                        if ((int)strtotime($value['timestamp']) <= (int)$best_date) {
                            $data_db_array[] = $value['close'];
                        }
                    }
                    $close_array[$base_id] = $data_db_array;
                    // dump($data_db->toArray());
                }
                if (count($best_dates) !== count($topCrypts)) {
                    continue;
                }
                $correlation_array = [];
                foreach ($close_array as $key_first => $value_first) {
                    foreach ($close_array as $key_second => $value_second) {
                        if ($key_first <= $key_second) {
                            if ((count($value_second) >= $daysCount / 2) && (count($value_first) >= $daysCount / 2)) {
                                
                                if ($key_first == $key_second) {
                                    $correlation = 1;
                                }else{
                                    $correlation = $this->getCorrelation($value_first, $value_second);  
                                }
                                $correlation_array[] = [
                                    'base_id' => $key_first,
                                    'quote_id' => $key_second,
                                    'correlation' => $correlation,
                                    'interval' => $interval,
                                    'timestamp' => date(DateFormat::DATE_TIME_FORMAT),
                                    'created_at' => date(DateFormat::DATE_TIME_FORMAT),
                                    'updated_at' => date(DateFormat::DATE_TIME_FORMAT),
                                ];
                            }
                        }
                            
                    }
                }
                Correlation::where('interval', $interval)->delete();
                Correlation::insert($correlation_array);

                if ($this->option('interval') != '') {
                    break;
                }
            }

        }
         dump('FINISH');
        sleep(60);
    }
    public function dateFromByInterval($interval,$dateTo)
    {

        switch ($interval) {
            case 'week':
                return date('Y-m-d', strtotime($dateTo. "-1 week"));
                break;
            case 'month':
                return date('Y-m-d', strtotime($dateTo. "-1 month"));
                break;
            case 'quarter':
                return date('Y-m-d', strtotime($dateTo. "-3 month"));
                break;
            case 'year':
                return date('Y-m-d', strtotime($dateTo. "-1 year"));
                break;
            case 'ytd':
                return date('Y', strtotime($dateTo)) . '-01-01';
                break;

            default:
                return date('Y-m-d', strtotime($dateTo. "-1 week"));
                break;
        }
    }
    public function getCorrelation($value_first, $value_second)
    {
        
        

        $max_count_arrays = (count($value_first) < count($value_second)) ? count($value_first) : count($value_second);
        $first_return_array = [];
        $first_summ_return = 0;
        for ($i=0; $i < $max_count_arrays; $i++) { 
            if ($i > 0) {
                $return = ((float)$value_first[$i] / (float)$value_first[$i - 1]) - 1;
                
                $first_return_array[] = $return;
                $first_summ_return += $return;
            }
        }

        $second_return_array = [];
        $second_summ_return = 0;
        for ($i=0; $i < $max_count_arrays; $i++) { 
            if ($i > 0) {
                $return = ((float)$value_second[$i] / (float)$value_second[$i - 1]) - 1;
                $second_return_array[] = $return;
                $second_summ_return += $return;
            }
        }

        $max_count_arrays = (count($first_return_array) < count($second_return_array)) ? count($first_return_array) : count($second_return_array);

        $mnoj_f_s_array = [];
        $summ_mnoj_f_s = 0;
        for ($i=0; $i < $max_count_arrays; $i++) { 
            $mnoj = $first_return_array[$i] * $second_return_array[$i];
            $mnoj_f_s_array[] = $mnoj;
            $summ_mnoj_f_s += $mnoj;
        }


        $first_sqr_array = [];
        $summ_first_sqr = 0;
        for ($i=0; $i < $max_count_arrays; $i++) { 
            $sqr = pow($first_return_array[$i], 2);
            $first_sqr_array[] = $sqr;
            $summ_first_sqr += $sqr;
        }

        $second_sqr_array = [];
        $summ_second_sqr = 0;
        for ($i=0; $i < $max_count_arrays; $i++) { 
            $sqr = pow($second_return_array[$i], 2);
            $second_sqr_array[] = $sqr;
            $summ_second_sqr += $sqr;
        }

        $chisl = $max_count_arrays * $summ_mnoj_f_s - ($first_summ_return * $second_summ_return);
        $znam1 = $max_count_arrays * $summ_first_sqr - pow($first_summ_return, 2);
        $znam2 = $max_count_arrays * $summ_second_sqr - pow($second_summ_return, 2);
        $mnoj_znam1_znam2 = $znam1 * $znam2;
        $sqrt = sqrt($mnoj_znam1_znam2);
        $correlation = $chisl / $sqrt;


        dump("value_first:  ");
        dump( implode("\n", $value_first));
        dump("value_second:  ");
        dump( implode("\n", $value_second));
        dump("first_return_array:  ");
        dump( implode("\n", $first_return_array));
        dump("second_return_array:  ");
        dump( implode("\n", $second_return_array));
        dump("max_count_arrays:  " .  $max_count_arrays);
        dump("first_summ_return:  " . $first_summ_return);
        dump("second_summ_return:  " . $second_summ_return);
        dump("summ_mnoj_f_s:  " . $summ_mnoj_f_s);
        dump("summ_first_sqr:  " . $summ_first_sqr);
        dump("summ_second_sqr:  " . $summ_second_sqr);
        dump("chisl:  " . $chisl);
        dump("znam1:  " . $znam1);
        dump("znam2:  " . $znam2);
        dump("mnoj_znam1_znam2:  " . $mnoj_znam1_znam2);
        dump("sqrt:  " . $sqrt);
        dump("correlation:  " . $correlation);


        return $correlation;
    }
}













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

class CorrelationYearly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correlation:yearly';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Correlation data yearly';

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

        $topCrypts = TopCryptocurrency::limit(30)->pluck('cryptocurrency_id')->toArray();
        $usd = Cryptocurrency::where('symbol', 'USD')->pluck('cryptocurrency_id')->toArray();
        if ($usd) {
            $usdId = $usd[0];
        }
        $percente_change = [];
        $correlationService = new CorrelationService;
        foreach ($topCrypts as $key => $value) {
            $month_last = $correlationService->getDiffCorrByYear($value, $usdId, '-1 year');
            $month_before_last = $correlationService->getDiffCorrByYear($value, $usdId, '-2 year');
            if ($month_before_last != 0) {
                $percente_change[$value] = (($month_last - $month_before_last) / $month_before_last) * 100;
            }else{
                $percente_change[$value] = $month_last;
            }
        }       
        $correlation_array = [];
        foreach ($percente_change as $k1 => $value1) {
            foreach ($percente_change as $k2 => $value2) {
                if ($k1 !== $k2) {
                    $correlation_array[] =  
                        [
                            'base_id'   => $k1,
                            'quote_id'  => $k2,
                            'diff'      => ($value2 != 0)  ? $value1 / $value2 : 0
                        ];
                }else{
                    $correlation_array[] =  
                        [
                            'base_id'   => $k1,
                            'quote_id'  => $k2,
                            'diff'      => 1
                        ];
                }
            }
        }
        $column_percent = array_column($correlation_array, 'diff');
        array_multisort($column_percent, SORT_ASC,  $correlation_array);
        $min_diff = abs($correlation_array[0]['diff']);
        $max_diff = $correlation_array[count($correlation_array) - 1]['diff'];
        foreach ($correlation_array as $key => $value) {
            if ($value['diff'] > 0) {
                if ($value['base_id'] == $value['quote_id']) {
                    $correlation_array[$key]['correlation'] = 1; 
                }else{
                    $correlation_array[$key]['correlation'] = ($max_diff != 0) ? $value['diff'] / $max_diff : 0; 
                }
            }else{
                $correlation_array[$key]['correlation'] = ($min_diff != 0) ? $value['diff'] / $min_diff : 0; 
            }
            $correlation_array[$key]['timestamp'] = date('Y', strtotime('-1 year')) . '-01-01 00:00:00';
            $correlation_array[$key]['interval'] = 'yearly';
            unset($correlation_array[$key]['diff']);

        }
        foreach ($correlation_array as $key => $value) {
            Correlation::firstOrCreate(['base_id' => $value['base_id'],'quote_id' => $value['quote_id'],'timestamp' => $value['timestamp'], 'interval' => $value['interval']], $value);
        }
        $sleep = new SleepService;
        sleep($sleep->intervalSleep('everyYear'));
    }

}













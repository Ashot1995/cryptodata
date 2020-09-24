<?php

namespace App\Services;

use App\Exceptions\EmptyEntityListException;
use App\Http\DateFormat\DateFormat;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\Correlation;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\Http\StatusCode\HTTPStatusCode;
use Carbon\Carbon;
use App\CorrelationCoin;

/**
 * 
 */
class CorrelationService
{

    const INTERVALS_CORRELATION = ['week', 'month', 'quarter', 'ytd', 'year'];
    const INTERVALS_CORRELATION_FILTER = ['markets_filters_graphs_weekly', 'markets_filters_graphs_monthly', 'markets_filters_graphs_quarter', 'markets_filters_graphs_ytd', 'markets_filters_graphs_yearly'];

	public function getDiffCorrByMonth($base_id, $quote_id, $month)
	{
		$f_day_last_month = ohlcv_cmc_1d::where('base_id', $base_id)
                    ->select('close')
                    ->where('quote_id', $quote_id)
                    ->whereMonth('timestamp', date('m',strtotime($month)))
                    ->whereYear('timestamp', date('Y',strtotime($month)))
                    ->orderBy('timestamp', 'asc')
                    ->first();
            $X1_month_last = ($f_day_last_month) ? $f_day_last_month->close : 0;
            $l_day_last_month = ohlcv_cmc_1d::where('base_id', $base_id)
                    ->select('close')
                    ->where('quote_id', $quote_id)
                    ->whereMonth('timestamp', date('m',strtotime($month)))
                    ->whereYear('timestamp', date('Y',strtotime($month)))
                    ->orderBy('timestamp', 'desc')
                    ->first();
            $X2_month_last = ($l_day_last_month) ? $l_day_last_month->close : 0;
            return (float)$X2_month_last - (float)$X1_month_last;
	}
    public function getDiffCorrByYear($base_id, $quote_id, $year)
    {
        $f_day_last_year = ohlcv_cmc_1d::where('base_id', $base_id)
                    ->select('close')
                    ->where('quote_id', $quote_id)
                    ->whereMonth('timestamp', '01')
                    ->whereYear('timestamp', date('Y',strtotime($year)))
                    ->orderBy('timestamp', 'asc')
                    ->first();
            $X1_year_last = ($f_day_last_year) ? $f_day_last_year->close : 0;
            $l_day_last_year = ohlcv_cmc_1d::where('base_id', $base_id)
                    ->select('close')
                    ->where('quote_id', $quote_id)
                    ->whereMonth('timestamp', '12')
                    ->whereYear('timestamp', date('Y',strtotime($year)))
                    ->orderBy('timestamp', 'desc')
                    ->first();
            $X2_year_last = ($l_day_last_year) ? $l_day_last_year->close : 0;
            return (float)$X2_year_last - (float)$X1_year_last;
    }
    public function getDataByInterval($interval, $data)
    {
        
        $correlation = Correlation::leftJoin('cryptocurrencies as c1', 'c1.cryptocurrency_id', '=', 'correlations.base_id')
            ->leftJoin('cryptocurrencies as c2', 'c2.cryptocurrency_id', '=', 'correlations.quote_id')
            ->where('interval', $interval)
            ->select(
                        'c1.symbol as currency1_name', 
                        'c2.symbol as currency2_name', 
                        'timestamp as time',
                        'correlation as value'
                    )
            ->orderBy('c1.cryptocurrency_id', 'asc')
            ->get()
            ->toArray();
        if (!$correlation) {
            throw new EmptyEntityListException("Database is empty for this interval");
        }
        $currencies = [];
        foreach ($correlation as $key => $value) {
            if (!in_array($value['currency1_name'], $currencies)) {
                $currencies[] = $value['currency1_name'];
            }
        }
        $currency_array = [];
        foreach ($currencies as $key => $value) {
            $currency_array[] =[
                    'name' => $value,
                    'id'   => $key + 1
                ];
        }
        foreach ($correlation as $key => $value) {
            $correlation[$key]['currency1_id'] = array_search($value['currency1_name'], $currencies) + 1;
            $correlation[$key]['currency2_id'] = array_search($value['currency2_name'], $currencies) + 1;
            $correlation[$key]['value'] = round((float)$value['value'],2);
        }
        // dd($currency_array);
        $column_value = array_column($correlation, 'value');
        array_multisort($column_value, SORT_ASC, $correlation);
        $data['filters_front'] =$currency_array;
        $data['filters']['currency_list'] = $currencies;
        $data['data'] = $correlation;
        return $data;
    }
    protected function getStartDate(string $startAt): string
    {
        $today = Carbon::now()->subMonth()->format('Y-m-d');
        $startAt = strtotime($startAt) ? $startAt : $today;
        return $startAt;
    }

    protected function getEndDate($endAt): string
    {
        $today = Carbon::now()->startOfDay()->format('Y-m-d');
        $endAt = strtotime($endAt) ? $endAt : $today;
        return $endAt;
    }
    public function getCorrelationIntervals()
    {
        $return_array = [];
        foreach (self::INTERVALS_CORRELATION as $key => $value) {
            $return_array[] = [
                'key' => self::INTERVALS_CORRELATION_FILTER[$key],
                'value' => $value
            ];
        }
        return $return_array;
    }
}
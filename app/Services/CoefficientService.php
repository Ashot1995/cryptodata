<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/11/19
 * Time: 7:18 PM
 */

namespace App\Services;


use App\Coefficient;
use App\GlobalCoefficient;
use App\Cryptocurrency;


class CoefficientService
{
 
    const CHART_TYPE_RETURN = 'return';
    const DAILY_INTERVAL = 'markets_filters_graphs_daily';
    const WEEKLY_INTERVAL = 'markets_filters_graphs_weekly';
    const MONTHLY_INTERVAL = 'markets_filters_graphs_monthly';
    const YEARLY_INTERVAL = 'markets_filters_graphs_yearly';

    const DAILY_INTERVAL_VALUE = 'daily';
    const WEEKLY_INTERVAL_VALUE = 'weekly';
    const MONTHLY_INTERVAL_VALUE = 'monthly';


    const CHART_TYPES = [
        'return' => 'annualized_return'
    ];

    public function getCoefficentsAndSave($cryptocurrency_id, $dateTo, $interval, $alpha = 0, $beta = 0, $sharpe = 0, $volatility = 0, $sortino = 0)
    {
        $coefficient = Coefficient::where('cryptocurrency_id', $cryptocurrency_id)
            ->where('interval', $interval)
            ->whereDate('c_date', $dateTo)
            ->where('convert', 'USD')
            ->first();

        if (!$coefficient) {
            $coefficient = new Coefficient();
        }

        $coefficient->cryptocurrency_id = $cryptocurrency_id;
        $coefficient->convert = 'USD';
        $coefficient->c_date = $dateTo;
        $coefficient->interval = $interval;

        if ($alpha) {
            $coefficient->alpha = $alpha;
        }

        if ($beta) {
            $coefficient->beta = $beta;
        }

        if ($sharpe) {
            $coefficient->sharpe = $sharpe;
        }

        if ($volatility) {
            $coefficient->volatility = $volatility;
        }

        if ($sortino) {
            $coefficient->sortino = $sortino;
        }

        $coefficient->save();
    }

    public function saveCoefficient($ar, $dateTo, $interval)
    {
        $coefficient = GlobalCoefficient::whereDate('timestamp', $dateTo)->first();

        if (!$coefficient) {
            $coefficient = new GlobalCoefficient();
        }

        $coefficient->annualized_return = $ar;
        $coefficient->timestamp = $dateTo;
        $coefficient->interval = $interval;
        $coefficient->save();
    }

    public function getChartDataByType(string $chartType, string $periodStartDate, string $periodEndDate, string $interval)
    {
        $chartType = self::CHART_TYPES[$chartType];
        $coefficients = GlobalCoefficient::whereBetween('timestamp', [$periodStartDate, $periodEndDate])
            ->where('interval', $interval)->get();
        $data = [];

        foreach ($coefficients as $coefficient) {
            $data[] = [
                'time' => $coefficient->timestamp,
                'value' => $coefficient[$chartType]
            ];
        }

        return $data;
    }
    public function firstDateChartDataByType(string $interval)
    {
        $last = GlobalCoefficient::where('interval', $interval)
            ->select('timestamp')
            ->orderBy('timestamp', 'asc')
            ->first();
        return ($last) ? date('Y-m-d',strtotime($last->timestamp)) : '';
    }
    
    public function endDateChartDataByType(string $interval)
    {
        $last = GlobalCoefficient::where('interval', $interval)
            ->select('timestamp')
            ->orderBy('timestamp', 'desc')
            ->first();
        return ($last) ? date('Y-m-d',strtotime($last->timestamp)) : '';
    }
    public function getMaxVolatility($id, $interval)
    {
        $maxVol = Coefficient::where('cryptocurrency_id', $id)->where('interval', $interval)->max('volatility');
        return $maxVol;
    }

    public static function getPeriodIntervals(): array
    {
        return [
            ['key' => self::DAILY_INTERVAL ,
                        'value' => self::DAILY_INTERVAL_VALUE,],
            ['key' => self::WEEKLY_INTERVAL ,
                        'value' => self::WEEKLY_INTERVAL_VALUE,],
            ['key' => self::MONTHLY_INTERVAL ,
                        'value' => self::MONTHLY_INTERVAL_VALUE,]
        ];
    }
    public static function getPeriodIntervalByKey($step)
    {

        switch ($step) {
            case self::DAILY_INTERVAL:
                return 'daily';
            case self::WEEKLY_INTERVAL:
                return 'weekly';
            case self::HOURLY_INTERVAL:
                return 'hourly';
            case self::MONTHLY_INTERVAL:
                return 'monthly';
            
            default:
                return 'weekly';
        }
    }
    
    public function getIdCurrencyByTicker(string $ticker)
    {
        $crypto = Cryptocurrency::where('symbol', strtoupper($ticker))->select('cryptocurrency_id')->first();
        if ($crypto === null) {
            return false;
        }
        return($crypto->cryptocurrency_id);
    }

    public function getEdgeDatesAnnualized($symbol, $interval)
    {
        $base_id = $this->getIdCurrencyByTicker($symbol);
        $quote_id = $this->getIdCurrencyByTicker('USD');
        if ($base_id === false) {
            return false;
        }

        $data_db = GlobalCoefficient::where('base_id', $base_id)
                    ->where('quote_id', $quote_id)
                    ->where('interval', $interval)
                    ->select('timestamp')
                    ->orderBy('timestamp', 'asc')
                    ->get();
        if (count($data_db) == 0) {
            return false;
        }
        $data_db_array = $data_db->toArray();
        return [
            'first_date_data' => $data_db_array[0]['timestamp'],
            'last_date_data' => $data_db_array[count($data_db_array) - 1]['timestamp'],
        ];
    }

    public function getZoomAnnualized()
    {
        return [
            
            [
                'key' => 'markets_filters_zoom_yearly' ,
                'value' => 'year',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_all' ,
                'value' => '',
                'count' => '',
            ],
        ];
    }
    public function getPeriodsAnnualized()
    {
        return [
            [
                'key' => self::MONTHLY_INTERVAL ,
                'value' => 'month',
            ],
            [
                'key' => self::YEARLY_INTERVAL ,
                'value' => 'year',
            ],
        ];
    }

    public function getAnnualizedChartsData($symbol, $periodStartDate, $periodEndDate, $interval, $data)
    {
        $base_id = $this->getIdCurrencyByTicker($symbol);
        $quote_id = $this->getIdCurrencyByTicker('USD');
        if ($base_id === false) {
            return [];
        }

        $data_db = GlobalCoefficient::where('base_id', $base_id)
                    ->where('quote_id', $quote_id)
                    ->where('interval', $interval)
                    ->whereBetween('timestamp', [$periodStartDate, $periodEndDate])
                    ->select('timestamp', 'annualized_return')
                    ->orderBy('timestamp', 'asc')
                    ->get();
        if (count($data_db) == 0) {
            return [];
        }
       
        $data = [];
        foreach ($data_db as $coefficient) {
            $data[] = [
                'time' => $coefficient->timestamp,
                'value' => $coefficient->annualized_return * 100,
            ];
        }

        return $data;
    }

    public function getSearchData($query, $limit)
    {
        
        $array = Coefficient::leftJoin('cryptocurrencies as c1',   'coefficients.cryptocurrency_id' , '=', 'c1.cryptocurrency_id' )
            ->where(function ($q) use ($query) {
                    $q->where('c1.symbol', 'like', $query . '%')
                      ->orWhere('c1.name', 'like', $query . '%');
            })
            ->whereDate('c_date', date('Y-m-d', strtotime('-3 day')))
            ->select(
                'c1.cryptocurrency_id as base_id',
                'c1.symbol as base',
                'c1.name as base_name'
            )
            ->orderBy('c1.id', 'asc')
            ->get();
        $return_array = [];
        if (count($array) > 0) {
            $array = $array->groupBy('base')->toArray();
            $lim = 0;
            foreach ($array as $key => $value) {
                if ($lim >= $limit) break;

                $return_array[] = [
                    'description' => $value[0]['base_name'],
                    'symbol' => $value[0]['base'],
                    'type' => 'bitcoin',
                    'ticker' => $value[0]['base'],
                    'full_name' => $value[0]['base'],
                ];

                $lim++;
            }
        }
        return $return_array;
    }

    public function getResolutionById($id, $convert)
    {
        $resolution_db = Coefficient::where('cryptocurrency_id', $id)
            ->where('convert', $convert)
            ->whereDate('c_date', date('Y-m-d', strtotime('-3 day')))
            ->get();
        $return_array = [];
        if (count($resolution_db) > 0) {
            $return_array = array_keys($resolution_db->groupBy('interval')->toArray());
        }
        return $return_array;
    }
}
 
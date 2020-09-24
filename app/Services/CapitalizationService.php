<?php

namespace App\Services;

use App\Exceptions\EmptyEntityListException;
use Carbon\Carbon;
use Exception;
use App\Cryptocurrency;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\OhlcvPair;


class CapitalizationService
{

    const CHART_STEP_HOUR_VALUE = 'hour';
    const CHART_STEP_4HOUR_VALUE = '4hour';
    const CHART_STEP_12HOUR_VALUE = '12hour';
    const CHART_STEP_DAY_VALUE = 'day';
    const CHART_STEP_WEEK_VALUE = 'week';
    const CHART_STEP_MONTH_VALUE = 'month';
    const CHART_STEP_YEAR_VALUE = 'year';


    const CHART_STEP_HOUR = 'markets_filters_graphs_1h';
    const CHART_STEP_4HOUR = 'markets_filters_graphs_4h';
    const CHART_STEP_12HOUR = 'markets_filters_graphs_12h';
    const CHART_STEP_DAY = 'markets_filters_graphs_daily';
    const CHART_STEP_WEEK = 'markets_filters_graphs_weekly';
    const CHART_STEP_MONTH = 'markets_filters_graphs_monthly';
    const CHART_STEP_YEAR = 'markets_filters_graphs_yearly';
    const DAY_DURATION_IN_SECONDS = 86400;


    public function getWeekDataByTicker($currency, $data)
    {
        $currencyId = $this->getIdCurrencyByTicker($currency);
        if ($currencyId === false) {
            throw new Exception("This currency not find");
        }
        $quoteId = $this->getIdCurrencyByTicker('USD');

        $CapitalizationData = ohlcv_cmc_1d::where('base_id', $currencyId)
                    ->where('quote_id', $quoteId)
                    ->select('open','high','low','close','market_cap','timestamp')
                    ->orderBY('timestamp', 'desc')
                    ->limit(7)
                    ->get()
                    ->toArray();
        if (count($CapitalizationData) < 7) {
            throw new EmptyEntityListException('Entity collection list is empty');
        }
        $graph_array = [];
        foreach ($CapitalizationData as $key => $value) {
           $graph_array[] = [
                    'time'  => date('Y-m-d', strtotime($value['timestamp'])) . ' 00:00:00',
                    'value' => $this->roundData($value['market_cap'])
                ];
        }
        if ($CapitalizationData[1]['market_cap']) {
            $change_24 = (float)($CapitalizationData[0]['market_cap'] - (float)$CapitalizationData[1]['market_cap'])/(float)$CapitalizationData[1]['market_cap'] * 100;
        }

        $block_data = [
                'high_24'      => $this->roundData($CapitalizationData[0]['high']),
                'low_24'       => $this->roundData($CapitalizationData[0]['low']),
                'open_24'      => $this->roundData($CapitalizationData[0]['open']),
                'percent_change_24'    => round($change_24, 2),
            ];
        $data['data'] = $block_data;
        $data['graph'] = $graph_array;
        return $data;
    }

    public function roundData($number)
    {
        $number = (float)$number;
        if (abs($number) >= 1) {
            return round($number, 2);
        }
        elseif (abs($number) >= 0.00001) {
            return round($number, 5);
        }
        else
        {
            $mnoj = 0;
            $num = $number;
            while (strpos($num, 'E')) {
                $num = $number * pow(10, $mnoj);
                $mnoj++;
            }
            $str = str_replace('.', '', (string)floatval($num));
            for ($i=0; $i < strlen($str) ; $i++) {
                if (substr($str, $i,1) != '0') {
                     // return number_format(round($number, $i + $mnoj - 1), $i + $mnoj - 1, '.', ',');
                     return round($number, $i + $mnoj - 1);
                 }
            }
        }
    }

	public function getChartDataByTicker(string $periodStartDate, string $periodEndDate, int $daysCount, array $data, int $objectAmount, string $currency, string $timeframe, $slug)
    {

        $className = 'App\OhlcvModels\ohlcv_cmc_' . $timeframe;
    	$currencyId = $this->getIdCurrencyByTicker($currency);
    	if ($currencyId === false) {
    		throw new Exception("This currency not find");
    	}
        $quoteId = $this->getIdCurrencyByTicker('USD');

        $CapitalizationData = $className::where('quote_id', $quoteId)
		        ->where('base_id', $currencyId)
		        ->whereDate('timestamp', '>=',$periodStartDate)
                ->whereDate('timestamp', '<=',$periodEndDate)
                ->select('timestamp', 'close as price', 'market_cap')
		        ->orderBY('timestamp', 'ASC')
		        ->get();
        if ($CapitalizationData->isEmpty()) {
            throw new EmptyEntityListException('Entity collection list is empty');
        }
        $chartDataInformation = [];
        $lastData = [];
        $startFrom = $periodStartDate;
        $dateIntervalFormat = $this->getDateIntervalFormatByCountDays($daysCount);
        foreach ($CapitalizationData as $datum) {
            if (strtotime($datum->timestamp) >= $startFrom) {

                $chartDataInformation[] = [
                    'time' => date($dateIntervalFormat, strtotime($datum->timestamp)),
                    'value' => [
                        [
                            'value' => $this->roundData($datum->market_cap),
                            'crypto_id' => 1
                        ],
                        [
                            'value' => $this->roundData($datum->price),
                            'crypto_id' => 2
                        ],
                    ]

                ];
            } elseif (strtotime($datum->timestamp) < $startFrom) {
                $lastData = [
                    'time' => date($dateIntervalFormat, strtotime($datum->timestamp)),
                    'value' => [
                        [
                            'value' => $this->roundData($datum->market_cap),
                            'crypto_id' => 1
                        ],
                        [
                            'value' => $this->roundData($datum->price),
                            'crypto_id' => 2
                        ],
                    ]
                ];
                continue;
            } elseif ($periodEndDate < $startFrom) {
                break;
            }
        }
        $chartDataInformationArray = [];
        if (($objectAmount !==0) && (count($chartDataInformation)>$objectAmount)) {
            $CountDataShow = round(count($chartDataInformation) / $objectAmount);
            if ($CountDataShow > 1) {
                foreach ($chartDataInformation as $key => $value) {
                    if ($key % $CountDataShow === 0) {
                        $chartDataInformationArray[] = $value;
                    }
                }
            }
        }
        $chartDataInformationArray = [];
        if (($daysCount > 7) && ($daysCount < 30)) {
            for ($i=0; $i < count($chartDataInformation); $i++) {
                if ($i % 2 === 0) {
                    $chartDataInformationArray[] = $chartDataInformation[$i];
                }
            }
        }
        if (empty($chartDataInformationArray)) {
            $chartDataInformationArray = $chartDataInformation;
        }
        if (!empty($lastData)) {
            $lastData['time'] = date($dateIntervalFormat, $startFrom);
            $chartDataInformationArray[] = $lastData;
        }
        $data['filters_front']['crypto_items'] = $this->getFiltersFront();
        $data['filters']['period_date_start'] = $periodStartDate;
        $data['filters']['period_date_end'] = $periodEndDate;
        $data['data'] = $chartDataInformationArray;
        if (count($CapitalizationData) === 0) {
            $data['data'] = [];
        }

        return $data;
    }

    public function getFiltersFront()
    {
        return [
            [
                'id' => 1,
                'name' => 'Capitalization'
            ],
            [
                'id' => 2,
                'name' => 'Price'
            ],

        ];

    }

    public function getFirtsDatesChartData(string $currency,  $daysCount, $slug)
    {
        $timeframe = $this->getTimeframeByCountDays($daysCount);

        start_timeframe:
        // $className = $this->getClassNameByCountDays($daysCount);
        $currencyId = $this->getIdCurrencyByTickerAndSlug($currency, $slug);
        if ($currencyId === false) {
            return null;
        }
        $quoteId = $this->getIdCurrencyByTicker('USD');
        $last = OhlcvPair::where('quote_id', $quoteId)
                ->where('base_id', $currencyId)
                ->where('exchange', 'cmc')
                ->select($timeframe . '_first_date as first_date', $timeframe . '_last_date as last_date', $timeframe. ' as data')
                ->first();
        if ($last) {
            if ($last['data'] == 0) {
                $timeframe = $this->getNextTimefraseByTimeframe($timeframe);

                if ($timeframe) {
                    goto start_timeframe;
                }else{
                    return false;
                }
            }
            $last['timeframe'] = $timeframe;
        }
        return ($last) ? $last : '';
    }

    public function getIdCurrencyByTickerAndSlug(string $symbol, string $slug)
    {
        $crypto = Cryptocurrency::select('cryptocurrency_id')
            ->when($slug, function ($query) use ($symbol, $slug) {
                    return $query->where( 'slug',  $slug )
                             ->where( 'symbol', $symbol );
                }, function ($query) use ($symbol) {
                    return $query->where( 'symbol', $symbol );
                })
            ->first();

        if ($crypto === null) {
            return false;
        }
        return($crypto->cryptocurrency_id);
    }

    public function getNextTimefraseByTimeframe($timeframe)
    {
        switch ($timeframe) {
            case '1h':
                return '4h';
                break;
            case '4h':
                return '12h';
                break;
            case '12h':
                return '1d';
                break;


            default:
                return false;
                break;
        }
    }

     public function getLastDateChartData(string $currency,  $daysCount)
    {
        $className = $this->getClassNameByCountDays($daysCount);
        $currencyId = $this->getIdCurrencyByTicker($currency);
        if ($currencyId === false) {
            return null;
        }
        $quoteId = $this->getIdCurrencyByTicker('USD');
        $last = $className::where('quote_id', $quoteId)
                ->where('base_id', $currencyId)
                ->select('timestamp')
                ->orderBY('timestamp', 'DESC')
                ->first();
        return ($last) ? date('Y-m-d', strtotime($last->timestamp)) : '';
    }
    public function getIdCurrencyByTicker(string $ticker)
    {
    	$crypto = Cryptocurrency::where('symbol', strtoupper($ticker))->select('cryptocurrency_id')->first();
    	if ($crypto === null) {
    		return false;
    	}
    	return($crypto->cryptocurrency_id);
    }


    public static function getPeriodIntervals(): array
    {
        return [
            [
                'key' => self::CHART_STEP_HOUR ,
                'value' => self::CHART_STEP_HOUR_VALUE,
            ],
            [
                'key' => self::CHART_STEP_4HOUR ,
                'value' => self::CHART_STEP_4HOUR_VALUE,
            ],
            [
                'key' => self::CHART_STEP_12HOUR ,
                'value' => self::CHART_STEP_12HOUR_VALUE,
            ],
            [
                'key' => self::CHART_STEP_DAY ,
                'value'  => self::CHART_STEP_DAY_VALUE,
            ],
            [
                'key' => self::CHART_STEP_WEEK ,
                'value' => self::CHART_STEP_WEEK_VALUE,
            ],
            [
                'key' => self::CHART_STEP_MONTH ,
                'value'  => self::CHART_STEP_MONTH_VALUE,
            ]
        ];
    }
    public static function getZoomIntervals(): array
    {
        return [


            [
                'key' => 'markets_filters_zoom_daily',
                'value' => 'day',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_weekly',
                'value' => 'week',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_monthly',
                'value' => 'month',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_yearly',
                'value' => 'year',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_all',
                'value' => '',
                'count' => '',
            ],

        ];
    }
    protected function getStartDateAsTimestamp(string $startAt,int $stepTimestamp): int
    {
        $today = Carbon::now()->startOfDay()->timestamp;
        $startAt = $startAt ? strtotime($startAt) : $today - $stepTimestamp;
        return $startAt;
    }

    protected function getEndDateAsTimestamp($endAt): int
    {
        $today = Carbon::now()->startOfDay()->timestamp;
        $endAt = $endAt ? strtotime($endAt) : $today;
        return $endAt;
    }

    protected function getTimestampStep(string $step): int
    {
        switch ($step) {
            case self::CHART_STEP_HOUR_VALUE:
                return 60 * 60;
            case self::CHART_STEP_4HOUR_VALUE:
                return 60 * 60 * 4;
            case self::CHART_STEP_12HOUR_VALUE:
                return 60 * 60 * 12;
            case self::CHART_STEP_DAY_VALUE:
                return self::DAY_DURATION_IN_SECONDS;
            case self::CHART_STEP_WEEK_VALUE:
                return self::DAY_DURATION_IN_SECONDS * 7;
            case self::CHART_STEP_MONTH_VALUE:
                return self::DAY_DURATION_IN_SECONDS * 30;
            default:
                return 60;
        }
    }

    protected function getClassNameByCountDays($daysCount)
    {
        if ($daysCount < 7) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_1h';
        }
        elseif ($daysCount < 30) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_1h';
        }
        elseif ($daysCount < 120) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_4h';
        }
        elseif ($daysCount < 365) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_12h';
        }
        elseif ($daysCount >= 365) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_1d';
        }
    }
    protected function getTimeframeByCountDays($daysCount)
    {
        if ($daysCount < 7) {
            return $className = '1h';
        }
        elseif ($daysCount < 30) {
            return $className = '1h';
        }
        elseif ($daysCount < 120) {
            return $className = '4h';
        }
        elseif ($daysCount < 365) {
            return $className = '12h';
        }
        elseif ($daysCount >= 365) {
            return $className = '1d';
        }
    }
    protected function getDateIntervalFormatByCountDays(int $daysCount)
    {
        if ($daysCount < 7) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount < 30) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount < 120) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount < 365) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount >= 365) {
            return 'Y-m-d 00:00:00';
        }
    }
}

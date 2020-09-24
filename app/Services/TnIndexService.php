<?php
/**
 * Created by PhpStorm.
 * User: ashot
 * Date: 2/26/19
 * Time: 5:33 PM
 */

namespace App\Services;


use App\Exceptions\IncorrectTypeException;
use App\TnIndex;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\TnIndexCoin;
use App\CustomIndex;

class TnIndexService
{

    const CHART_STEP_DAY = 'markets_filters_graphs_daily';
    const CHART_STEP_WEEK = 'markets_filters_graphs_weekly';
    const CHART_STEP_MONTH = 'markets_filters_graphs_monthly';
    const CHART_STEP_YEAR = 'markets_filters_graphs_yearly';

    const CHART_STEP_DAY_VALUE = 'day';
    const CHART_STEP_WEEK_VALUE = 'week';
    const CHART_STEP_MONTH_VALUE = 'month';
    const CHART_STEP_YEAR_VALUE = 'year';

    public function getTnIndexes($index_type, $from, $to)
    {
        $top = $this->getIndexes($index_type, $from, $to);

        if (count($top) == 0) {
            return false;
        }
        $data = [];
        foreach ($top as $t) {
            $d = [];
            $value = $t[$index_type];
            $time = $t->timestamp;
            $d['value'] = $value;
            $d['time'] = $time;
            $data[] = $d;
        }

        return $data;

    }

    public function getTnByTops($topCrypts, $count, $date, $quoteId)
    {
        $tn = 0;
        $top = DB::table('ohlcv_cmc_1d')
            ->select('close')
            ->where('quote_id', $quoteId)
            ->whereDate('timestamp', $date)
            ->whereIn('base_id', $topCrypts)
            ->get();

        if (count($top) == $count) {
            $tn = $top->avg('close');
        }

        return $tn;
    }

    public function getIdCurrencyByTicker(string $ticker)
    {
        $crypto = Cryptocurrency::where('symbol', strtoupper($ticker))->select('cryptocurrency_id')->first();
        if ($crypto === null) {
            return false;
        }
        return($crypto->cryptocurrency_id);
    }

    public function getFirstDateTnByTopsFromCmc($topCrypts){
        $quote_id = $this->getIdCurrencyByTicker('USD');
        $top = DB::table('ohlcv_cmc_1h')
            ->select('timestamp')
            ->where('quote_id', $quote_id)
            ->orderBy('timestamp', 'asc')
            ->whereIn('base_id', $topCrypts)
            ->first();
        return $top->timestamp;
    }
    public function getTnByTopsFromCmc($topCrypts, $count, $date)
    {
        $quote_id = $this->getIdCurrencyByTicker('USD');
        $tn = 0;
        $top = DB::table('ohlcv_cmc_1h')
            ->select('close')
            ->where('quote_id', $quote_id)
            ->orderBy('timestamp', 'asc')
            ->whereIn('base_id', $topCrypts)
            ->where('timestamp', $date . ' 23:59:59')
            ->get();
        dump(count($top));
        if (count($top) == $count) {
            $tn = $top->avg('close');
        }

        return $tn;
    }
    public function getTnByTopsFromCmcDay($topCrypts, $count, $date)
    {
        $quote_id = $this->getIdCurrencyByTicker('USD');
        $tn = 0;
        $top = DB::table('ohlcv_cmc_1d')
            ->select('close')
            ->where('quote_id', $quote_id)
            ->orderBy('timestamp', 'asc')
            ->whereIn('base_id', $topCrypts)
            ->whereDate('timestamp', $date)
            ->get();
        if (count($top) == $count) {
            $tn = $top->avg('close');
            dd($top->max());
        }

        return $tn;
    }

    public function getIndexes($index_type, $dateFrom, $dateTo)
    {
        if (!in_array($index_type, self::getChartTypes())) {
            throw new IncorrectTypeException('This data type ' . $index_type . ' does not exist');
        }

        $tnIndex = TnIndex::select($index_type, 'timestamp')
            ->whereBetween('timestamp', [$dateFrom, $dateTo])
            ->orderBy('timestamp')
            ->get();
        return $tnIndex;
    }

    public function getTnIndexesforMonth($lastMonth)
    {
        $tnIndexesforMonth = TnIndex::whereMonth('timestamp', '=', date('m', $lastMonth))
            ->whereYear('timestamp', '=', date('Y', $lastMonth))->get();
        return $tnIndexesforMonth;

    }

    public function getTnIndexbyDate($date)
    {
        $tnIndex = TnIndex::select('tn100', 'timestamp')->whereDate('timestamp', $date)->first();
        return $tnIndex;
    }

    public static function getChartTypes()
    {
        $dataDb = TnIndexCoin::select('index_name', 'default')->get();
        $dataDb = $dataDb->groupBy('index_name');
        $dataArray = [];
        if (count($dataDb) > 0) {
            foreach ($dataDb as $key => $value) {
                if ($value->first()->default) {
                    $dataArray['default'][] = $key;
                }else{
                    $dataArray['custom'][] = $key;
                }
            }
        }
        return $dataArray;
    }

    public function getChartsDataByType(string $type, string $periodStartDate, string $periodEndDate, array $data, int $objectAmount)
    {
        $periodStartDate = $this->getStartDate($periodStartDate);
        $periodEndDate = $this->getEndDate($periodEndDate);

        $tnIndex = CustomIndex::query()->select(
            DB::raw("DATE_FORMAT(timestamp, '%Y-%m-%d') as time"),
            'value'
        )
            ->where('index_name', $type)
            ->whereBetween('timestamp', [$periodStartDate . ' 00:00:00', $periodEndDate . ' 00:00:00'])
            ->orderBy('timestamp', 'asc')
            ->get();

        // $max_db = CustomIndex::select('value')
        //     ->where('index_name', $type)
        //     ->orderBy('value', 'desc')
        //     ->first();
        // $max = (float)$max_db->value;
        // $coefficient = $max / 100;
        // foreach ($tnIndex as $key => $value) {
        //     $tnIndex[$key]->value = (float)$value->value/$coefficient;
        // }
        $tnIndexeArray = [];
        if ($objectAmount !==0) {
            $CountDataShow = round(count($tnIndex) / $objectAmount);
            if ($CountDataShow > 1) {
                foreach ($tnIndex as $key => $value) {
                    if ($key % $CountDataShow === 0) {
                        $tnIndexArray[] = $value;
                    }
                }
            }
        }
        if (empty($tnIndexArray)) {
            $tnIndexArray = $tnIndex;
        }
        $data['filters']['period_date_start'] = $periodStartDate;
        $data['filters']['period_date_end'] = $periodEndDate;
        $data['data'] = $tnIndexArray;
        return $data;
    }

    public function getFirstDatesByType($type)
    {
        $dataDb = CustomIndex::where('index_name', $type)->orderBy('timestamp', 'asc')->get();
        if ($dataDb->count() == 0) {
            return false;
        }
        return [
            'first_date_data' => $dataDb->first()->timestamp,
            'end_date_data' => $dataDb->last()->timestamp,
        ];
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

    public static function getZoomIntervals(): array
    {
        return [
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
                'key' => 'markets_filters_zoom_monthly',
                'value' => 'month',
                'count' => 6,
            ],
            [
                'key' => 'markets_filters_zoom_yearly',
                'value' => 'year',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_all',
                'value' => '',
                'count' => 1,
            ],

        ];
    }


    public function getOhlcvData($symbol, $timeStart, $timeEnd)
    {

        $data = CustomIndex::where('index_name', $symbol)
            ->whereBetween('timestamp', [$timeStart, $timeEnd])
            ->orderBy('timestamp', 'asc')
            ->get();

        if (count($data) > 0) {
            $data = $data->toArray();
            $returnData = [];
            $returnData['s'] = "ok";
            $returnData['c'] = [];
            $returnData['h'] = [];
            $returnData['l'] = [];
            $returnData['o'] = [];
            $returnData['t'] = [];
            $returnData['v'] = [];

            foreach ($data as $item) {
                $returnData['c'][] = floatval($item['value']);
                $returnData['h'][] = floatval($item['value']);
                $returnData['l'][] = floatval($item['value']);
                $returnData['o'][] = floatval($item['value']);
                $returnData['t'][] = strtotime($item['timestamp']);
                $returnData['v'][] = floatval($item['value']);
            }

        } else {
            $returnData = [];
            $returnData['s'] = "no_data";
        }
        return $returnData;

    }
}

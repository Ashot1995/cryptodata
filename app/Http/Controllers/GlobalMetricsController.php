<?php

namespace App\Http\Controllers;

use App\GlobalMetric;
use App\Cryptocurrency;
use App\Exchange;
use App\MarketPair;
use App\GlobalMetricsHistorical;
use App\GlobalMetricsHistoricalRequests;
use App\Http\StatusCode\HTTPStatusCode;
use App\Services\GlobalMetricService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class GlobalMetricsController extends Controller
{
    /**
     * v1/global-metrics/quotes/latest
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function latest(Request $request)
    {
        $symbol = null;

        $convert [] = 'USD';

        $validator = Validator::make($request->all(), [
            'convert' => 'string',
        ]);

        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        if (!empty($request['convert'])) {
            $convert = explode(",", $request['convert']);
        }

        $globalmetric = GlobalMetric::orderByRaw('last_updated DESC')
            ->with(['quotes' => function ($q) use($convert) {
                $q->whereIn('symbol', $convert);
            }])
            ->first();

        $formattedResponse = [];
        foreach ($globalmetric->quotes as $quote) {
            $btc_dominance_dollar = (float)$quote->total_market_cap / 100 * (float)$globalmetric->btc_dominance;
            $eth_dominance_dollar = (float)$quote->total_market_cap / 100 * (float)$globalmetric->eth_dominance;
            $other_dominance = 100 - (float)$globalmetric->btc_dominance - (float)$globalmetric->eth_dominance;
            $other_dominance_dollar = (float)$quote->total_market_cap - $btc_dominance_dollar - $eth_dominance_dollar;

            $formattedResponse['active_market_pairs'] = $globalmetric->active_market_pairs;
            $formattedResponse['eth_dominance'] = $globalmetric->eth_dominance;
            $formattedResponse['btc_dominance'] = $globalmetric->btc_dominance;
            $formattedResponse['total_market_cap'] = $quote->total_market_cap;
            $formattedResponse['total_volume_24h'] = $quote->total_volume_24h;
            $formattedResponse['btc_dominance_dollar'] = $btc_dominance_dollar;
            $formattedResponse['eth_dominance_dollar'] = $eth_dominance_dollar;
            $formattedResponse['other_dominance'] = $other_dominance;
            $formattedResponse['other_dominance_dollar'] = $other_dominance_dollar;
        }
        $active_cryptocurrencies = Cryptocurrency::leftJoin('quotes', 'quotes.cryptocurrency_id', '=',
            'cryptocurrencies.cryptocurrency_id')->where('quotes.symbol', 'USD')->count();
        $active_exchanges = Exchange::count();
        $formattedResponse['active_cryptocurrencies'] = $active_cryptocurrencies;
        $formattedResponse['active_exchanges'] = $active_exchanges - 1;
        $this->saveRequest();
        return response()->json([
            "status" => [
                "error_code" => 0,
                "error_message" => null,
            ],
            'data' => $formattedResponse,
            'filters' => $data['filters'] = [
                'convert' => $convert
            ]
        ], 201);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function historical(Request $request)
    {
        $timeStart = null;
        $timeEnd = null;
        $count = 10;
        $interval = "daily";
        $convert = "USD";
        $query = [];

        $validator = Validator::make($request->all(), [
//            'from' => 'required',
            'time_start' => 'required',
            'count' => 'integer',
            'interval' => 'in:hourly,daily,weekly,monthly,yearly,5m,10m,15m,30m,45m,1h,2h,3h,4h,6h,12h,1d,2d,3d,7d,14d,15d,30d,60d,90d,365d',
//            'resolution' => 'string'
        ]);

//        $resolutions = [
//            'H' => 'hourly',
//            '60' => 'hourly',
//            "D" => 'daily',
//            "1W" => 'weekly',
//            "W" => 'weekly',
//            "1M" => 'monthly',
//            "M" => 'monthly',
//            "365D" => 'yearly',
//            "120" => '2h',
//            "180" => '3h',
//            "240" => '4h',
//            "360" => '6h',
//            "720" => '12h',
//            "2D" => '2d',
//            "3D" => '3d',
//            "15D" => '15d',
//            "90D" => '90d',
//            "365D" => '365d',
//            "14D" => '14d',
//        ];

        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        // from
        if (!empty($request['time_start'])) {
            $timeStart = date('Y-m-d H:i:s', strtotime($request['time_start']));
            $query['time_start'] = $timeStart;
        }

        // to
        if (!empty($request['time_end'])) {
            $timeEnd = date('Y-m-d H:i:s', strtotime($request['time_end']));
            $query['time_end'] = $timeEnd;
        }

        if (!empty($request['count'])) {
            $count = $request['count'];
            $query['count'] = $count;
        }

//        if (!empty($request['resolution']) && !empty($resolutions[$request['resolution']])) {
//            $interval = $resolutions[$request['resolution']];
//
//            if ($interval === 'hourly') {
//                $query['time_period'] = 'hourly';
//            }
//
//        } else if (!empty($request['interval'])) {
//            $interval = $request['interval'];
//        }

        if (!empty($request['interval'])) {
            $interval = $request['interval'];
        }

        $query['interval'] = $interval;

        if (!empty($request['convert'])) {
            $convert = $request['convert'];
            $query['convert'] = $convert;
        }

//        $oldRequests = GlobalMetricsHistoricalRequests::where('updated_at', '<', date('Y-m-d'));
//        $oldRequests->delete();
//        $oldQuotes = GlobalMetricsHistorical::where('updated_at', '<', date('Y-m-d'));
//        $oldQuotes->delete();

        // get from ohlcv_requests table if there is such a request
        $historicalRequest = GlobalMetricsHistoricalRequests::where('convert', $convert)
            ->where('time_start', '>=', $timeStart)
            ->where('time_end', '<=', $timeEnd)
            ->where('count', $count)
            ->where('interval', $interval)
            ->first();

        if (!$historicalRequest) {
            Artisan::call('global:historical', [
                "--interval" => $interval,
                "--convert" => $convert,
                "--time_start" => $timeStart,
                "--time_end" => $timeEnd,
                "--count" => $count,
            ]);
        }

        $globalMetricsHistorical = GlobalMetricsHistorical::where('timestamp', '>=', $timeStart)
                ->where('timestamp', '<=', $timeEnd)
                ->where('interval', $interval)
                ->where('convert', $convert)->get();

//        return response()->json($globalMetricsHistorical, 201);
        $returnData = [];
        $returnData['s'] = "ok";
        $returnData['btc_dominance'] = [];
        $returnData['total_market_cap'] = [];
        $returnData['total_volume_24h'] = [];
        $returnData['t'] = [];
        $returnData['timestamp_quote'] = [];

//
        foreach ($globalMetricsHistorical as $key => $quotes) {
            $returnData['btc_dominance'][] = floatval($quotes->btc_dominance);
            $returnData['total_market_cap'][] = floatval($quotes->total_market_cap);
            $returnData['total_volume_24h'][] = floatval($quotes->total_volume_24h);
            $returnData['timestamp_quote'][] = floatval($quotes->timestamp_quote);
            $returnData['t'][] = strtotime($quotes->timestamp);
        }

        $this->saveRequest();
        return response()->json($returnData, 201);

    }
    /**
     * for charting_library
     * @return \Illuminate\Http\JsonResponse
     */
    public function config()
    {
        return response()->json([
            'supported_resolutions' => ["5", "10", "15", "30", "45", "60", "120", "180", "360", "720", "D", "2D", "3D", "15D", "W", "2W", "3W", "M", "2M", "3M", "Y"],
//            'supported_resolutions' => ['
//"yearly" "monthly" "weekly" "daily" "hourly" "5m" "10m" "15m" "30m" "45m" "1h" "2h" "3h" "6h" "12h" "24h" "1d" "2d" "3d" "7d" "14d" "15d" "30d" "60d" "90d" "365d"],
            'supports_group_request' => false,
            'supports_marks' => false,
            'supports_search' => true,
            'supports_time' => true,
//            'supports_timescale_marks' => true,
        ], 201);
    }

    public function getChartsData(Request $request)
    {
        $first_date = $request->get('first_date', false);
        $globalMetricService = new GlobalMetricService();

        $first_date_data = $globalMetricService->getLastDateChartData();
        $end_date_data = $globalMetricService->getFirstDateChartData();
        $periodStartDate = $request->get('period_date_start', $first_date_data);
        $periodEndDate = $request->get('period_date_end', $end_date_data);
        $step = $request->get('period_interval', GlobalMetricService::CHART_STEP_WEEK_VALUE);
        $chartType = $request->get('chart_type', 'btc_dominance');
        $objectAmount = $request->get('object_amount', 0);
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'first_date' => $first_date,
                'period_date_start' => $periodStartDate,
                'period_date_end' => $periodEndDate,
                'period_interval' => $step,
                'chart_type' => $chartType,
                'object_amount' => $objectAmount,
                'chart_types' => GlobalMetricService::getChartTypes(),
                'period_intervals' => GlobalMetricService::getPeriodIntervals(),
                'zoom' => GlobalMetricService::getZoomIntervals(),
                'first_date_data' => $first_date_data,
                'end_date_data' => $end_date_data,
            ],
            'data' => []
        ];
        if ($first_date) {
            return response()->json($data, HTTPStatusCode::OK);
        }
        try {
            $data = $globalMetricService->getChartDataByType($chartType, $periodStartDate, $periodEndDate, $step, $data, $objectAmount);
        } catch (\Exception $e) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] = [
                'error_message' => $e->getMessage(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        return response()->json($data, HTTPStatusCode::OK);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\StatusCode\HTTPStatusCode;
use Illuminate\Http\Request;
use App\Services\CapitalizationService;

class CapitalizationController extends Controller
{

    public function getChartsDataByTicker(Request $request)
    {
        $CapitalizationService = new CapitalizationService;
        $step = $request->get('period_interval', CapitalizationService::CHART_STEP_WEEK_VALUE);
        $objectAmount = $request->get('object_amount', 0);
        $currency = $request->get('currency', 'btc');
        $slug = $request->get('slug', false);
        $periodStartDate = $request->get('period_date_start', false);
        $periodEndDate = $request->get('period_date_end', false);
        if ($periodStartDate && $periodEndDate) {
            $daysCount = (int)floor(((int)strtotime($periodEndDate) - (int)strtotime($periodStartDate)) / 3600 / 24);
        } else {
            $daysCount = 365;
        }
        $first_dates = $CapitalizationService->getFirtsDatesChartData($currency, $daysCount, $slug);
        if ($first_dates) {
            $first_date_data = date('Y-m-d', strtotime($first_dates['first_date']));
            $end_date_data = date('Y-m-d', strtotime($first_dates['last_date']));
            $timeframe = $first_dates['timeframe'];
        } else {
            $first_date_data = null;
            $end_date_data = null;
        }
        $first_date = $request->get('first_date', false);
        $periodStartDate = $request->get('period_date_start', $first_date_data);
        $periodEndDate = $request->get('period_date_end', $end_date_data);


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
                'object_amount' => $objectAmount,
                'zoom' => CapitalizationService::getZoomIntervals(),
                'currency' => $currency,
                'first_date_data' => $first_date_data,
                'end_date_data' => $end_date_data,
            ],
            'filters_front' => [],
            'data' => []
        ];

        if (!$first_dates) {
            return response()->json($data, HTTPStatusCode::OK);
        }
        $daysCount = (int)floor(((int)strtotime($periodEndDate) - (int)strtotime($periodStartDate)) / 3600 / 24);
        if ($first_date) {
            return response()->json($data, HTTPStatusCode::OK);
        }
        try {
            $data = $CapitalizationService->getChartDataByTicker($periodStartDate, $periodEndDate, $daysCount, $data, $objectAmount, $currency, $timeframe, $slug);
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

    public function getWeekDataByTicker(Request $request)
    {
        $currency = $request->get('symbol', 'btc');
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'symbol' => $currency,
            ],
            'data' => [],
            'graph' => [],
        ];
        $CapitalizationService = new CapitalizationService;

        try {
            $data = $CapitalizationService->getWeekDataByTicker($currency, $data);
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

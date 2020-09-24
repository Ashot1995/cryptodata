<?php

namespace App\Http\Controllers;

use App\Http\StatusCode\HTTPStatusCode;
use App\Services\CryptoCurrencyService;
use Illuminate\Http\Request;

class CryptocurrencyController extends Controller
{
    public function getCurrentHistory(Request $request)
    {
        $currencySymbol = $request->get('symbol', '');
        $slug = $request->get('slug', false);
        $page = (int) $request['page'] ?: 1;
        $perPage = (int) $request['perPage'] ?: 10;
        $skip = ($page - 1) * $perPage;
        $cryptoService = new CryptoCurrencyService();
        try {
            $historyData = $cryptoService->getCryptoCurrencyHistoryDataBySymbolAndSlug($currencySymbol, $slug);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], HTTPStatusCode::BAD_REQUEST);
        }
        $data['pagination'] = [
            'page' => $page,
            'perPage' => $perPage,
            'skip' => $skip,
            'total' => count($historyData)
        ];
        $data['filters'] = [
            'symbol' => $currencySymbol
        ];
        $this->saveRequest();
        $data['data_history'] = array_slice($historyData, $skip, $perPage);
        return response()->json($data, HTTPStatusCode::OK);
    }

    public function compareCoins(Request $request)
    {
        $coins = $request->get('coin_list', '');
        $first_date = $request->get('first_date', false);
        $periodStartDate = $request->get('period_date_start', '');
        $periodEndDate = $request->get('period_date_end', '');
        $step = $request->get('period_interval', CryptoCurrencyService::COMPARE_STEP_WEEK_VALUE);
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'first_date' => $first_date,
                'coin_list' => $coins,
                'period_date_start' => $periodStartDate,
                'period_date_end' => $periodEndDate,
                'period_interval' => $step,
                'period_intervals' => CryptoCurrencyService::getPeriodIntervals()
            ],
            'filters_front' => [],
            'data' => []
        ];
        if ($first_date) {
            return $data;
        }
        $cryptoService = new CryptoCurrencyService();
        try {
            $data = $cryptoService->getCoinsCompareData($coins, $periodStartDate, $periodEndDate, $data);
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
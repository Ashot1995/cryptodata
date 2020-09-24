<?php

namespace App\Http\Controllers;

use App\Http\DateFormat\DateFormat;
use App\Http\StatusCode\HTTPStatusCode;
use App\Services\CoefficientService;
use App\Services\CryptoCurrencyService;
use Illuminate\Http\Request;
use Validator;
use App\Cryptocurrency;


class CoefficientController extends Controller
{
    const DEFAULT_CONVERT = 'USD';
    const RESOLUTIONS = ["W", "M", "Y"];
    const RESOLUTIONS_CONVERT = [
        'H' => 'hour',
        '60' => 'hour',
        "D" => 'day',
        "W" => 'week',
        "1W" => 'week',
        "M" => 'month',
        "1M" => 'month',
        "Y" => 'year',
        "Y1" => 'year',
    ];

    /**
     * for charting_library
     * @return \Illuminate\Http\JsonResponse
     */
    public function config()
    {
        $this->saveRequest();
        return response()->json([
            'supported_resolutions' => self::RESOLUTIONS,
            'supports_group_request' => false,
            'supports_marks' => false,
            'supports_search' => true,
            'supports_time' => true,
            'symbols_types' => [
                0 => [
                    'name' => "bitcoin",
                    'value' => "bitcoin"
                ]
            ]
        ], HTTPStatusCode::OK);
    }

    /**
     * for charting_library
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function symbols(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'symbol' => 'required'
        ]);

        if ($validator->fails()) {
            $this->saveRequest();
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        $symbolArrFirst = explode(':', $request['symbol']);
        $symbolArr = explode('/', $symbolArrFirst[0]);
        $symbol = $symbolArr[0];

        $cryptoService = new CryptoCurrencyService;
        $CoefficientService = new CoefficientService;
        // get data from DB
        $symbolData = Cryptocurrency::where('symbol', $symbol)->first();
        // $convert = !empty($symbolArr[1]) ? $symbolArr[1] : self::DEFAULT_CONVERT;
        $convert = $cryptoService->getIdCurrencyByTicker('USD');
        $resolution = $CoefficientService->getResolutionById($symbolData->cryptocurrency_id, $convert);
        if (count($resolution) > 0) {
            foreach ($resolution as $key => $value) {
                $resolution[$key] = array_search($value, self::RESOLUTIONS_CONVERT);
            }
        }
        $returnDada = [
            'description' => $symbolData->name,
            'exchange-listed' => '',
            'exchange-traded' => '',
            'has_intraday' => false,
            'has_no_volume' => false,
            'minmovement' => 1,
            'minmovement2' => 0,
            'name' => $symbolData->symbol,
            'pointvalue' => 1,
            'pricescale' => 100000000,
            'session' => "24x7",
            'ticker' => $symbolData->symbol,
            'timezone' => "Asia/Almaty",
            'has_daily' => false,
            'has_intraday' => false,
            'has_weekly_and_monthly' => true,
            'type' => "bitcoin",
            'supported_resolutions' => $resolution,
        ];

        $this->saveRequest(0, 0, $symbol, $convert);
        return response()->json($returnDada, HTTPStatusCode::OK);
    }

    /**
     * for charting_library
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMethod(Request $request)
    {
        $limit = 30;
        $query = null;
        $query2 = null;

        if (!empty($request['limit'])) {
            $limit = $request['limit'];
        }
        if (!empty($request['query'])) {
            $queryArray = explode('/', $request['query']);
            $query = $queryArray[0];
            $query2 = !empty($queryArray[1]) ? $queryArray[1] : null;
        }

        $cryptoService = new CoefficientService;
        $searchData = $cryptoService->getSearchData($query, $limit);

        $this->saveRequest();
        return response()->json($searchData, HTTPStatusCode::OK);

    }

    public function volatilityHistorical(Request $request)
    {
        $symbol = null;
        $interval = "daily";
        $convert = self::DEFAULT_CONVERT;

        $validator = Validator::make($request->all(), [
            'symbol' => 'required'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        if ($request->get('symbol')) {
            $symbolArr = explode('/', $request->get('symbol'));
            $symbol = $symbolArr[0];

            if (!empty($symbolArr[1])) {
                $convert = $symbolArr[1];
            }

        }
        $timeEnd = date('Y-m-d', $request->get('to', strtotime('today')));
        $timeStart = date('Y-m-d', $request->get('from', strtotime($timeEnd . "-30 days")));

        if (!empty($request['resolution']) && !empty(self::RESOLUTIONS_CONVERT[$request['resolution']])) {
            $interval = self::RESOLUTIONS_CONVERT[$request['resolution']];
        }

        $cryptoService = new CryptoCurrencyService();
        $returnData = $cryptoService->getCryptocurrencyWithCoefficients($symbol, $convert, $timeStart, $timeEnd,
            $interval, 'volatility');

        if (!$returnData) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST, 0, $symbol, $convert);
            return response()->json([
                's' => 'no_data',
            ]);
        }

        $this->saveRequest(0, 0, $symbol, $convert);
        return response()->json($returnData, HTTPStatusCode::OK);

    }

    public function sharpeHistorical(Request $request)
    {
        $symbol = null;
        $interval = "daily";
        $convert = self::DEFAULT_CONVERT;

        $validator = Validator::make($request->all(), [
            'symbol' => 'required'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        if ($request->get('symbol')) {
            $symbolArr = explode('/', $request->get('symbol'));
            $symbol = $symbolArr[0];

            if (!empty($symbolArr[1])) {
                $convert = $symbolArr[1];
            }

        }

        $timeEnd = date('Y-m-d', $request->get('to', strtotime('today')));
        $timeStart = date('Y-m-d', $request->get('from', strtotime($timeEnd . "-30 days")));

        if (!empty($request['resolution']) && !empty(self::RESOLUTIONS_CONVERT[$request['resolution']])) {
            $interval = self::RESOLUTIONS_CONVERT[$request['resolution']];
        }

        $cryptoService = new CryptoCurrencyService();
        $returnData = $cryptoService->getCryptocurrencyWithCoefficients($symbol, $convert, $timeStart, $timeEnd,
            $interval, 'sharpe');

        if (!$returnData) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST, 0, $symbol, $convert);
            return response()->json([
                's' => 'no_data',
            ]);
        }

        $this->saveRequest(0, 0, $symbol, $convert);
        return response()->json($returnData, HTTPStatusCode::OK);

    }

    public function alphaHistorical(Request $request)
    {
        $symbol = null;
        $interval = "daily";
        $convert = self::DEFAULT_CONVERT;

        $validator = Validator::make($request->all(), [
            'symbol' => 'required'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], HTTPStatusCode::BAD_REQUEST);
        }

        if ($request->get('symbol')) {
            $symbolArr = explode('/', $request->get('symbol'));
            $symbol = $symbolArr[0];

            if (!empty($symbolArr[1])) {
                $convert = $symbolArr[1];
            }

        }

        $timeEnd = date(DateFormat::DATE_FORMAT, $request->get('to', strtotime('today')));
        $timeStart = date(DateFormat::DATE_FORMAT, $request->get('from', strtotime($timeEnd . "-30 days")));

        if (!empty($request['resolution']) && !empty(self::RESOLUTIONS_CONVERT[$request['resolution']])) {
            $interval = self::RESOLUTIONS_CONVERT[$request['resolution']];
        }

        $cryptoService = new CryptoCurrencyService();
        $returnData = $cryptoService->getCryptocurrencyWithCoefficients($symbol, $convert, $timeStart, $timeEnd,
            $interval, 'alpha');

        if (!$returnData) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST, 0, $symbol, $convert);
            return response()->json([
                's' => 'no_data',
            ]);
        }

        $this->saveRequest(0, 0, $symbol, $convert);
        return response()->json($returnData, 201);
    }

    public function betaHistorical(Request $request)
    {
        $symbol = null;
        $interval = "daily";
        $convert = self::DEFAULT_CONVERT;

        $validator = Validator::make($request->all(), [
            'symbol' => 'required'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        if ($request->get('symbol')) {
            $symbolArr = explode('/', $request->get('symbol'));
            $symbol = $symbolArr[0];

            if (!empty($symbolArr[1])) {
                $convert = $symbolArr[1];
            }

        }

        $timeEnd = date(DateFormat::DATE_FORMAT, $request->get('to', strtotime('today')));
        $timeStart = date(DateFormat::DATE_FORMAT, $request->get('from', strtotime($timeEnd . "-30 days")));

        if (!empty($request['resolution']) && !empty(self::RESOLUTIONS_CONVERT[$request['resolution']])) {
            $interval = self::RESOLUTIONS_CONVERT[$request['resolution']];
        }

        $cryptoService = new CryptoCurrencyService();
        $returnData = $cryptoService->getCryptocurrencyWithCoefficients($symbol, $convert, $timeStart, $timeEnd,
            $interval, 'beta');

        if (!$returnData) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST, 0, $symbol, $convert);
            return response()->json([
                's' => 'no_data',
            ]);
        }

        $this->saveRequest(0, 0, $symbol, $convert);
        return response()->json($returnData, HTTPStatusCode::OK);

    }

    public function sortinoHistorical(Request $request)
    {

        $symbol = null;
        $interval = "weekly";
        $convert = self::DEFAULT_CONVERT;

        $validator = Validator::make($request->all(), [
            'symbol' => 'required'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        if ($request->get('symbol')) {
            $symbolArr = explode('/', $request->get('symbol'));
            $symbol = $symbolArr[0];

            if (!empty($symbolArr[1])) {
                $convert = $symbolArr[1];
            }

        }

        $timeEnd = date(DateFormat::DATE_FORMAT, $request->get('to', strtotime('today')));
        $timeStart = date(DateFormat::DATE_FORMAT, $request->get('from', strtotime($timeEnd . "-30 days")));

        if (!empty($request['resolution']) && !empty(self::RESOLUTIONS_CONVERT[$request['resolution']])) {
            $interval = self::RESOLUTIONS_CONVERT[$request['resolution']];
        }

        $cryptoService = new CryptoCurrencyService();
        $returnData = $cryptoService->getCryptocurrencyWithCoefficients($symbol, $convert, $timeStart, $timeEnd,
            $interval, 'sharpe');

        if (!$returnData) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST, 0, $symbol, $convert);
            return response()->json([
                's' => 'no_data',
            ]);
        }

        $this->saveRequest(0, 0, $symbol, $convert);
        return response()->json($returnData, HTTPStatusCode::OK);

    }

    public function getChartsData(Request $request)
    {
        $chartTypes = CryptoCurrencyService::getChartTypes();
        $validator = Validator::make($request->all(), [
            'symbol' => 'required',
            'chart_type' => 'in:' . implode(',', $chartTypes)
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        $cryptoService = new CryptoCurrencyService();

        $chartSymbol = $request->get('symbol', '');
        $periodIntervals = $cryptoService->getPeriodIntervalsBySymbol($chartSymbol);

        $step = $request->get('period_interval', CryptoCurrencyService::WEEKLY_HISTORY_INTERVAL_VALUE);
        $first_date_data = $cryptoService->getLastDateCoefficientsBySimbol($chartSymbol, $step);
        $end_date_data = $cryptoService->getFirstDateCoefficientsBySimbol($chartSymbol, $step);
        $periodStartDate = $request->get('period_date_start', $first_date_data);
        $periodEndDate = $request->get('period_date_end', $end_date_data);
        $chartType = $request->get('chart_type', CryptoCurrencyService::CHART_TYPE_SORTINO);
        $zoom = CryptoCurrencyService::getZoomIntervals();

        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'period_date_start' => $periodStartDate,
                'period_date_end' => $periodEndDate,
                'period_interval' => $step,
                'chart_type' => $chartType,
                'chart_types' => $chartTypes,
                'period_intervals' => $periodIntervals,
                'zoom' => $zoom,
                'symbol' => $chartSymbol,
                'first_date_data' => $first_date_data,
                'end_date_data' => $end_date_data,
            ],
            'info' => [
                'name' => $chartType,
                'period' => $periodStartDate . ' ' . $periodEndDate,
                'symbol' => $chartSymbol,
                'interval' => $step,
            ],
            'data' => []
        ];


        if (!$first_date_data) {
            $data['status'] = [
                'error_message' => 'Data not found for this interval and symbol',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        // dd($data);
        try {
            $data['data'] = $cryptoService->getChartDataBySymbolAndType($chartSymbol, $chartType, $periodStartDate, $periodEndDate, $step, $data);
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

    public function getGlobalChartsData(Request $request)
    {
        $periodIntervals = CoefficientService::getPeriodIntervals();
        $chartTypes = array_keys(CoefficientService::CHART_TYPES);
        $validator = Validator::make($request->all(), [
            'chart_type' => 'in:' . implode(',', $chartTypes)
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        $first_date = $request->get('first_date', false);

        $coefficientService = new CoefficientService();
        $step = $request->get('period_interval', CoefficientService::MONTHLY_INTERVAL);
        $first_date_data = $coefficientService->firstDateChartDataByType($step);
        $end_date_data = $coefficientService->endDateChartDataByType($step);
        $chartType = $request->get('chart_type', CoefficientService::CHART_TYPE_RETURN);
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
                'chart_type' => $chartType,
                'chart_types' => $chartTypes,
                'period_intervals' => $periodIntervals,
                'first_date_data' => $first_date_data,
                'end_date_data' => $end_date_data,
            ],
            'info' => [
                'name' => $chartType,
                'period' => $periodStartDate . ' ' . $periodEndDate,
                'interval' => $step,
            ],
            'data' => []
        ];
        // $step = CoefficientService::getPeriodIntervalByKey($step);

        $data['data'] = $coefficientService->getChartDataByType($chartType, $periodStartDate, $periodEndDate, $step, $data);

        return response()->json($data, HTTPStatusCode::OK);

    }

    public function getAnnualizedChartsData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        $symbol = $request->get('symbol', 'BTC');
        $first_date = $request->get('first_date', false);

        $coefficientService = new CoefficientService();

        $interval = $request->get('period_interval', 'year');
        $egde_dates = $coefficientService->getEdgeDatesAnnualized($symbol, $interval);
        if ($egde_dates === false) {
            return response()->json([
                'error_message' => 'No data for this symbol and interval',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }
        $first_date_data = $egde_dates['first_date_data'];
        $end_date_data = $egde_dates['last_date_data'];
        $periodStartDate = $request->get('period_date_start', $first_date_data);
        $periodEndDate = $request->get('period_date_end', $end_date_data);
        $periodIntervals = $coefficientService->getPeriodsAnnualized();
        $zoom = $coefficientService->getZoomAnnualized();

        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'period_date_start' => $periodStartDate,
                'period_date_end' => $periodEndDate,
                'period_interval' => $interval,
                'period_intervals' => $periodIntervals,
                'zoom' => $zoom,
                'symbol' => $symbol,
                'first_date_data' => $first_date_data,
                'end_date_data' => $end_date_data,
            ],

            'data' => []
        ];

        $data['data'] = $coefficientService->getAnnualizedChartsData($symbol, $periodStartDate, $periodEndDate, $interval, $data);

        return response()->json($data, HTTPStatusCode::OK);
    }
}

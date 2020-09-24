<?php

namespace App\Http\Controllers;

use App\Cryptocurrency;
use App\Http\DateFormat\DateFormat;
use App\Services\CryptoCurrencyService;
use App\Http\StatusCode\HTTPStatusCode;
use Illuminate\Http\Request;
use Validator;
use App\MarketPair;
use DB;

class CcxtOhlcvController extends Controller
{
    const DEFAULT_COINS_QUOTES_SYMBOL = 'USDT';
    const DEFAULT_EXCHANGE_NAME = 'binance';
    const RESOLUTIONS = ['1', '5', '15', '30', '60', '240', '720', "D", "W", "M"];
    const STOCK_INTERVALS = [
        '1' => '1m',
        '5' => '5m',
        '15' => '15m',
        '30' => '30m',
        '60' => '1h',
        '240' => '4h',
        '720' => '12h',
        '1D' => '1d',
        '1W' => '1w',
        '1M' => '30d',
    ];
    const INTRADAY_MULTIPLIERS = ['1', '5', '15', '30', '60', '240', '720'];
    const RESOLUTIONS_CONVERT = [
        '1' => '1m',
        '5' => '5m',
        '15' => '15m',
        '30' => '30m',
        'm' => '1m',
        'H' => '1h',
        '60' => '1h',
        '240' => '4h',
        '720' => '12h',
        "D" => '1d',
        "1D" => '1d',
        "1W" => '1w',
        "W" => '1w',
        "M" => 'monthly',
        "1M" => 'monthly',
        "365D" => 'yearly',
    ];

    /**
     * for charting_library
     * @return \Illuminate\Http\JsonResponse
     */
    public function config()
    {
        $this->saveRequest();
        $cryptoService = new CryptoCurrencyService();
        $exchanges = $cryptoService->getCcxtExchanges();

        return response()->json([
            'supported_resolutions' => self::RESOLUTIONS,
            'supports_group_request' => false,
            'supports_marks' => false,
            'supports_search' => true,
            'supports_time' => true,
            'symbols_types' => [0 => [
                'name' => "bitcoin",
                'value' => "bitcoin"
            ]
            ],
            'exchanges' => $exchanges
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

        $symbolArrFirst = explode(':', $request->get('symbol'));
        if (count($symbolArrFirst) > 1) {
            $stock = strtolower($symbolArrFirst[0]);
            $pair = $symbolArrFirst[1];
        } else {
            $stock = 'binance';
            $pair = $symbolArrFirst[0];
        }
        if (strpos($pair, '/') !== false) {
            $symbolArr = explode('/', $pair);
            $baseSymbol = $symbolArr[0];
            $quoteSymbol = $symbolArr[1];
        } else {
            $baseSymbol = $pair;
            $quoteSymbol = 'USDT';
        }
        $cryptoService = new CryptoCurrencyService();
        $exchange = $stock;
        if ($exchange === false) {
            return response()->json([
                'error_message' => 'Exchange not find',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }
        // get data from DB
        //
        $pairId = MarketPair::getPairIdStrong($baseSymbol, $quoteSymbol);
        if ($pairId === false) {
            return response()->json([
                'error_message' => 'Pair ' . $pair . ' not find',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }


        $currency_resolution = $cryptoService->getSymbolResolution($pairId[1], $pairId[2], $exchange);


        $returnDada = [
            'description' => $baseSymbol . '/' . $quoteSymbol,
            'exchange-listed' => $stock,
            'exchange-traded' => $stock,
            'has_intraday' => false,
            'has_no_volume' => false,
            'minmovement' => 1,
            'minmovement2' => 0,
            'name' => $baseSymbol . '/' . $quoteSymbol,
            'pointvalue' => 1,
            'pricescale' => 100000000,
            'session' => "24x7",
            'ticker' => $exchange . ':' . $baseSymbol . '/' . $quoteSymbol,
            'timezone' => "Asia/Almaty",
            'has_daily' => true,
            'has_intraday' => true,
            'has_weekly_and_monthly' => true,
            'type' => "bitcoin",
            'supported_resolutions' => $currency_resolution,
            'intraday_multipliers' => self::INTRADAY_MULTIPLIERS
        ];

        $this->saveRequest(0, 0, $baseSymbol, $quoteSymbol);
        return response()->json($returnDada, HTTPStatusCode::OK);
    }

    /**
     * for charting_library
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMethod(Request $request)
    {
        $query = null;
        $query2 = null;
        $limit = $request->get('limit', 30);

        $exchange = strtolower($request->get('exchange', ''));
        $requestQuery = $request->get('query', '');
        if ($exchange === '') {
            $symbolArrFirst = explode(':', $request->get('query'));
            if (count($symbolArrFirst) > 1) {
                $exchange = strtolower($symbolArrFirst[0]);
                $requestQuery = $symbolArrFirst[1];
            } else {
                $requestQuery = $symbolArrFirst[0];
            }
        }
        // dd($exchange);
        $cryptoService = new CryptoCurrencyService();
        // $exchange = $cryptoService->changeExchangeToClass($exchange);
        if ($exchange === false) {
            return response()->json([], HTTPStatusCode::OK);
        }

        if ($requestQuery) {
            $queryArray = explode('/', $requestQuery);
            $query = $queryArray[0];
            $query2 = !empty($queryArray[1]) ? $queryArray[1] : null;
        }

        $searchData = $cryptoService->getSearchData($query, $query2, $limit, $exchange);
        $this->saveRequest();
        return response()->json($searchData, HTTPStatusCode::OK);
    }

    public function ohlcvHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'symbol' => 'required',
            'resolution' => 'string'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }
        $symbolArrFirst = explode(':', $request->get('symbol'));
        if (count($symbolArrFirst) > 1) {
            $stock = strtolower($symbolArrFirst[0]);
            $pair = $symbolArrFirst[1];
        } else {
            $stock = 'binance';
            $pair = $symbolArrFirst[0];
        }
        if (strpos($pair, '/') !== false) {
            $symbolArr = explode('/', $pair);
            $baseSymbol = $symbolArr[0];
            $quoteSymbol = $symbolArr[1];
        } else {
            $baseSymbol = $pair;
            $quoteSymbol = 'USDT';
        }
        $cryptoService = new CryptoCurrencyService();
        $exchange = $stock;
        // $exchange = $cryptoService->changeExchangeToClass($stock);
        // if ($exchange === false) {
        //     return response()->json([
        //         'error_message' => 'Exchange not find',
        //         'error_code' => HTTPStatusCode::BAD_REQUEST
        //     ], HTTPStatusCode::BAD_REQUEST);
        // }

        $resolution = $request->get('resolution', '60');
        $from = $request->get('from');
        $to = $request->get('to', date(DateFormat::DATE_FORMAT));
        $interval = self::RESOLUTIONS_CONVERT[$resolution];

        $interval_stock = self::STOCK_INTERVALS[$resolution];


        $timeStart = date(DateFormat::DATE_TIME_FORMAT, $from);
        $timeEnd = date(DateFormat::DATE_TIME_FORMAT, $to);
        $ccxtOjlcvData = $cryptoService->getCcxtOjlcvData($baseSymbol, $quoteSymbol, $timeStart, $timeEnd, $interval_stock, $exchange);
        return response()->json($ccxtOjlcvData, HTTPStatusCode::OK);
    }
    // public function duplicate(Request $request)
    // {
    //     $table = $request->get('table', 'binance_1d');
    //     $className = 'App\OhlcvModels\ohlcv_' . $table;
    //     $tableName = 'ohlcv_' . $table;
    //     $duplicate = DB::delete("
    //         delete
    //         from {$tableName} using {$tableName},
    //             {$tableName} e1
    //         where {$tableName}.id > e1.id
    //             and {$tableName}.base_id = e1.base_id
    //             and {$tableName}.quote_id = e1.quote_id
    //             and {$tableName}.timestamp = e1.timestamp
    //         ");

    //     dump($tableName);
    //     dump($duplicate);
    // }
}

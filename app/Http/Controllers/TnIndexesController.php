<?php

namespace App\Http\Controllers;

use App\Http\StatusCode\HTTPStatusCode;
use App\Services\TnIndexService;
use Illuminate\Http\Request;
use Validator;
use App\Cryptocurrency;
use App\TnIndexCoin;
use App\CustomIndex;
use App\Http\DateFormat\DateFormat;

class TnIndexesController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCharts(Request $request)
    {
        $tnIndexService = new TnIndexService();
        $objectAmount = $request->get('object_amount', 0);
        $type = $request->get('chart_type', 'tn10');

        $first_dates = $tnIndexService->getFirstDatesByType($type);
        if (!$first_dates) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => 'No data for this type',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        $first_date_data = date('Y-m-d', strtotime($first_dates['first_date_data']));
        $end_date_data = date('Y-m-d', strtotime($first_dates['end_date_data']));

        $periodStartDate = $request->get('period_date_start', $first_date_data);
        $periodEndDate = $request->get('period_date_end', $end_date_data);
        $interval = $request->get('period_interval', 'month');
        $first_dates = $tnIndexService->getFirstDatesByType($type);
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'chart_type' => $type,
                'period_date_start' => $periodStartDate,
                'period_date_end' => $periodEndDate,
                'object_amount' => $objectAmount,
                'first_date_data' => $first_date_data,
                'end_date_data' => $end_date_data,
                'chart_types' => TnIndexService::getChartTypes(),
                'zoom' => TnIndexService::getZoomIntervals(),

            ],
            'data' => []
        ];
        try {
            $data = $tnIndexService->getChartsDataByType($type, $periodStartDate, $periodEndDate, $data, $objectAmount);
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

    public function getIndexes()
    {
        $dataDb = TnIndexCoin::with('Cryptocurrency')->get();
        $dataDb = $dataDb->groupBy('index_name');
        $dataArray = [];
        if (count($dataDb) > 0) {
            foreach ($dataDb as $key => $value) {
                $coinsArray = [];
                foreach ($value as  $coin) {
                    $coinsArray[] = $coin->cryptocurrency->symbol;
                }
                $dataArray[] = [
                    'name' => $key,
                    'default' => (boolean)$value->first()->default,
                    'count_coins' => count($value),
                    'coins' => $coinsArray
                ];
            }
        }
        return  [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'data' => $dataArray,
        ];
    }

    public function newIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'coin_list' => 'required|string',
            'default' => 'boolean',
        ]);
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
        ];
        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        $requestSymbols = $request->coin_list;
        $nameIndex = $request->name;
        $default = $request->get('default', 'false');
        $data['filters'] = [
            'coin_list' => $requestSymbols,
            'name' => $nameIndex,
            'default' => $default,
        ];
        
        if (TnIndexCoin::where('index_name', $nameIndex)->count() > 0) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => 'Name ' . $nameIndex . ' already used ',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        $requestSymbols = explode(',', $requestSymbols);
        $requestSymbolsArray = [];
        foreach ($requestSymbols as $key => $value) {
            $requestSymbolsArray[$key] = str_replace(' ', '', $value);
        }
        $cryptoDb = Cryptocurrency::select('symbol', 'cryptocurrency_id')->whereIn('symbol', $requestSymbolsArray)->get()->toArray();
        $cryptoSymbolsDb = [];
        foreach ($cryptoDb as $key => $value) {
            $cryptoSymbolsDb[$value['cryptocurrency_id']] = $value['symbol'];
        }

        $coins_array = [];
        foreach ($requestSymbolsArray as $key => $coin) {

            if (!in_array(strtoupper($coin), $cryptoSymbolsDb)) {
                $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
                $data['status'] =[
                    'error_message' => 'Undefined symbol ' . $coin,
                    'error_code' => HTTPStatusCode::BAD_REQUEST
                ];
                return response()->json($data, HTTPStatusCode::BAD_REQUEST);
            }
            $coins_array[] = [
                'index_name' => $nameIndex,
                'cryptocurrency_id' => array_search(strtoupper($coin), $cryptoSymbolsDb),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        TnIndexCoin::insert($coins_array);
        return response()->json($data, HTTPStatusCode::OK);

    }

    public function changeIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'coin_list' => 'required|string',
            'default' => 'boolean',
        ]);
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
        ];
        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        $default = $request->get('default', 'false');
        $requestSymbols = $request->coin_list;
        $nameIndex = $request->name;
        $data['filters'] = [
            'coin_list' => $requestSymbols,
            'name' => $nameIndex,
            'default' => $default,
        ];
        $coin_tn_index = TnIndexCoin::where('index_name', $nameIndex)->pluck('cryptocurrency_id')->toArray();
        if (count($coin_tn_index) == 0) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => 'Index ' . $nameIndex . ' not found ',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        $requestSymbols = explode(',', $requestSymbols);
        $requestSymbolsArray = [];
        foreach ($requestSymbols as $key => $value) {
            $requestSymbolsArray[$key] = str_replace(' ', '', $value);
        }
        $cryptoDb = Cryptocurrency::select('symbol', 'cryptocurrency_id')->whereIn('symbol', $requestSymbolsArray)->get()->toArray();
        $cryptoSymbolsDb = [];
        foreach ($cryptoDb as $key => $value) {
            $cryptoSymbolsDb[$value['cryptocurrency_id']] = $value['symbol'];
        }
        $coins_array = [];
        $change = false;
        foreach ($requestSymbolsArray as $key => $coin) {
            if (!in_array(strtoupper($coin), $cryptoSymbolsDb)) {
                $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
                $data['status'] =[
                    'error_message' => 'Undefined symbol ' . $coin,
                    'error_code' => HTTPStatusCode::BAD_REQUEST
                ];
                return response()->json($data, HTTPStatusCode::BAD_REQUEST);
            }
            $coin_id = array_search(strtoupper($coin), $cryptoSymbolsDb);
            if (!in_array(strtoupper($coin_id), $coin_tn_index)) {
                $change = true;
            }
            
            $coins_array[] = [
                'index_name' => $nameIndex,
                'cryptocurrency_id' => $coin_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        if (count($requestSymbolsArray) != count($coin_tn_index)) {
            $change = true;
        }
        if ($change) {
            CustomIndex::where('index_name', $nameIndex)->delete();
            TnIndexCoin::where('index_name', $nameIndex)->delete();
            TnIndexCoin::insert($coins_array);
        }
            
        return response()->json($data, HTTPStatusCode::OK);
    }

    public function deleteIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
        ];
        if ($validator->fails()) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        $nameIndex = $request->name;
        $data['filters'] = [
            'name' => $nameIndex
        ];
        
        $index = TnIndexCoin::where('index_name', $nameIndex)->first();
        if (!$index) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => 'Index ' . $nameIndex . ' not found ',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        if ($index->default == 1) {
            $this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] =[
                'error_message' => 'Can\'t delete default index',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }
        
        CustomIndex::where('index_name', $nameIndex)->delete();
        TnIndexCoin::where('index_name', $nameIndex)->delete();
            
        return response()->json($data, HTTPStatusCode::OK);
    }

    public function config()
    {
        $this->saveRequest();
        return response()->json([
            'supported_resolutions' => ['D'],
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
        if ($symbol == 'BTC') {
            $symbol = 'tn10';
        }
        

        $dataDb = TnIndexCoin::where('index_name', $symbol)->first();
        if (!$dataDb) {
            $this->saveRequest();
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ], HTTPStatusCode::BAD_REQUEST);
        }

        $returnDada = [
            'description' => $dataDb->index_name,
            'exchange-listed' => '',
            'exchange-traded' => '',
            'has_intraday' => false,
            'has_no_volume' => false,
            'minmovement' => 1,
            'minmovement2' => 0,
            'name' => $dataDb->index_name,
            'pointvalue' => 1,
            'pricescale' => 100000000,
            'session' => "24x7",
            'ticker' => $dataDb->index_name,
            'timezone' => "Asia/Almaty",
            'has_daily' => false,
            'has_intraday' => false,
            'has_weekly_and_monthly' => true,
            'type' => "bitcoin",
            'supported_resolutions' => ['D'],
        ];

        $this->saveRequest(0, 0, $symbol);
        return response()->json($returnDada, HTTPStatusCode::OK);
    }

    public function searchMethod(Request $request)
    {
        $query = null;
        $limit = $request->get('limit', 30);

        $requestQuery = $request->get('query', '');

        $dataDb = TnIndexCoin::where('index_name', 'like', $requestQuery . '%')->get();
        if ($dataDb->count() == 0) {
            return [];
        }
        $dataDb = $dataDb->groupBy('index_name')->toArray();

        $lim = 0;
        $searchData = [];
        foreach ($dataDb as $key => $value) {
            if ($lim >= $limit) break;

            $searchData[] = [
                'description' => $key,
                'symbol' => $key,
                'type' => 'bitcoin',
                'ticker' => $key,
                'full_name' => $key,
            ];

            $lim++;
        }
        $this->saveRequest();
        return response()->json($searchData, HTTPStatusCode::OK);
    }

    public function getHistorical(Request $request)
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
        $symbol = $request->get('symbol');
        $resolution = $request->get('resolution', 'D');

        $from = $request->get('from');
        $to = $request->get('to', date(DateFormat::DATE_FORMAT));


        $timeStart = date(DateFormat::DATE_TIME_FORMAT, $from);
        $timeEnd = date(DateFormat::DATE_TIME_FORMAT, $to);
        $TnService = new TnIndexService;
        $ohlcvData = $TnService->getOhlcvData($symbol, $timeStart, $timeEnd);
        return response()->json($ohlcvData, HTTPStatusCode::OK);
    }
}

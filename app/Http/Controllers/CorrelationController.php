<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\DateFormat\DateFormat;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\Services\CorrelationService;
use App\Correlation;
use App\Http\StatusCode\HTTPStatusCode;
use App\CorrelationCoin;
use Artisan;

class CorrelationController extends Controller
{

    public function getDataByInterval(Request $request)
    {	
    	// $periodStartDate = $request->get('from', '');
     //    $periodEndDate = $request->get('to', '');
        $correlationService = new CorrelationService;
        $interval = $request->get('interval', 'week');
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'interval' => $interval,
                'intervals' => $correlationService->getCorrelationIntervals(),
            ],
            'filters_front' => [],
            'data' => []
        ];

        if (!in_array($interval, CorrelationService::INTERVALS_CORRELATION)) {
        	$this->saveRequest(HTTPStatusCode::BAD_REQUEST);
            $data['status'] = [
                'error_message' => 'interval incorrectly entered',
                'error_code' => HTTPStatusCode::BAD_REQUEST
            ];
            return response()->json($data, HTTPStatusCode::BAD_REQUEST);
        }

        
        try {
            $data = $correlationService->getDataByInterval($interval, $data);
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

    public function getCoins()
    {
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'data' => CorrelationCoin::select('symbol as value')->get()->toArray(),
        ];
        
        return($data);
    }

    public function updateCoins(Request $request)
    {
        $coins =  $request->get('coin_list', '');
        $data = [
            'status' => [
                'error_message' => 0,
                'error_code' => null
            ],
            'filters' => [
                'coin_list' => $coins,
            ],
        ];
        if (!$request->has('coin_list')) {
            $data['status'] = [
                    'error_message' => 'Coins list can\'t by empty',
                    'error_code' => '400'
                ];
            return response()->json($data , 400);
        }

        
        $coins_array = explode(',', $coins);
        if (count($coins_array) != 20) {
            $data['status'] = [
                    'error_message' => 'The number of coins must be equal to 20, now: ' .count($coins_array),
                    'error_code' => '400'
                ];
            return response()->json($data , 400);
        }
        
        $crypto_db = Cryptocurrency::whereIn('symbol', $coins_array)->select('cryptocurrency_id', 'symbol')->get();
        if ($crypto_db->count() !== 20) {
            $crypto_db_array = [];
            foreach ($crypto_db as $key_db => $value_db) {
                $crypto_db_array[] = strtolower($value_db->symbol);
                
            }
            $string_undefined_coins = '';
            foreach ($coins_array as $key => $value) {
                if (array_search(strtolower($value), $crypto_db_array) === false ){
                    $string_undefined_coins .= (string)$value . ', ';
                }
            }
            $data['status'] = [
                    'error_message' => 'symbols: ' . $string_undefined_coins . 'not found',
                    'error_code' => '400'
                ];
            return response()->json($data , 400);
        }
        foreach ($crypto_db as $key_db => $value) {
            $crypto_db[$key_db]->created_at = date('Y-m-d H:i:s');
            $crypto_db[$key_db]->updated_at = date('Y-m-d H:i:s');
        }
        $crypto_db = $crypto_db->toArray();
        CorrelationCoin::truncate();
        CorrelationCoin::insert($crypto_db);
        
       
        return response()->json([$data, 200]);


    }


}

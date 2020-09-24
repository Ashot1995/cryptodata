<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/19/19
 * Time: 7:46 PM
 */

namespace App\Services;


use App\MarketPair;
use App\Request;

class CoinBaseService
{
    public static function saveRequestCommands($errorCode = 0, $creditCount = 0, $symbol = '', $convert = '', $apiReq = '')
    {
        $marketPairId = null;
        $currencySymbol = null;
        if ($symbol && $convert) {
            $currencySymbol = $symbol . '/' . $convert;
            // get pair id
            $marketPairId = MarketPair::getPairId($symbol, $convert);

        } else if($symbol) {
            $currencySymbol = $symbol;
        }
        $newRequest = Request::where('api_coin_request', $apiReq)->where('currency_symbol', $currencySymbol)->whereDate('created_at', '=', date('Y-m-d'))->first();
        if (!$newRequest) {
            $newRequest = new Request();
            $newRequest->request_name = '';
            $newRequest->api_coin_request = $apiReq;
            $newRequest->currency_symbol = $currencySymbol;
            $newRequest->market_pair_id = $marketPairId;
            $newRequest->success_count = !$errorCode ? 1 : 0;
            $newRequest->credit_count = $creditCount;
            $newRequest->daily_request_count = 1;
        } elseif ($errorCode === 0) {
            $newRequest->success_count++;
            $newRequest->credit_count = isset($newRequest->credit_count) ? $newRequest->credit_count + $creditCount : 0;
            $newRequest->daily_request_count++;
        } else {
            $newRequest->credit_count = isset($newRequest->credit_count) ? $newRequest->credit_count + $creditCount : 0;
            $newRequest->daily_request_count++;
        }

        $newRequest->save();
    }


}

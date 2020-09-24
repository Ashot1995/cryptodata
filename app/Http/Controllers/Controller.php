<?php

namespace App\Http\Controllers;

use App\MarketPair;
use App\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function saveRequest($errorCode = 0, $creditCount = 0, $symbol = '', $convert = '',  $apiReq = '')
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

        $requestName = url()->current();
        $newRequest = Request::where('request_name', $requestName)->where('currency_symbol', $currencySymbol)->whereDate('created_at', '=', date('Y-m-d'))->first();
        if (!$newRequest) {
            $newRequest = new Request();
            $newRequest->request_name = $requestName;
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

    function currencyFormat($num) {

        if($num>1000 && $num < 1000000000000000000) {

            $x = round($num);
            $x_number_format = number_format($x);
            $x_array = explode(',', $x_number_format);
            $x_parts = array('K', 'M', 'B', 'T', 'Q');
            $x_count_parts = count($x_array) - 1;
            $x_display = $x;
            $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
            $x_display .= $x_parts[$x_count_parts - 1];

            return $x_display;

        }

        return $num;
    }

    public function timeMethod()
    {
        $this->saveRequest();
        return response()->json(strtotime("now"), 201);
    }
}

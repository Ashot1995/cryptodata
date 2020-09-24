<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Request as Req;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    public function requestsOld(Request $request) {

        $timeStart = null;
        $timeEnd = date('Y-m-d');
        $interval = null;
        $search = null;

        $validator = Validator::make($request->all(), [
//            'time_start' => 'date',
            'interval' => 'integer',
        ]);

        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        if (!empty($request['time_start'])) {
            $timeStart = date('Y-m-d', strtotime($request['time_start']));
        }

        if (!empty($request['time_end'])) {
            $timeStart = date('Y-m-d', strtotime($request['time_end']));
        }

        if (!empty($request['interval'])) {
            $interval = $request['interval'];
            //todo if timeStart then timeEnd = timeStart + interval

            if ($timeStart) {
                $timeEnd =date('Y-m-d', strtotime('+' . $interval -1 . ' day', strtotime($timeStart)));
            } else {
                $timeStart = date('Y-m-d', strtotime('-' . $interval + 1 . ' day', strtotime($timeEnd)));
            }

        }

        if (!empty($request['search'])) {
            $search = $request['search'];
        }

        if ($timeStart) {
            //group by date
            $request = Req::where('request_name', 'like', '%' . $search . '%')->select('id', 'request_name', 'currency_symbol', 'api_coin_request', 'credit_count', 'success_count', 'daily_request_count', 'created_at' )
                ->whereDate('created_at', '>=', $timeStart)->whereDate('created_at', '<=', $timeEnd)
                ->get();

        } else {
            $request = Req::where('request_name', 'like', '%' . $search . '%')->select('id', 'request_name', 'currency_symbol',  'api_coin_request', 'credit_count', 'success_count', 'daily_request_count', 'created_at' )
                ->whereDate('created_at', '=', $timeEnd)
                ->get();
        }

        return response()->json($request, 200);
    }

    public function requests(Request $request) {

        $timeStart = null;
        $timeEnd = date('Y-m-d');
        $interval = null;
        $search = null;

        $validator = Validator::make($request->all(), [
//            'time_start' => 'date',
            'interval' => 'integer',
        ]);

        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        if (!empty($request['time_start'])) {
            $timeStart = date('Y-m-d', strtotime($request['time_start']));
        }

        if (!empty($request['time_end'])) {
            $timeStart = date('Y-m-d', strtotime($request['time_end']));
        }

        if (!empty($request['interval'])) {
            $interval = $request['interval'];
            //todo if timeStart then timeEnd = timeStart + interval

            if ($timeStart) {
                $timeEnd =date('Y-m-d', strtotime('+' . $interval -1 . ' day', strtotime($timeStart)));
            } else {
                $timeStart = date('Y-m-d', strtotime('-' . $interval + 1 . ' day', strtotime($timeEnd)));
            }

        }

        if (!empty($request['search'])) {
            $search = $request['search'];
        }

        if ($timeStart) {
            //group by date
            $request = Req::where('request_name', 'like', '%' . $search . '%')
                ->select('api_coin_request',  DB::raw('sum(credit_count) credit_count'))
                ->whereDate('created_at', '>=', $timeStart)->whereDate('created_at', '<=', $timeEnd)->where('api_coin_request', '!=', '')
                ->orderBy('credit_count', 'DESC')
                ->groupBy('api_coin_request')
                ->get();

        } else {
            $request = Req::where('request_name', 'like', '%' . $search . '%')
                ->select('api_coin_request',  DB::raw('sum(credit_count) credit_count'))
                ->whereNotNull('api_coin_request')
                ->orderBy('credit_count', 'DESC')
                ->groupBy('api_coin_request')
                ->get();
        }

        return response()->json($request, 200);
    }
}

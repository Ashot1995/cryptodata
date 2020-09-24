<?php

namespace App\Http\Controllers;

use App\Cryptocurrency;
use App\Exchange;
use App\ExchangePairQuotes;
use App\Http\DateFormat\DateFormat;
use App\MarketPair;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\Services\ExchangeService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Validator;
use Illuminate\Http\Request;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Facades\URL;

class ExchangeController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mapAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start' => 'integer',
            'limit' => 'integer',
        ]);
        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        $limit = $request->get('limit', 0);
        $start = $request->get('start', 0);

        $exchangesCount = Exchange::count();

        if (!$limit) {
            $limit = $exchangesCount;
        }

        $exchanges = Exchange::select('name')->limit($limit)->offset($start)->get();
        $this->saveRequest();
        return response()->json([
            "status" => [
                "error_code" => 0,
                "error_message" => null,
                'timestamp' => date(DateFormat::DATE_TIME_FORMAT),
            ],
            'exchanges_count' => $exchangesCount,
            'data' => $exchanges,
        ], 201);
    }

    /**
     * TN_135
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer',
            'sort' => 'in:name,volume_24h',
            'sort_dir' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        $limit = $request->get('limit', 100);
        $sort = $request->get('sort', 'volume_24h');
        $sortDir = $request->get('sort_dir', 'desc');
        $autocomplete = $request->get('autocomplete', '');

        $exchanges = Exchange::leftJoin('exchange_quotes', 'exchange_quotes.exchange_id', '=', 'exchanges.exchange_id')
            ->select(
                'exchanges.name AS name',
                'exchanges.logo_2 AS logo',
                'exchanges.num_market_pairs AS pair_count',
                'exchange_quotes.volume_24h as value_24h',
                'exchange_quotes.volume_7d as value_7d',
                'exchange_quotes.volume_30d as value_30d',
                'exchange_quotes.percent_change_volume_24h as percent_change_volume_24h',
                'exchange_quotes.percent_change_volume_7d as percent_change_volume_7d',
                'exchange_quotes.percent_change_volume_30d as percent_change_volume_30d'
            )
            ->orderBy($sort, $sortDir)
            ->where('exchange_quotes.symbol', 'USD')
            ->where('name', 'LIKE', $autocomplete . '%')
            ->paginate($limit)->toArray();

        $skip = ($exchanges['current_page'] - 1) * $limit;

        foreach ($exchanges['data'] as $key => $exchange) {
            $exchanges['data'][$key]['rank'] = $skip + $key + 1;
            $exchanges['data'][$key]['logo'] = $exchange['logo'] ? URL::to('/') . $exchange['logo'] : '';

        }

        $this->saveRequest();
        return response()->json([
            "status" => [
                "error_code" => 0,
                "error_message" => null,
            ],
            'pagination' => [
                "page" => $exchanges['current_page'],
                "per_page" => $exchanges['per_page'],
                "skip" => ($exchanges['current_page'] - 1) * $limit,
                "total" => $exchanges['total'],
            ],
            'filters' => [
                'autocomplete' => $autocomplete,
            ],
            'data' => $exchanges['data']
        ], 201);


    }

    public function marketPairs(Request $request)
    {
        $curlString = env('API_COIN') . "exchange/market-pairs/latest";
        $usedCurlRequest = false;
        $query = [];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'limit' => 'integer'
        ]);

        if ($validator->fails()) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => $validator->errors()->first(),
                'error_code' => '400'
            ], 400);
        }

        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        $name = $request->get('name');
        $skip = ($page - 1) * $limit;


        // check if all data isset in the tables
        // if yes just show data, if not get from coin_api, save, then show


        $exchangeServise = new ExchangeService();
        $exchange = $exchangeServise->getExchange($name);

        if (!$exchange) {
            $this->saveRequest(400);
            return response()->json([
                'error_message' => 'no_data_found',
                'error_code' => '400'
            ], 400);
        }

        $exchangeMarketPairs = $exchangeServise->getExchangeMarketPairs($exchange, $limit, $skip);

        if (!$exchangeMarketPairs['data']) {
            $query['id'] = $exchange->id;
            $f = (int)($skip / 100);
            $query['start'] = $f * 100 + 1;

            try {
                $client = new Client();
                $response = $client->get($curlString, [
                    'headers' => ['X-CMC_PRO_API_KEY' => env('API_COIN_KEY')],
                    'query' => $query,
                ]);
                $body = $response->getBody();
                $result = json_decode($body, true);
                $usedCurlRequest = true;
                $creditCount = $result['status']['credit_count'];

                foreach ($result['data']['market_pairs'] as $dataitem) {

                    $pieces = explode("/", $dataitem['market_pair']);
                    $marketPairId = MarketPair::getPairId($pieces[0], $pieces[1]);

                    if (!$marketPairId) {
                        $cId1 = Cryptocurrency::where('symbol', $pieces[0])->first();
                        $cId2 = Cryptocurrency::where('symbol', $pieces[1])->first();

                        if ($cId1 && $cId2) {
                            $newMarketPair = new MarketPair();
                            $newMarketPair->string1_id = $cId1->cryptocurrency_id;
                            $newMarketPair->string2_id = $cId2->cryptocurrency_id;
                            $newMarketPair->save();
                            $marketPairId = $newMarketPair->id;
                        } else {
                            continue;
                        }

                    }

                    $price = $dataitem['quote']['exchange_reported']['price'];

                    foreach ($dataitem['quote'] as $key => $res) {
                        if ($key == 'exchange_reported') {
                            continue;
                        }

                        $symbol = $key;
                        $usd_id = $exchangeServise->getIdCurrencyByTicker('USD');
                        $convertPrice = $dataitem['quote'][$symbol]['price'];
                        $volume24h = $dataitem['quote'][$symbol]['volume_24h'];


                        //get old volume_24h anc calculate percent_value_24h
                        $exchangePairQuotes = ExchangePairQuotes::where('exchange_id', $exchange->exchange_id)
                            ->where('market_pair_id', $marketPairId)
                            ->where('symbol', $usd_id)
                            ->whereDate('updated_at', '<', Date(DateFormat::DATE_FORMAT))->first();


                        if ($exchangePairQuotes) {
                            if (($exchangePairQuotes->volume_24h != 0)&&(!empty($exchangePairQuotes->volume_24h))) {
                                $percentValue24h = ($exchangePairQuotes->volume_24h - $volume24h) * 100 / $exchangePairQuotes->volume_24h;
                            }else{
                                $percentValue24h = null;
                            }
                            // $exchangePairQuotes->delete();
                            // dd($exchangePairQuotes->delete());
                        } else {
                            $percentValue24h = null;
                        }



                        if (!$exchangePairQuotes) {
                            $exchangePairQuotes = new ExchangePairQuotes();

                            $exchangePairQuotes->exchange_id = $exchange->exchange_id;
                            $exchangePairQuotes->symbol = $usd_id;
                            $exchangePairQuotes->market_pair_id = $marketPairId;
                            $exchangePairQuotes->price = $price;
                            $exchangePairQuotes->convert_price = $convertPrice;
                            $exchangePairQuotes->volume_24h = $volume24h;
                            $exchangePairQuotes->percent_value_24h = $percentValue24h;

                            $exchangePairQuotes->save();
                        }else{

                            $exchangePairQuotes = ExchangePairQuotes::where('exchange_id', $exchange->exchange_id)
                            ->where('market_pair_id', $marketPairId)
                            ->where('symbol', $usd_id)
                            ->update([
                                'price' => $price,
                                'convert_price' => $convertPrice,
                                'volume_24h' => $volume24h,
                                'percent_value_24h' => $percentValue24h,
                            ]);
                        }



                        $exchangeMarketPairs = $exchangeServise->getExchangeMarketPairs($exchange, $limit, $skip);

                    }
                }
            } catch (ClientException $exception) {
                // $this->saveRequest(400, 0, '', '', $curlString);
                // return response()->json(json_decode($exception->getResponse()->getBody()->getContents(), true));
                 $exchangeMarketPairs = $exchangeServise->getExchangeMarketPairsWithoutDate($exchange, $limit, $skip);

            }

        }
        $pagination = [
            "page" => $exchangeMarketPairs['current_page'],
            "per_page" => $exchangeMarketPairs['per_page'],
            "skip" => ($exchangeMarketPairs['current_page'] - 1) * $limit,
            "total" => $exchange['num_market_pairs'],
        ];

        foreach ($exchangeMarketPairs['data'] as $key => $exchangeMarketPair) {
            $exchangeMarketPairs['data'][$key]['volume_24h'] = round($exchangeMarketPair['volume_24h']);
            $exchangeMarketPairs['data'][$key]['price_usd'] = round($exchangeMarketPair['price_usd'], 2);
            $exchangeMarketPairs['data'][$key]['percent_value_24h'] = round($exchangeMarketPair['percent_value_24h'], 2);
            $exchangeMarketPairs['data'][$key]['logo'] = $exchangeMarketPair['logo'] ? URL::to('/') . $exchangeMarketPair['logo'] : '';
            unset(
                $exchangeMarketPairs['data'][$key]['id'],
                $exchangeMarketPairs['data'][$key]['string1_id'],
                $exchangeMarketPairs['data'][$key]['string2_id'],
                $exchangeMarketPairs['data'][$key]['created_at'],
                $exchangeMarketPairs['data'][$key]['updated_at'],
                $exchangeMarketPairs['data'][$key]['allowed_ohlcv']
            );
        }
        $exchange->urls = json_decode($exchange->urls);
        $exchange->volume_24h = round($exchange->volume_24h);
        $exchange->volume_7d = round($exchange->volume_7d);
        $exchange->volume_30d = round($exchange->volume_30d);
        $exchange->logo = $exchange->logo ? URL::to('/') . $exchange->logo : '';
        $btc_id = Cryptocurrency::where('symbol', 'BTC')->select('cryptocurrency_id as id')->first();

        $btc_price = ohlcv_cmc_1d::where('base_id', $btc_id->id)
            ->select('close as price')
            ->orderBy('timestamp', 'desc')
            ->first();
        $btc_volume_24 = round($exchange->volume_24h / $btc_price->price);

        $exchange->btc_volume_24 = $btc_volume_24;

        unset($exchange->exchange_id);
        unset($exchange->id);

        if ($usedCurlRequest) {
            $this->saveRequest(0, $creditCount, '', '', $curlString);
        } else {
            $this->saveRequest(0);
        }

        return response()->json([
            'exchange' => $exchange,
            'pagination' => $pagination,
            'filters' => [
                'name' => $name,
                'page' => $page,
                'limit' => $limit,
            ],
            'exchange_market_pairs' => $exchangeMarketPairs['data']
        ], 200);

    }

    public function autocomplete()
    {
        $exchangeServise = new ExchangeService();
        $exchange = $exchangeServise->getExchangeForAutocomplete();
        $this->saveRequest();
        return response()->json([
            "status" => [
                "error_code" => 0,
                "error_message" => null,
            ],
            'data' => $exchange
        ], 201);
    }
}

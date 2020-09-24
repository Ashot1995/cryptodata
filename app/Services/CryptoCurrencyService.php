<?php

namespace App\Services;

use App\Cryptocurrency;
use App\Coefficient;
use App\CryptocurrencyHistorical;
use App\DataProviders\CryptoCurrencyDataProvider;
use App\Exceptions\CoinsLimitException;
use App\Exceptions\EmptyEntityListException;
use App\Exchange;
use App\OhlcvModels\ohlcv_cmc_1d;
use App\OhlcvModels\ohlcv_cmc_1h;
use App\OhlcvModels\ohlcv_cmc_1m;
use App\OhlcvModels\ohlcv_cmc_1w;
use App\Platform;
use App\Quote;
use App\MarketPair;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\EntityNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\OhlcvPair;

class CryptoCurrencyService
{
    const PRICE_SYMBOL = '$';
    const PERCENT_CHANGE_SYMBOL = '%';
    const QUOTES_CURRENCY_SYMBOL = 'USD';
    const MARKET_CAP_ORDER_DEFAULT = 1000000;
    const TOP_LIMIT = 10;
    const DAILY_HISTORY_INTERVAL = 'markets_filters_graphs_daily';
    const WEEKLY_HISTORY_INTERVAL = 'markets_filters_graphs_weekly';
    const HOURLY_HISTORY_INTERVAL = 'markets_filters_graphs_1h';
    const MONTHLY_HISTORY_INTERVAL = 'markets_filters_graphs_monthly';
    const YEARLY_HISTORY_INTERVAL = 'markets_filters_graphs_monthly';

    const DAILY_HISTORY_INTERVAL_VALUE = 'day';
    const WEEKLY_HISTORY_INTERVAL_VALUE = 'week';
    const HOURLY_HISTORY_INTERVAL_VALUE = 'hour';
    const MONTHLY_HISTORY_INTERVAL_VALUE = 'month';
    const YEARLY_HISTORY_INTERVAL_VALUE = 'year';

    const MINUTELY_HISTORY_INTERVAL = 'minutely';
    const DEFAULT_COINS_QUOTES_SYMBOL = 'USD';

    const COMPARE_STEP_MINUTE = 'minute';


    const COMPARE_STEP_HOUR_VALUE = 'hour';
    const COMPARE_STEP_DAY_VALUE = 'day';
    const COMPARE_STEP_WEEK_VALUE = 'week';
    const COMPARE_STEP_MONTH_VALUE = 'month';

    const COMPARE_STEP_HOUR = 'markets_filters_graphs_1h';
    const COMPARE_STEP_DAY = 'markets_filters_graphs_daily';
    const COMPARE_STEP_WEEK = 'markets_filters_graphs_weekly';
    const COMPARE_STEP_MONTH = 'markets_filters_graphs_monthly';
    const DAY_DURATION_IN_SECONDS = 86400;
    const CHART_TYPE_SORTINO = 'sortino';
    const CHART_TYPE_RETURN = 'return';
    const CHART_TYPE_SHARPE = 'sharpe';
    const CHART_TYPE_VOLATILITY = 'volatility';
    const CURRENCY_FIAT = 'fiat';
    const CURRENCY_CRYPTO = 'cryptocurrency';
    const EXCHANGES_TRADINGVIEW = ['Bittrex', 'Huobi Pro', 'OKEX', 'Binance', 'Poloniex'];
    const EXCHANGES_TRADINGVIEW_NAME = ['bittrex', 'huobi_pro', 'okex', 'binance', 'poloniex'];
    // const EXCHANGES_TRADINGVIEW = [ 'Coinmarketcap', 'Bitfinex', 'Bittrex', 'Huobi Pro', 'OKEX', 'Binance', 'Poloniex'];
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
    const CMC_INTERVALS = [
        '60' => '1h',
        '240' => '4h',
        '720' => '12h',
        '1D' => '1d',
        '1W' => '1w',
        '1M' => '30d',
    ];
    const INTERVAL_CCXT = [
            '1m' => '1m',
            '5m'=> '5m',
            '15m'=> '15m',
            '30m'=> '30m',
            '1h'=> '1h',
            '4h'=> '4h',
            '12h'=> '12h',
            '1d'=> '1d',
            '1w'=> '1w',
            '30d'=> '1M',
        ];
    const TIME_FRAMES = ['30d', '1w', '1d', '12h', '4h', '1h', '30m', '15m', '5m', '1m'];
    public function getWidgetCryptoMarketTopData(): array
    {
        $cryptocurrencies = $this->getCryptocurrenyWithQuotes();
        $crypts = $this->combineCurrencyWithQuotesData($cryptocurrencies);
        $exchanges = $this->getExchangesWithQuotes();
        $formattedExchangesData = $this->forrmatExchangesData($exchanges);
        $data = [
            'status' => [
                "error_code" => 0,
                "error_message" => null,
            ],
            'top10_crypto' => $crypts,
            'top10_exchange' => $formattedExchangesData
        ];
        return $data;
    }


    public function quoteId()
    {
        $quote_id = Cryptocurrency::select('cryptocurrency_id')->where('symbol',
            self::DEFAULT_COINS_QUOTES_SYMBOL)->first();
        return $quote_id;
    }

    public function getAllCurrencies()
    {
        $cryptocurrencies = Cryptocurrency::query()->get();
        return $cryptocurrencies;
    }

    public function getCryptocurrencyWithQuotes($id, $convert)
    {
        $cryptocurrenciesWithQuotes = Quote::select('price')
            ->where('cryptocurrency_id', $id)
            ->where('symbol', $convert)
            ->orderBy('updated_at')
            ->first();
        return $cryptocurrenciesWithQuotes;
    }

    public function getCryptocurrenciesPriceForROI($id)
    {
        $quote_id = $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL);
        $week = ohlcv_cmc_1d::where('base_id', $id)
                    ->where('quote_id', $quote_id)
                    ->whereDate('timestamp', date('Y-m-d', strtotime('-1 week')))
                    ->select('close')
                    ->first();
        $month = ohlcv_cmc_1d::where('base_id', $id)
                    ->where('quote_id', $quote_id)
                    ->whereDate('timestamp', date('Y-m-d', strtotime('-1 month')))
                    ->select('close')
                    ->first();
        $threeMonth = ohlcv_cmc_1d::where('base_id', $id)
                    ->where('quote_id', $quote_id)
                    ->whereDate('timestamp', date('Y-m-d', strtotime('-3 month')))
                    ->select('close')
                    ->first();

        return [
            'week' => ($week) ? $week->close : null,
            'month' => ($month) ? $month->close : null,
            'threeMonth' => ($threeMonth) ? $threeMonth->close : null,
        ];
    }

    public function getCryptocurrencyWhithCryptoHistorical($id, $dateFrom, $dateTo)
    {
        $cryptocurrencyWhithCryptoHistorical = CryptocurrencyHistorical::select('price', 'created_at')
            ->orderBy('price', 'desc')
            ->where('cryptocurrency_id', $id)
            ->where('created_at', '<=', $dateTo)
            ->where('created_at', '>=', $dateFrom)
            ->orderBy('updated_at')
            ->first();
        return $cryptocurrencyWhithCryptoHistorical;
    }

    public function getCryptocurrencyMaxPrice($id)
    {
        $quote_id = $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL);
        $maxPrice = ohlcv_cmc_1d::select('high', 'timestamp')
            ->orderBy('high', 'desc')
            ->where('base_id', $id)
            ->where('quote_id', $quote_id)
            ->first();
        if ($maxPrice) {
            return [$maxPrice->high, $maxPrice->timestamp];
        }
        return 0;
    }


     public function getCryptocurrencySavePrice($id, $maxPrice)
    {
        $savePercentChanges = Cryptocurrency::where('cryptocurrency_id', $id)
            ->first();

        if (!$savePercentChanges) {
            $savePercentChanges = new Cryptocurrency();
        }
        $max_price = $this->roundData($maxPrice[0]);
        if ((float)$savePercentChanges->ath < (float)$max_price) {
            $savePercentChanges->ath = $max_price;
            $savePercentChanges->ath_date = date('Y-m-d 00:00:00', strtotime($maxPrice[1]));
            $savePercentChanges->save();

        }
    }

    public function getCryptocurrencySavePercentChanges($parcentChangesWeekly, $parcentChangesMonthly,
        $parcentChangesThreeMonth,  $id)
    {
        $savePercentChanges = Cryptocurrency::where('id', $id)
            ->first();

        if (!$savePercentChanges) {
            $savePercentChanges = new Cryptocurrency();
        }
        $savePercentChanges->percent_changes_weekly = floatval($parcentChangesWeekly);
        $savePercentChanges->percent_changes_monthly = floatval($parcentChangesMonthly);
        $savePercentChanges->percent_changes_three_month = floatval($parcentChangesThreeMonth);
        $savePercentChanges->save();
    }

    protected function getCryptocurrenyWithQuotes(
        string $quoteSymbol = self::QUOTES_CURRENCY_SYMBOL,
        int $limit = self::TOP_LIMIT
    ): Collection {
        $cryptocurrencies = Cryptocurrency::query()
            ->select(
                '*',
                DB::raw('IF(market_cap_order IS NOT NULL, market_cap_order, 1000000) market_cap_order')
            )
            ->limit($limit)
            ->offset(0)
            ->orderBy('market_cap_order')
            ->with([
                'quotes' => function ($q) use ($quoteSymbol) {
                    $q
                        ->where('symbol', $quoteSymbol)
                        ->select(
                            'cryptocurrency_id',
                            'symbol',
                            'price',
                            'volume_24h',
                            'percent_change_24h',
                            'percent_change_1h',
                            'percent_change_7d',
                            'market_cap',
                            'last_updated'
                        );
                }
            ])
            ->get();
        return $cryptocurrencies;
    }

    protected function combineCurrencyWithQuotesData(Collection $cryptocurrencies): array
    {
        $crypts = [];
        if ($cryptocurrencies->isEmpty()) {
            return $crypts;
        }
        foreach ($cryptocurrencies as $key => $cryptocurrency) {
            $quote = $cryptocurrency['quotes'][0];
            $crypts[] = [
                'top_number' => $key + 1,
                'symbol' => $cryptocurrency->symbol,
                'name' => $cryptocurrency->name,
                'logo' => URL::to('/') . $cryptocurrency->logo_2,
                'price' => round($quote->price, 2),
                'price_symbol' => self::PRICE_SYMBOL,
                'percent_change_24h' => round($quote->percent_change_24h, 2),
                'percent_change_24h_symbol' => self::PERCENT_CHANGE_SYMBOL,
                'increase' => ($quote->percent_change_24h >= 0) ? 1 : 0
            ];

        }
        return $crypts;
    }

    protected function getExchangesWithQuotes(string $quoteSymbol = self::QUOTES_CURRENCY_SYMBOL, int $limit = self::TOP_LIMIT)
    {
        $exchanges = Exchange::query()
            ->select('exchanges.*', 'exchange_quotes.volume_24h', 'exchange_quotes.percent_change_volume_24h')
            ->leftJoin('exchange_quotes', function ($join) use ($quoteSymbol) {
                $join
                    ->on('exchange_quotes.exchange_id', '=', 'exchanges.exchange_id')
                    ->where('symbol', $quoteSymbol);
            })
            ->orderBy('exchange_quotes.volume_24h', 'DESC')
            ->offset(0)
            ->limit($limit)
            ->get();
        return $exchanges;
    }

    protected function forrmatExchangesData(Collection $exchanges): array
    {
        $formattedData = [];
        foreach ($exchanges as $key => $exchange) {
            $formattedData[] = [
                'rank' => $key + 1,
                'name' => $exchange->name,
                'logo' => URL::to('/') . $exchange->logo_2,
                'volume_24h' => round($exchange->volume_24h, 2),
                'percent_volume_24h' => round($exchange->percent_change_volume_24h, 2),
                'increase' => ($exchange->percent_change_volume_24h >= 0) ? 1 : 0
            ];
        }
        return $formattedData;
    }

    public function getTickerCoinsBySymbols(array $symbols, string $quoteSymbol = self::QUOTES_CURRENCY_SYMBOL): array
    {
        $coins = Cryptocurrency::query()
            ->orderBy('market_cap_order')
            ->with([
                'quotes' => function ($q) use ($quoteSymbol) {
                    $q->where('symbol', $quoteSymbol);
                }
            ])
            ->whereIn('symbol', $symbols)
            ->get();
        $tickerCoinsData = [];
        foreach ($coins as $coin) {
            if (!isset($coin->quotes[0])) {
                continue;
            }
            $quote = $coin->quotes[0];
            $tickerCoinsData[] = [
                'symbol' => $coin->symbol,
                'name' => $coin->name,
                'price' => round($quote->price, 3),
                'price_symbol' => self::PRICE_SYMBOL,
                'percent_change_24h' => round($quote->percent_change_24h, 3),
                'percent_change_24h_symbol' => self::PERCENT_CHANGE_SYMBOL,
                'increase' => ($quote->percent_change_24h >= 0) ? 1 : 0
            ];

        }
        return $tickerCoinsData;
    }

    public function getFavoriteCoinsBySymbols(array $symbols): array
    {
        $coins = Cryptocurrency::query()
            ->whereIn('symbol', $symbols)
            ->with(['quotes' => function ($query) {
                $query->where('symbol', self::DEFAULT_COINS_QUOTES_SYMBOL);
            }])
            ->get();

        $favoriteCoinsData = [];
        foreach ($coins as $coin) {
            if (!isset($coin->quotes[0])) {
                continue;
            }
            $quote = $coin->quotes[0];
            $favoriteCoinsData[] = [
                'logo' => URL::to('/') . $coin->logo_2,
                'symbol' => $coin->symbol,
                'name' => $coin->name,
                'price' => round($quote->price, 3),
                'percent_price' => round($quote->percent_change_24h, 2),
                'volume_24' => round($quote->volume_24h, 3)
            ];
        }
        return $favoriteCoinsData;
    }

    public function getCryptoCurrencyHistoryDataBySymbol(string $symbol)
    {
        $cryptoModel = Cryptocurrency::query()->where('symbol', $symbol);
        if ($cryptoModel->count() === 0) {
            return [];
        }
        $historicalData = $this->getCachedCurrencyHistoryData($cryptoModel, $symbol);
        return $historicalData;
    }

    public function getCryptoCurrencyHistoryDataBySymbolAndSlug(string $symbol, $slug)
    {
        $cryptoModel = Cryptocurrency::query()->when($slug, function ($query) use ($symbol, $slug) {
                            return $query->where( 'slug',  $slug )
                                     ->where( 'symbol', $symbol );
                        }, function ($query) use ($symbol) {
                            return $query->where( 'symbol', $symbol );
                        });
        if ($cryptoModel->count() === 0) {
            return [];
        }
        $historicalData = $this->getCachedCurrencyHistoryData($cryptoModel, $symbol);
        return $historicalData;
    }

    public function getCryptocurrencies($sort, $sortDir, $limit, $autocomplete = '')
    {
        $GLOBALS['autocomplete'] = $autocomplete;
        $cryptocurrencies = Cryptocurrency::leftJoin('quotes', 'quotes.cryptocurrency_id', '=',
            'cryptocurrencies.cryptocurrency_id')
            ->select(
                'cryptocurrencies.name AS name',
                'cryptocurrencies.symbol AS symbol',
                'cryptocurrencies.circulating_supply AS circulating_supply',
                'logo_2 AS logo',
                DB::raw('circulating_supply / max_supply * 100 as circulating_supply_percent'),
                'quotes.price AS price',
                'quotes.volume_24h AS volume_24h',
                'quotes.percent_change_24h AS percent_change_24h',
                'quotes.percent_change_1h AS percent_change_1h',
                'quotes.percent_change_7d AS percent_change_7d',
                'quotes.market_cap AS market_cap'
            )
            ->orderBy($sort, $sortDir)
            ->where('quotes.symbol', 'USD')
            ->where(function ($query) {
                $query->orWhere('cryptocurrencies.name', 'LIKE', $GLOBALS['autocomplete'] . '%')
                    ->orWhere('cryptocurrencies.symbol', 'LIKE', $GLOBALS['autocomplete'] . '%');
            })
            ->paginate($limit);


        $koeficent = $cryptocurrencies->avg('market_cap');
        $cryptocurrencies = $cryptocurrencies->toArray();


        foreach ($cryptocurrencies['data'] as $key => $cryptocurrency) {
            $cryptocurrencies['data'][$key]['rank'] = $key + 1;
            $cryptocurrencies['data'][$key]['circulating_supply'] = floatval($cryptocurrency['circulating_supply']);
            $cryptocurrencies['data'][$key]['logo'] = URL::to('/') . $cryptocurrency['logo'];
            $cryptocurrencies['data'][$key]['circulating_supply_percent'] = floatval($cryptocurrency['circulating_supply_percent']);
            $cryptocurrencies['data'][$key]['price'] = floatval($cryptocurrency['price']);
            $cryptocurrencies['data'][$key]['volume_24h'] = floatval($cryptocurrency['volume_24h']);
            $cryptocurrencies['data'][$key]['percent_change_24h'] = floatval($cryptocurrency['percent_change_24h']);
            $cryptocurrencies['data'][$key]['percent_change_1h'] = floatval($cryptocurrency['percent_change_1h']);
            $cryptocurrencies['data'][$key]['percent_change_7d'] = floatval($cryptocurrency['percent_change_7d']);
            $cryptocurrencies['data'][$key]['market_cap'] = floatval($cryptocurrency['market_cap']);
            $cryptocurrencies['data'][$key]['increase'] = ($cryptocurrency['percent_change_24h'] >= 0) ? 1 : 0;
            $cryptocurrencies['data'][$key]['Wx'] = ($koeficent) ? floatval($cryptocurrency['market_cap'] / $koeficent) : 0;
        }

        return $cryptocurrencies;

    }
    public function getCryptocurrenciesByAutocomplete($autocomplete, $limit)
    {
        $cryptocurrencies =
        Cryptocurrency::leftJoin('quotes', 'quotes.cryptocurrency_id', '=',
            'cryptocurrencies.cryptocurrency_id')
        ->select(
            'cryptocurrencies.cryptocurrency_id AS id',
            'cryptocurrencies.name AS name',
            'cryptocurrencies.symbol AS symbol',
            'quotes.price AS price')
        ->where(function($query) use ($autocomplete)
            {
                $query->orWhere('cryptocurrencies.name', 'LIKE', $autocomplete . '%')
                      ->orWhere('cryptocurrencies.symbol', 'LIKE', $autocomplete . '%');
            })
        ->orderBy('cryptocurrencies.cryptocurrency_id', 'asc')
        ->paginate($limit)
        ->toArray();


        return $cryptocurrencies;
    }
    public function getCryptocurrenciesWithFilters($sort, $sortDir, $limit, $autocomplete = '', $market_cap_filter, $price_filter, $volume24_filter) {
        $market_cap = $this->getMarketCap($market_cap_filter);
        $price = $this->getPrice($price_filter);
        $volume24 = $this->getVolume24($volume24_filter);
        $now_page = (isset($_GET['page'])) ? $_GET['page'] : 1;
        $rank_start= ((int)$now_page - 1) * (int)$limit;
        $cryptocurrencies = Cryptocurrency::leftJoin('quotes', 'quotes.cryptocurrency_id', '=',
            'cryptocurrencies.cryptocurrency_id')
            ->select(
                'cryptocurrencies.name AS name',
                'cryptocurrencies.symbol AS symbol',
                'cryptocurrencies.slug AS slug',
                'cryptocurrencies.circulating_supply AS circulating_supply',
                'cryptocurrencies.ath AS max_price',
                'logo_2 AS logo',
                DB::raw('circulating_supply / max_supply * 100 as circulating_supply_percent'),
                'quotes.price AS price',
                'quotes.volume_24h AS volume_24h',
                'quotes.percent_change_24h AS percent_change_24h',
                'quotes.percent_change_1h AS percent_change_1h',
                'quotes.percent_change_7d AS percent_change_7d',
                'quotes.market_cap AS market_cap'
            )
            ->orderBy($sort, $sortDir)
            ->where('quotes.symbol', 'USD')
            ->when($market_cap, function ($query, $market_cap) {
                    return $query->whereBetween('market_cap',$market_cap);
                })
            ->when($price, function ($query, $price) {
                    return $query->whereBetween('price',$price);
                })
            ->when($volume24, function ($query, $volume24) {
                    return $query->whereBetween('volume_24h',$volume24);
                })


            ->where(function($query) use ($autocomplete)
            {
                $query->orWhere('cryptocurrencies.name', 'LIKE', $autocomplete . '%')
                      ->orWhere('cryptocurrencies.symbol', 'LIKE', $autocomplete . '%');
            })
            ->paginate($limit);


        $koeficent = $cryptocurrencies->avg('market_cap');
        $cryptocurrencies = $cryptocurrencies->toArray();

        foreach ($cryptocurrencies['data'] as $key => $cryptocurrency) {
        // dd($cryptocurrency);

            $cryptocurrencies['data'][$key]['rank'] = $rank_start + $key + 1;
            $cryptocurrencies['data'][$key]['circulating_supply'] =  $this->roundData($cryptocurrency['circulating_supply']);
            $cryptocurrencies['data'][$key]['logo'] = URL::to('/') . $cryptocurrency['logo'];
            $cryptocurrencies['data'][$key]['circulating_supply_percent'] =  $this->roundData($cryptocurrency['circulating_supply_percent']);
            $cryptocurrencies['data'][$key]['price'] = $this->roundData($cryptocurrency['price']);
            $cryptocurrencies['data'][$key]['volume_24h'] = $this->roundData($cryptocurrency['volume_24h']);
            $cryptocurrencies['data'][$key]['percent_change_24h'] = $this->roundData($cryptocurrency['percent_change_24h']);
            $cryptocurrencies['data'][$key]['percent_change_1h'] = $this->roundData($cryptocurrency['percent_change_1h']);
            $cryptocurrencies['data'][$key]['percent_change_7d'] = $this->roundData($cryptocurrency['percent_change_7d']);
            $cryptocurrencies['data'][$key]['market_cap'] = $this->roundData($cryptocurrency['market_cap']);
            $cryptocurrencies['data'][$key]['increase'] = ($cryptocurrency['percent_change_24h'] >= 0) ? 1 : 0;
            $cryptocurrencies['data'][$key]['Wx'] = ($koeficent) ? $this->roundData($cryptocurrency['market_cap'] / $koeficent) : 0;

            if (($cryptocurrency['max_price'] == 0) || ($cryptocurrency['max_price'] < $cryptocurrency['price'])) {
                $atn = $cryptocurrency['price'];
            }else{
                $atn = $cryptocurrency['max_price'];
            }
            $atn = $this->roundData($atn);
            $cryptocurrencies['data'][$key]['atn_percent'] = round($cryptocurrency['price'] / $atn * 100, 2);
            $cryptocurrencies['data'][$key]['max_price'] = floatval($atn);
        }

        return $cryptocurrencies;

    }
    protected function getDataForExchangeBySymbol($exchange, string $base, string $quote, int $base_id, int $quote_id, string $interval, string $className, string $timeEnd, string $db_data_last_timestamp)
    {

        $formattedInsertData = [];
        $pause = 0;
        try {
            $now = (int) (\Carbon\Carbon::now()->timestamp . '000');
            if ($db_data_last_timestamp) {
                $since = $db_data_last_timestamp;

            }else{
                $since_db = $className::select('timestamp', 'updated_at')
                    ->where('base_id', $base_id)
                    ->where('quote_id', $quote_id)
                    ->orderBy('timestamp', 'DESC')
                    ->first();
            }
            if (isset($since_db)) {
                if ((int)(strtotime($since_db->updated_at)) > (int)(strtotime($timeEnd))) {
                    return false;
                }
                if (($now - ((int)(strtotime($since_db->updated_at) . '000'))) < 10000) {
                    return false;
                }
                $since = ((int)(strtotime($since_db->timestamp) . '000') );
            }elseif (!isset($since)) {
                if (($interval === '1m') || ($interval === '5m')) {
                    $since = (int) (\Carbon\Carbon::now()->subDay(3)->timestamp . '000');
                }else{
                    $since = (int) (\Carbon\Carbon::now()->subMonth(24)->timestamp . '000');
                }
            }elseif ($since) {
                $since = (int)(strtotime($db_data_last_timestamp) . '000');
            }
            $symbol = strtoupper($base) . '/' . strtoupper($quote);
            $fullOhlyData = [];
            $count = 0;


            for ($startFrom = $since; $startFrom < $now; $startFrom = end($fullOhlyData)[0]) {
                try {
                    if (strpos($className, 'okex') !== false) {
                        $ohly = $exchange->fetchOHLCV($symbol, $interval, $startFrom);

                    }else{

                       $ohly = $exchange->fetchOHLCV($symbol, $interval, $startFrom, $exchange->rateLimit);
                    }
                } catch (\Exception $e) {
                    // $this->info($e->getMessage());
                    break;
                    return false;
                }
                $fullOhlyData = array_merge_recursive($fullOhlyData, $ohly);

                if (count($ohly) < $exchange->rateLimit) {
                    break;
                }
            }

            foreach ($fullOhlyData as $datum) {

                $formattedInsertData[] = [
                    'base_id' => $base_id,
                    'quote_id' => $quote_id,
                    'timestamp' => date('Y-m-d H:i:s', substr($datum[0], 0, 10)),
                    'open' => $datum[1],
                    'high' => $datum[2],
                    'low' => $datum[3],
                    'close' => $datum[4],
                    'volume' => $datum[5],
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }

        } catch (\Exception $e) {
            // $this->error($e->getMessage());
        }
        return $formattedInsertData;
    }
    public function getCcxtOjlcvData($base, $quote, $timeStart, $timeEnd, $interval, $exchangeName)
    {

        $className = 'App\OhlcvModels\ohlcv_' . $exchangeName . '_' . $interval;
        $pair = MarketPair::getPairIdStrong($base, $quote);
        if ($pair === false) {
            return [];
        }

        $base_id = $pair[1];
        $quote_id = $pair[2];
        $interval_ccxt = self::INTERVAL_CCXT[$interval];

        $base_id = $pair[1];
        $quote_id = $pair[2];
        $data_db = $className::where('base_id', $base_id)
            ->where('quote_id', $quote_id)
            ->whereBetween('timestamp', [$timeStart, $timeEnd])
            ->get()
            ->groupBy('timestamp')
            ->toArray();
        $max_date_db = $className::select('timestamp')
            ->where('base_id', $base_id)
            ->where('quote_id', $quote_id)
            ->orderBy('timestamp', 'desc')
            ->first();
        if ($max_date_db) {
            $max_date = $max_date_db->timestamp;
        }else{
            $max_date = date('Y-m-d', strtotime('-5 year'));
        }
        $data = [];
        foreach ($data_db as $key => $value) {
            $data[] = $value[0];
        }
        $db_data_last_timestamp = ($data) ? $data[count($data) -1]['timestamp'] : false;
        if (($exchangeName != 'bitfinex') && ($exchangeName != 'cmc')) {
            if ($exchangeName != 'huobi_pro') {
                $exchangeClass = "\\ccxt\\" . $exchangeName;
            }else{
                $exchangeClass = "\\ccxt\\huobipro";
            }
            if ((int)strtotime($timeEnd) > (int)strtotime($max_date)) {
                $exchange = new $exchangeClass();
                $formattedData = $this->getDataForExchangeBySymbol($exchange, $base, $quote, $base_id, $quote_id, $interval_ccxt, $className, $timeEnd, $db_data_last_timestamp);
                if (!(($exchangeName == 'binance') && ($interval_ccxt == '5m'))) {
                    if (($formattedData !== false) && (count($formattedData) !== 0)) {

                        $className::updateOrCreate(['base_id' => $formattedData[0]['base_id'], 'quote_id' => $formattedData[0]['quote_id'], 'timestamp' => $formattedData[0]['timestamp']], $formattedData[0]);
                        unset($formattedData[0]);
                        if (count($formattedData) !== 0) {
                            $className::insert($formattedData);
                        }
                    }
                }
            }
        }




        // if (($exchangeName == 'binance') && ($interval_ccxt == '5m')) {
        if (isset($formattedData)) {
            if ((count($data) > 0) && (count($formattedData) > 0)) {
                if (isset( $formattedData[0])) {
                    if ($data[count($data) - 1]['timestamp'] == $formattedData[0]['timestamp']) {
                        unset($data[count($data) - 1]);
                    }
                }
            }
            
            $data = array_merge($data, $formattedData);
        }

        // }

        if (count($data) > 0) {
            $returnData = [];
            $returnData['s'] = "ok";
            $returnData['c'] = [];
            $returnData['h'] = [];
            $returnData['l'] = [];
            $returnData['o'] = [];
            $returnData['t'] = [];
            $returnData['v'] = [];

            foreach ($data as $item) {
                $returnData['c'][] = floatval($item['close']);
                $returnData['h'][] = floatval($item['high']);
                $returnData['l'][] = floatval($item['low']);
                $returnData['o'][] = floatval($item['open']);
                $returnData['t'][] = strtotime($item['timestamp']);
                $returnData['v'][] = floatval($item['volume']);
            }

        } else {
            $returnData = [];
            $returnData['s'] = "no_data";
        }
        return $returnData;

    }

    public function getCcxtExchanges()
    {
        $exchanges = [];
        foreach (self::EXCHANGES_TRADINGVIEW as $exchange) {
            $exchanges[] = [
                'name' => $exchange,
                'desc' => $exchange,
                'value' => ($exchange != 'Coinmarketcap') ? strtolower(str_replace(' ', '_', $exchange)) : 'cmc',
            ];
        }
        $name = array_column($exchanges, 'name');
        array_multisort($name, SORT_ASC, $exchanges);
        $all_exchanges[] = [
                'name' => 'All Exchanges',
                'desc' => '',
                'value' => '',
            ];
        return array_merge($all_exchanges, $exchanges);
    }

    public function getSymbolResolution($base_id, $quote_id, $exchange)
    {
        $db_data = OhlcvPair::where('base_id', $base_id)
                            ->where('quote_id', $quote_id)
                            ->where('exchange', $exchange)
                            ->select(self::TIME_FRAMES)
                            ->first()
                            ->toArray();
        $resolution = [];
        foreach ($db_data as $key => $value) {
            if ($value) {
                $resolution[] = array_search($key, self::STOCK_INTERVALS);
            }
        }
        return $resolution;
    }
    public function getSearchData($query, $query2, $limit, $exchange)
    {


        if ($exchange == '') {
            $pairs_array = $this->getSearchByAllExchanges($query, $query2, $limit);
        }else{
            $pairs_array = $this->getSearchByExchange($query, $query2, $limit, $exchange);
        }
        foreach ($pairs_array as $key1 => $value1) {
            $pairs_array[$key1]['length'] = strlen($value1['base']) + strlen($value1['quote']);
        }
        $base_id = array_column($pairs_array, 'base_id');
        $length = array_column($pairs_array, 'length');
        array_multisort($base_id, SORT_ASC, $length, SORT_ASC, $pairs_array);
        $return_pairs_array = [];
        foreach ($pairs_array as $key => $value) {
            $return_pairs_array[] = [
                'description' => $value['base_name'] . '/' . $value['quote_name'],
                'exchange' => $value['exchange'],
                'symbol' => $value['base'] . '/' . $value['quote'],
                'type' => 'bitcoin',
                'ticker' => $value['exchange'] . ':' . $value['base'] . '/' . $value['quote'],
                'full_name' => $value['exchange'] . ':' . $value['base'] . '/' . $value['quote']
            ];
        }
        return $return_pairs_array;

    }
    public function getSearchByExchange($query, $query2, $limit, $exchange)
    {
        $pairs_array = OhlcvPair::leftJoin('cryptocurrencies as c1',   'base_id' , '=', 'c1.cryptocurrency_id' )
            ->leftJoin('cryptocurrencies as c2',  'quote_id' , '=', 'c2.cryptocurrency_id' )
            ->where(function ($q) use ($query, $query2, $exchange) {
                $q->where('c1.symbol', 'like', $query . '%')
                  ->where('c2.symbol', 'like', $query2 . '%')
                  ->where('exchange', $exchange)
                  ->where(function ($q){
                        $q->where('1m', '=', 1)
                          ->orWhere('5m', '=', 1)
                          ->orWhere('15m', '=', 1)
                          ->orWhere('30m', '=', 1)
                          ->orWhere('1h', '=', 1)
                          ->orWhere('4h', '=', 1)
                          ->orWhere('12h', '=', 1)
                          ->orWhere('1d', '=', 1)
                          ->orWhere('1w', '=', 1)
                          ->orWhere('30d', '=', 1);
                    });
            })
            ->orWhere(function ($q) use ($query, $query2, $exchange) {
                    $q->where('c1.name', 'like', $query . '%')
                    ->where('c2.name', 'like', $query2 . '%')
                    ->where('exchange', $exchange)
                    ->where(function ($q){
                        $q->where('1m', '=', 1)
                          ->orWhere('5m', '=', 1)
                          ->orWhere('15m', '=', 1)
                          ->orWhere('30m', '=', 1)
                          ->orWhere('1h', '=', 1)
                          ->orWhere('4h', '=', 1)
                          ->orWhere('12h', '=', 1)
                          ->orWhere('1d', '=', 1)
                          ->orWhere('1w', '=', 1)
                          ->orWhere('30d', '=', 1);
                    });
            })
            ->select(
                'c1.cryptocurrency_id as base_id',
                'c2.cryptocurrency_id as quote_id',
                'c1.symbol as base',
                'c2.symbol as quote',
                'c1.name as base_name',
                'c2.name as quote_name',
                'exchange'
            )
            ->orderBy('c1.id', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
        return $pairs_array;
    }

    public function getSearchByAllExchanges($query, $query2, $limit){
        // dd(self::EXCHANGES_TRADINGVIEW_NAME);
        $pairs_array = OhlcvPair::leftJoin('cryptocurrencies as c1',   'base_id' , '=', 'c1.cryptocurrency_id' )
                ->leftJoin('cryptocurrencies as c2',  'quote_id' , '=', 'c2.cryptocurrency_id' )
                ->where(function ($q) use ($query, $query2) {
                    $q->where('c1.symbol', 'like', $query . '%')
                      ->where('c2.symbol', 'like', $query2 . '%')
                      ->whereIn('exchange', self::EXCHANGES_TRADINGVIEW_NAME)
                      ->where(function ($q){
                        $q->where('1m', '=', 1)
                              ->orWhere('5m', '=', 1)
                              ->orWhere('15m', '=', 1)
                              ->orWhere('30m', '=', 1)
                              ->orWhere('1h', '=', 1)
                              ->orWhere('4h', '=', 1)
                              ->orWhere('12h', '=', 1)
                              ->orWhere('1d', '=', 1)
                              ->orWhere('1w', '=', 1)
                              ->orWhere('30d', '=', 1);
                        });

                })
                ->orWhere(function ($q) use ($query, $query2) {
                        $q->where('c1.name', 'like', $query . '%')
                        ->where('c2.name', 'like', $query2 . '%')
                        ->whereIn('exchange', self::EXCHANGES_TRADINGVIEW_NAME)
                        ->where(function ($q){
                        $q->where('1m', '=', 1)
                              ->orWhere('5m', '=', 1)
                              ->orWhere('15m', '=', 1)
                              ->orWhere('30m', '=', 1)
                              ->orWhere('1h', '=', 1)
                              ->orWhere('4h', '=', 1)
                              ->orWhere('12h', '=', 1)
                              ->orWhere('1d', '=', 1)
                              ->orWhere('1w', '=', 1)
                              ->orWhere('30d', '=', 1);
                        });
                })
                ->select(
                    'c1.cryptocurrency_id as base_id',
                    'c2.cryptocurrency_id as quote_id',
                    'c1.symbol as base',
                    'c2.symbol as quote',
                    'c1.name as base_name',
                    'c2.name as quote_name',
                    'exchange'
                )
                ->orderBy('c1.id', 'asc')
                ->limit($limit)
                ->get()
                ->toArray();
        return $pairs_array;
    }
    public function getSearchByClassName($className, $tableName, $query, $query2, $limit, $exchange)
    {
        $pairs = $className::leftJoin('cryptocurrencies as c1',  $tableName . '.base_id' , '=', 'c1.cryptocurrency_id' )
            ->leftJoin('cryptocurrencies as c2',  $tableName . '.quote_id' , '=', 'c2.cryptocurrency_id' )
            ->whereDate($tableName . '.timestamp',  date('Y-m-d', strtotime('-1 month')))
            ->where(function ($q) use ($query, $query2) {
                $q->where('c1.symbol', 'like', $query . '%')
                  ->where('c2.symbol', 'like', $query2 . '%');
            })
            ->orWhere(function ($q) use ($query, $query2, $tableName) {
                    $q->where('c1.name', 'like', $query . '%')
                    ->where('c2.name', 'like', $query2 . '%')
                    ->whereDate($tableName . '.timestamp',  date('Y-m-d', strtotime('-1 month')));
            })
            ->select(
                'c1.cryptocurrency_id as base_id',
                'c2.cryptocurrency_id as quote_id',
                'c1.symbol as base',
                'c2.symbol as quote',
                'c1.name as base_name',
                'c2.name as quote_name',
                $tableName. '.volume as volume',
                $tableName. '.timestamp as timestamp'
            )
            ->orderBy('c1.id', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();

        foreach ($pairs as $key1 => $value1) {
            $pairs[$key1]['market'] = $exchange;
            $pairs[$key1]['length'] = strlen($value1['base']) + strlen($value1['quote']);

        }

        foreach ($pairs as $key1 => $value1) {
            foreach ($pairs as $key2 => $value2) {
                if (($value1['base'] == $value2['base']) && ($value1['quote'] == $value2['quote']) && ($key2 !== $key1)) {
                    unset($pairs[$key1]);
                }
            }
        }
        return $pairs;
    }
    public function getCryptocurrencyWithCoefficients($symbol, $convert, $timeStart, $timeEnd, $interval, $coeffName)
    {
        $convert = $this->getIdCurrencyByTicker($convert);
        $cryptocurrencyWithCoefficients = $this->getCurrencyWithCoefficients($symbol, $timeStart, $timeEnd, $interval, $convert);
        if (!$cryptocurrencyWithCoefficients) {
            return false;
        }

        $returnData = [];
        $returnData['s'] = "ok";
        $returnData['c'] = [];
        $returnData['h'] = [];
        $returnData['l'] = [];
        $returnData['o'] = [];
        $returnData['t'] = [];
        $returnData['v'] = [];
        
        foreach ($cryptocurrencyWithCoefficients->coefficients as $coefficient) {
            $returnData['c'][] = floatval($coefficient[$coeffName]);
            $returnData['h'][] = floatval($coefficient[$coeffName]);
            $returnData['l'][] = floatval($coefficient[$coeffName]);
            $returnData['o'][] = floatval($coefficient[$coeffName]);
            $returnData['t'][] = strtotime($coefficient->c_date);
            $returnData['v'][] = floatval($coefficient[$coeffName]);
        }

        return $returnData;
    }

    public function getCryptocurrencyWithOhlcv($cIds, $dateFrom, $dateTo)
    {
        $quoteId = $this->quoteId()->cryptocurrency_id;

        $cryptocurrenciesWithQuotes = Cryptocurrency::select([
            '*',
            DB::raw('IF(`market_cap_order` IS NOT NULL, `market_cap_order`, 1000000) `market_cap_order`')
        ])
            ->with([
                'ohlcvQuotesDaily' => function ($q) use ($dateFrom, $dateTo, $quoteId) {
                    $q->select('*')

                        ->where('quote_id', $quoteId)
                        ->whereDate('timestamp', '>=', $dateFrom)
                        ->whereDate('timestamp', '<=', $dateTo)
                        ->orderBy('timestamp');
                }
            ])->whereIn('cryptocurrency_id', $cIds)->limit(200)->orderBy('market_cap_order')->get();
        return $cryptocurrenciesWithQuotes;
    }

    public function getCryptocurrencyWithHourlyOhlcv(array $cIds, string $date)
    {
        $quoteId = $this->quoteId()->cryptocurrency_id;
        $cryptocurrenciesWithQuotes = Cryptocurrency::select([
            '*',
            DB::raw('IF(`market_cap_order` IS NOT NULL, `market_cap_order`, 1000000) `market_cap_order`')
        ])
            ->with([
                'ohlcvQuotesHourly' => function ($q) use ($cIds, $date, $quoteId) {
                    $q->select('base_id', 'quote_id', 'open', 'close')
                        ->where('base_id', $cIds)
                        ->whereDate('timestamp', $date)
                        ->where('quote_id', $quoteId)
                        ->orderBy('timestamp');
                }
            ])->whereIn('cryptocurrency_id', $cIds)->orderBy('market_cap_order')->get();
        return $cryptocurrenciesWithQuotes;
    }

    public function getMonthlyQuotes($cryptocurrency, $lastMonth)
    {
        $monthlyQuotes = ohlcv_cmc_1d::query()
            ->select('close')
            ->where('quote_id', $this->quoteId()->cryptocurrency_id)
            ->where('base_id', $cryptocurrency->cryptocurrency_id)
            ->whereMonth('timestamp', '=', date('m', $lastMonth))
            ->whereYear('timestamp', '=', date('Y', $lastMonth))->get();
        return $monthlyQuotes;
    }

    public function getDailyQuotes($cryptocurrency, $date)
    {
        $xDaily = ohlcv_cmc_1d::query()
            ->select('close')
            ->where('quote_id', $this->quoteId()->cryptocurrency_id)
            ->where('base_id', $cryptocurrency->cryptocurrency_id)
            ->whereDate('timestamp', $date)->first();
        return $xDaily;
    }

    public function getChartDataBySymbolAndType(
        $symbol,
        string $chartType,
        string $periodStartDate,
        string $periodEndDate,
        string $step,
        array $data
    ) {
        $data = [];

        $cryptocurrencyWithCoefficients = $this->getCurrencyWithCoefficients($symbol, $periodStartDate, $periodEndDate,
            $step, $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL));

        if (!$cryptocurrencyWithCoefficients) {
            return [];
        }

        foreach ($cryptocurrencyWithCoefficients->coefficients as $coefficient) {
            $data[] = [
                'time' => $coefficient->c_date,
                'value' => $coefficient[$chartType]
            ];
        }

        return $data;
    }

    protected function getCachedCurrencyHistoryData(Builder $cryptoModel, string $symbol, bool $cache = false): array
    {
        $cacheKeyPrefix = strtolower($symbol);
        if ($cache) {
            $historicalData = Cache::remember($cacheKeyPrefix . '_cryptocurrency_history', 60,
                function () use ($cryptoModel) {
                    return $this->getRawCurrencyHistoryData($cryptoModel);
                });
        } else {
            $historicalData = $this->getRawCurrencyHistoryData($cryptoModel);
        }
        return $historicalData;

    }

    protected function getRawCurrencyHistoryData(Builder $cryptoModel): array
    {

        $cryptoData = $cryptoModel->first();
        $quote_id = $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL);
        $historicalData = [];
        $onlcvQuotes = ohlcv_cmc_1d::where('base_id', $cryptoData->cryptocurrency_id)->where('quote_id', $quote_id)->orderBy('timestamp', 'desc')->get();
        foreach ($onlcvQuotes as $quote) {
            $historicalData[] = [
                'date' => date('d.m.Y', strtotime($quote->timestamp)),
                'open' => $this->roundData($quote->open),
                'high' => $this->roundData($quote->high),
                'low' => $this->roundData($quote->low),
                'close' => $this->roundData($quote->close),
                'market_cap' => $this->roundData($quote->market_cap)
            ];
        }
        return $historicalData;
    }
    public function roundData($number)
    {
        $number = (float)$number;
        if (abs($number) >= 1) {
            return round($number, 2);
        }
        elseif (abs($number) >= 0.00001) {
            return round($number, 5);
        }
        else
        {
            $mnoj = 0;
            $num = $number;
            while (strpos($num, 'E')) {
                $num = $number * pow(10, $mnoj);
                $mnoj++;
            }
            $str = str_replace('.', '', (string)floatval($num));
            for ($i=0; $i < strlen($str) ; $i++) {
                if (substr($str, $i,1) != '0') {
                     // return number_format(round($number, $i + $mnoj - 1), $i + $mnoj - 1, '.', ',');
                     return round($number, $i + $mnoj - 1);
                 }
            }
        }
    }
    public function getCoinsCompareData(
        $coins,
        string $periodStartDate,
        string $periodEndDate,
        array $data
    ) {
        if (empty($coins)) {
            throw new EmptyEntityListException('Coins list can\'t by empty');
        }
        $coins = explode(',', $coins);
        $existCoins = Cryptocurrency::whereIn('symbol', $coins)->get();
        if ($existCoins->isEmpty()) {
            throw new EntityNotFoundException(Cryptocurrency::class, implode(',', $coins));
        }
        if ($existCoins->count() > 10) {
            throw new CoinsLimitException('The number of coins exceeded the limit 10');
        }

        $filtersFront = $this->getFrontCoinFilters($existCoins);
        $cryptocrrencyIds = array_keys($filtersFront);
        if ($periodStartDate && $periodEndDate) {
            $daysCount = (int)floor(((int)strtotime($periodEndDate) - (int)strtotime($periodStartDate))/3600/24);
        }else{
            $daysCount = 365;
        }
        $end_date_data = $this->getOhlcvFirstDataPeriodByIds($cryptocrrencyIds, $daysCount);
        $lastDateData = $this->getOhlcvLastDataPeriodByIds($cryptocrrencyIds, $periodStartDate, $periodEndDate);
        $periodStartDate = ($periodStartDate) ? $periodStartDate : $lastDateData;
        $periodEndDate = ($periodEndDate) ? $periodEndDate : $end_date_data;


        $data['filters_front']['crypto_items'] = array_values($filtersFront);

        // $periodStartDate = $this->getStartDateAsTimestamp($periodStartDate, $stepTimestamp, $lastDateData);
        // $periodEndDate = $this->getEndDateAsTimestamp($periodEndDate, $end_date_data);
        // $periodEndDate = $periodEndDate + $stepTimestamp;

        $data['filters']['first_date_data'] = date('Y-m-d', strtotime($lastDateData));
        $data['filters']['end_date_data'] = date('Y-m-d', strtotime($end_date_data));


        $daysCount = (int)floor(((int)strtotime($periodEndDate) - (int)strtotime($periodStartDate))/3600/24);
        $dateIntervalFormat = $this->getDateIntervalFormatByCountDays($daysCount);
        $ohlcvData = $this->getOhlcvDataPeriodByIds($cryptocrrencyIds, $periodStartDate, $periodEndDate, $daysCount);
        $dateIntervalFormat = str_replace('%', '' ,$dateIntervalFormat);
        $coinsInformation = [];
        $groupOhlcvData = $ohlcvData->groupBy('timestamp');
        foreach ($groupOhlcvData as $date => $datum) {
            $values = [];
            foreach ($datum as $coins) {
                if (array_key_exists($coins->base_id, $values)) {
                    continue;
                }
                $values[$coins->base_id] = [
                    'value' => round($coins->close, 2),
                    'crypto_id' => $filtersFront[$coins->base_id]['id']
                ];
            }
            $coinsInformation[] = [
                'time' => date($dateIntervalFormat, strtotime($date)),
                'value' => array_values($values)
            ];

        }
        // dd(count($coinsInformation));
        $return_array = [];
        if (($daysCount > 7) && ($daysCount < 30)) {
            for ($i=0; $i < count($coinsInformation); $i++) {
                if ($i % 2 === 0) {
                    $return_array[] = $coinsInformation[$i];
                }
            }
            $coinsInformation = $return_array;
        }
        $data['filters']['period_date_start'] = date('Y-m-d', strtotime($periodStartDate));
        $data['filters']['period_date_end'] = date('Y-m-d', strtotime($periodEndDate));
        $data['data'] = $coinsInformation;
        return $data;
    }

    protected function getCurrencyWithCoefficients($symbol, $timeStart, $timeEnd, $interval, $convert)
    {

        $cryptocurrencyWithCoefficients = Cryptocurrency::with([
            'coefficients' => function ($q) use ($timeStart, $timeEnd, $interval, $convert) {
                $q->select('cryptocurrency_id', 'volatility', 'sharpe', 'alpha', 'beta', 'sortino', 'c_date')
                    ->whereBetween('c_date', [$timeStart, $timeEnd])
                    ->where('interval', $interval)
                    ->where('convert', $convert)
                    ->orderBy('c_date');
            }
        ])->where('symbol', $symbol)->first();
        if (!$cryptocurrencyWithCoefficients || $cryptocurrencyWithCoefficients->coefficients->count() === 0) {
            return [];
        }

        return $cryptocurrencyWithCoefficients;
    }
    public function convertIntervalForCoefficients($interval)
    {

        switch ($interval) {
            case self::DAILY_HISTORY_INTERVAL_VALUE:
                return 'daily';
                break;
            case self::WEEKLY_HISTORY_INTERVAL_VALUE:
                return 'weekly';
                break;
            case self::HOURLY_HISTORY_INTERVAL_VALUE:
                return 'hourly';
                break;
            case self::MONTHLY_HISTORY_INTERVAL_VALUE:
                return 'monthly';
                break;

            default:
                return 'weekly';
                break;
        }
    }
    public function getLastDateCoefficientsBySimbol($symbol, $interval)
    {
        $last = Coefficient::leftJoin('cryptocurrencies as c1', 'c1.cryptocurrency_id', '=', 'coefficients.cryptocurrency_id')
            ->where('coefficients.interval', $interval)
            ->where('c1.symbol', $symbol)
            ->select('coefficients.c_date as date')
            ->orderBy('coefficients.c_date', 'asc')
            ->first();
            return ($last) ? date('Y-m-d',strtotime($last->date)) : null;
    }
    public function getFirstDateCoefficientsBySimbol($symbol, $interval)
    {
        $last = Coefficient::leftJoin('cryptocurrencies as c1', 'c1.cryptocurrency_id', '=', 'coefficients.cryptocurrency_id')
            ->where('coefficients.interval', $interval)
            ->where('c1.symbol', $symbol)
            ->select('coefficients.c_date as date')
            ->orderBy('coefficients.c_date', 'desc')
            ->first();
            return ($last) ? date('Y-m-d',strtotime($last->date)) : null;
    }
    protected function getFrontCoinFilters(Collection $existCoins): array
    {
        $filtersFront = [];
        $coinsDataWithId = [];
        foreach ($existCoins as $key => $coin) {
            $coinsData = [
                'id' => $key + 1,
                'name' => $coin->symbol
            ];
            $filtersFront[$coin->cryptocurrency_id] = $coinsData;
        }
        return $filtersFront;
    }

    protected function getOhlcvDataPeriodByIds(
        array $cryptocrrencyIds,
         $periodStartDate,
         $periodEndDate,
         $daysCount
    )
    {
        $quote_id = $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL);
        $className = $this->getClassNameByCountDays($daysCount);
        return $className::whereIn('base_id', $cryptocrrencyIds)
                    ->where('quote_id', $quote_id)
                    ->whereDate('timestamp', '>=',$periodStartDate)
                    ->whereDate('timestamp', '<=',$periodEndDate)
                    ->select('close', 'base_id', 'timestamp')
                    ->orderBy('timestamp', 'asc')
                    ->get();

        // return OhlcvQuote::whereIn('cryptocurrency_id', $cryptocrrencyIds)
        //     ->select(
        //         '*',
        //         DB::raw("DATE_FORMAT(timestamp, '" . $dateIntervalFormat . "') as timestamp"))
        //     ->where('convert', 'USD')
        //     ->whereBetween('timestamp', [date('Y-m-d H:i:s', $periodStartDate), date('Y-m-d H:i:s', $periodEndDate)])
        //     ->orderBy('timestamp')
        //     ->get();
    }


    protected function getClassNameByCountDays($daysCount)
    {
        if ($daysCount < 7) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_1h';
        }
        elseif ($daysCount < 30) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_1h';
        }
        elseif ($daysCount < 120) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_4h';
        }
        elseif ($daysCount < 365) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_12h';
        }
        elseif ($daysCount >= 365) {
            return $className = 'App\OhlcvModels\ohlcv_cmc_1d';
        }
    }

    protected function getOhlcvFirstDataPeriodByIds($cryptocrrencyIds, $daysCount)
    {
        $className = $this->getClassNameByCountDays($daysCount);
        $quote_id = $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL);
        $last = $className::whereIn('base_id', $cryptocrrencyIds)
                    ->where('quote_id', $quote_id)
                    ->select('timestamp')
                    ->orderBy('timestamp', 'desc')
                    ->first();
        return ($last) ? date('Y-m-d',strtotime($last->timestamp)) : null;
    }
    protected function getOhlcvLastDataPeriodByIds($cryptocrrencyIds, $daysCount)
    {
        $className = $this->getClassNameByCountDays($daysCount);
        $quote_id = $this->getIdCurrencyByTicker(self::QUOTES_CURRENCY_SYMBOL);
        $last = $className::whereIn('base_id', $cryptocrrencyIds)
                    ->where('quote_id', $quote_id)
                    ->select('timestamp')
                    ->orderBy('timestamp', 'asc')
                    ->first();
        return ($last) ? date('Y-m-d',strtotime($last->timestamp)) : null;
    }
    protected function getDateIntervalFormatByCountDays(int $daysCount)
    {
        if ($daysCount < 7) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount < 30) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount < 120) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount < 365) {
            return 'Y-m-d H:00:00';
        }
        elseif ($daysCount >= 365) {
            return 'Y-m-d 00:00:00';
        }
    }
    protected function getPeriodIntervalByKey(string $step): string
    {
        switch ($step) {
            case self::DAILY_HISTORY_INTERVAL:
                return 'daily';
            case self::WEEKLY_HISTORY_INTERVAL:
                return 'weekly';
            case self::HOURLY_HISTORY_INTERVAL:
                return 'hourly';
            case self::MONTHLY_HISTORY_INTERVAL:
                return 'monthly';

            default:
                return 'weekly';
        }
    }
    public static function getPeriodIntervals(): array
    {
        return [
            ['key' => self::COMPARE_STEP_MONTH ,
                        'value' => self::COMPARE_STEP_MONTH_VALUE,],
            ['key' => self::COMPARE_STEP_WEEK ,
                        'value' => self::COMPARE_STEP_WEEK_VALUE,],
            ['key' => self::COMPARE_STEP_HOUR ,
                        'value' => self::COMPARE_STEP_HOUR_VALUE,],
            ['key' => self::COMPARE_STEP_DAY ,
                        'value' => self::COMPARE_STEP_DAY_VALUE,],
        ];
    }
    public function getPeriodIntervalsBySymbol($symbol): array
    {
        $symbol_id = $this->getIdCurrencyByTicker($symbol);
        $intervals = ['week', 'month', 'year'];
        $interval_db_array = [];
        foreach ($intervals as $interval) {
            $interval_db = Coefficient::where('cryptocurrency_id', $symbol_id)->where('interval', $interval)->first();
            if ($interval_db) {
                switch ($interval) {
                    case 'week':
                        $interval_db_array[] = [
                            'key' => self::WEEKLY_HISTORY_INTERVAL ,
                            'value' => self::WEEKLY_HISTORY_INTERVAL_VALUE
                        ];
                        break;
                    case 'month':
                        $interval_db_array[] = [
                            'key' => self::MONTHLY_HISTORY_INTERVAL ,
                            'value' => self::MONTHLY_HISTORY_INTERVAL_VALUE
                        ];
                        break;
                    case 'year':
                        $interval_db_array[] = [
                            'key' => self::YEARLY_HISTORY_INTERVAL ,
                            'value' => self::YEARLY_HISTORY_INTERVAL_VALUE
                        ];
                        break;
                }
            }
        }
        return $interval_db_array;
    }

        public static function getChartTypes(): array
    {
        return [
            self::CHART_TYPE_SORTINO,
            self::CHART_TYPE_SHARPE,
            self::CHART_TYPE_VOLATILITY
        ];
    }


    protected function getStartDateAsTimestamp(string $startAt,int $stepTimestamp, string $date): int
    {
        $today = Carbon::now()->startOfDay()->timestamp;
        if ($startAt) {
            $startAt = strtotime($startAt);
        }elseif ($date) {
            $startAt = strtotime($date);
        }else{
            $startAt = $today - $stepTimestamp;
        }
        return $startAt;
    }

    protected function getEndDateAsTimestamp($endAt, string $date): int
    {
        $today = Carbon::now()->startOfDay()->timestamp;
        if ($endAt) {
            $endAt = strtotime($endAt);
        }elseif ($date) {
            $endAt = strtotime($date);
        }else{
            $endAt = $today;
        }
        return $endAt;
    }

    protected function getTimestampStep(string $step): int
    {

        switch ($step) {
            case self::COMPARE_STEP_MINUTE:
                return 60;
            case self::COMPARE_STEP_HOUR_VALUE:
                return 60 * 60;
            case self::COMPARE_STEP_DAY_VALUE:
                return self::DAY_DURATION_IN_SECONDS;
            case self::COMPARE_STEP_WEEK_VALUE:
                return self::DAY_DURATION_IN_SECONDS * 7;
            case self::COMPARE_STEP_MONTH_VALUE;
                return self::DAY_DURATION_IN_SECONDS * 30;
            default:
                return self::DAY_DURATION_IN_SECONDS * 7;
        }
    }

    public function getCryptoCurrencyBySymbol($symbol)
    {
        $data = Cryptocurrency::query()->where('symbol', $symbol)->first();
        return $data;
    }

    public function getCryptoCurrencyWithOhlcvCmc($symbol, $quoteId, $timeStart, $timeEnd, $cmcType)
    {
        $cryptocurrencyWithQuotes = Cryptocurrency::with([
            $cmcType => function ($q) use ($timeStart, $timeEnd, $quoteId) {
                $q->select('base_id', 'quote_id', 'open', 'high', 'low', 'close', 'volume', 'time_open',
                    'time_close', 'timestamp')
                    ->where('timestamp', '>=', $timeStart)
                    ->where('timestamp', '<=', $timeEnd)
                    ->where('quote_id', $quoteId)
                    ->orderBy('timestamp');
            }
        ])->where('symbol', $symbol)->first([
            'cryptocurrency_id',
            'id',
            'symbol',
            'name'
        ]);
        return $cryptocurrencyWithQuotes;
    }

    public function getCryptoCurrencyApiData()
    {
        $dataProvider = new CryptoCurrencyDataProvider();
        $data = $dataProvider->getData();
        return $data;
    }

    public function getCryptoCurrencyApiDataByLimit($limit)
    {
        $dataProvider = new CryptoCurrencyDataProvider();
        $dataProvider->limit = $limit;
        $data = $dataProvider->getData();
        return $data;
    }

    public function updateCryptoCurrencyData(array $data)
    {
        $fiats = config('fiats');
        foreach ($data as $currency) {
            $currencyModel = Cryptocurrency::firstOrNew(['id' => $currency['id']]);
            $currencyModel->id = $currency['id'];
            $currencyModel->name = $currency['name'] ?? null;
            $currencyModel->symbol = $currency['symbol'] ?? null;
            $currencyModel->slug = $currency['slug'] ?? null;
            $currencyModel->circulating_supply = $currency['circulating_supply'] ?? null;
            $currencyModel->max_supply = $currency['max_supply'] ?? null;
            $currencyModel->date_added = $currency['date_added'] ?? null;
            $currencyModel->last_updated = $currency['last_updated'] ?? 0;
            $currencyModel->num_market_pairs = $currency['num_market_pairs'] ?? 0;
            $currencyModel->total_supply = $currency['total_supply'] ?? null;
            $currencyModel->cmc_rank = $currency['cmc_rank'] ?? null;
            $currencyModel->save();

            if (in_array($currency['symbol'], $fiats)) {
                $currencyModel->currency_type = self::CURRENCY_FIAT;
            } else {
                $currencyModel->currency_type = self::CURRENCY_CRYPTO;
            }

            if (!empty($currency['quote'])) {
                $currencyId = $currencyModel->cryptocurrency_id;
                $this->saveQuoteCoinsData($currency['quote'], $currencyId);
            }

            if (!empty($currency['platform'])) {
                $platform = $this->savePlatformData($currency['platform']);
                $currencyModel->platform_id = $platform->platform_id;
            }

            $currencyModel->save();
        }
    }

    public function savePlatformData(array $platform)
    {
        $platform = Platform::firstOrNew(['id' => $platform['id']]);
        $platform->id = $platform['id'];
        $platform->name = $platform['name'] ?? null;
        $platform->symbol = $platform['symbol'] ?? null;
        $platform->slug = $platform['slug'] ?? null;
        $platform->save();
        return $platform;
    }

    public function saveQuoteCoinsData(array $quotes, int $cryptoId)
    {
        foreach ($quotes as $quoteKey => $quoteValue) {
            $quote = Quote::firstOrNew(['symbol' => $quoteKey, 'cryptocurrency_id' => $cryptoId]);
            if (!$quotes) {
                $quotes = new Quote();
            }
            $quote->cryptocurrency_id = $cryptoId;
            $quote->symbol = $quoteKey;
            $quote->price = $quoteValue['price'] ?? null;
            $quote->volume_24h = $quoteValue['volume_24h'] ?? null;
            $quote->percent_change_1h = $quoteValue['percent_change_1h'] ?? null;
            $quote->percent_change_24h = $quoteValue['percent_change_24h'] ?? null;
            $quote->percent_change_7d = $quoteValue['percent_change_7d'] ?? null;
            $quote->market_cap = $quoteValue['market_cap'] ?? null;
            $quote->last_updated = $quoteValue['last_updated'] ?? null;
            $quote->save();
        }
    }
    public function getMarketCapFilters(){
        return [
            [
                'key' => 'markets_all_currencies_filters_all',
                'value' => ''
            ],

            [
                'key' => 'markets_all_currencies_mc1',
                'value' =>  '1BP',
            ],
            [
                'key' => 'markets_all_currencies_mc2',
                'value' =>  '1B',
            ],
            [
                'key' => 'markets_all_currencies_mc3',
                'value' =>  '100M',
            ],
            [
                'key' => 'markets_all_currencies_mc4',
                'value' =>  '10M',
            ],
            [
                'key' => 'markets_all_currencies_mc5',
                'value' =>  '1M',
            ],
            [
                'key' => 'markets_all_currencies_mc6',
                'value' =>  '100K',
            ]
        ];
    }
    public function getPriceFilters(){
        return [
            [
                'key' => 'markets_all_currencies_filters_all',
                'value' => ''
            ],

            [
                'key' => 'markets_all_currencies_p1',
                'value' =>  '100P',
            ],
            [
                'key' => 'markets_all_currencies_p2',
                'value' =>  '100',
            ],
            [
                'key' => 'markets_all_currencies_p3',
                'value' =>  '1',
            ],
            [
                'key' => 'markets_all_currencies_p4',
                'value' =>  '0.0001',
            ]
        ];
    }
    public function getVolume24Filters(){
        return [
            [
                'key' => 'markets_all_currencies_filters_all',
                'value' => ''
            ],

            [
                'key' => 'markets_all_currencies_vol1',
                'value' =>  '10MP',
            ],
            [
                'key' => 'markets_all_currencies_vol2',
                'value' =>  '10M',
            ],
            [
                'key' => 'markets_all_currencies_vol3',
                'value' =>  '1M',
            ],
            [
                'key' => 'markets_all_currencies_vol4',
                'value' =>  '100K',
            ],
            [
                'key' => 'markets_all_currencies_vol5',
                'value' =>  '10K',
            ],
        ];
    }
    public function getMarketCap($market_cap){
        switch ($market_cap) {
            case '1BP':
                return[1000000000, 10000000000000000];
                break;
            case '1B':
                return[100000000, 1000000000];
                break;
            case '100M':
                return[10000000, 100000000];
                break;
            case '10M':
                return[1000000, 10000000];
                break;
            case '1M':
                return[100000, 1000000];
                break;
            case '100K':
                return[0, 100000];
                break;

            default:
                return false;
                break;
        }
    }
    public function getPrice($price){
        switch ($price) {
            case '100P':
                return[100, 10000000000000000];
                break;
            case '100':
                return[1, 100];
                break;
            case '1':
                return[0.0001, 1];
                break;
            case '0.0001':
                return[0, 0.0001];
                break;

            default:
                return false;
                break;
        }
    }
    public function getVolume24($volume24){
        switch ($volume24) {
            case '10MP':
                return[10000000, 10000000000000000];
                break;
            case '10M':
                return[1000000, 10000000];
                break;
            case '1M':
                return[100000, 1000000];
                break;
            case '100K':
                return[10000, 100000];
                break;
            case '10K':
                return[0, 10000];
                break;

            default:
                return false;
                break;
        }
    }
    public function changeExchangeToClass($exchange){
        switch (strtolower($exchange)) {
            case 'binance':
                return 'binance';
                break;
            case 'cmc':
                return 'cmc';
                break;
            case 'coinmarketcap':
                return 'cmc';
                break;
            case 'bitfinex':
                return 'bitfinex';
                break;
            case 'bitstamp':
                return 'bitstamp';
                break;
            case 'bittrex':
                return 'bittrex';
                break;
            case 'coinbase':
                return 'coinbase';
                break;
            case 'huobi pro':
                return 'huobi_pro';
                break;
            case 'huobi_pro':
                return 'huobi_pro';
                break;
            case 'okex':
                return 'okex';
                break;
            case 'poloniex':
                return 'poloniex';
                break;
            case '':
                return '';
                break;

            default:
                return false;
                break;
        }
    }
    public function getResolutionForPair($exchange, $baseId, $quoteId)
    {
        $exchange_intervals = ($exchange == 'cmc') ? self::CMC_INTERVALS : self::STOCK_INTERVALS;
        $pair_intervals = [];
        foreach ($exchange_intervals as $key => $value) {
            $className = 'App\OhlcvModels\ohlcv_' . $exchange . '_' . $value;
            if (($value == '5m') || ($value == '1m')) {
                if ($className::where('base_id', $baseId)->where('quote_id', $quoteId)->whereDate('timestamp', '>=', date('Y-m-d', strtotime('-5 day')))->count() > 0) {
                    $pair_intervals[] = $key;
                }
            }else{
                if ($className::where('base_id', $baseId)->where('quote_id', $quoteId)->count() > 0) {
                    $pair_intervals[] = $key;
                }
            }
        }
        return $pair_intervals;
    }

    public function getIdCurrencyByTicker(string $ticker)
    {
        $crypto = Cryptocurrency::where('symbol', strtoupper($ticker))->select('cryptocurrency_id')->first();
        if ($crypto === null) {
            return false;
        }
        return($crypto->cryptocurrency_id);
    }
    public static function getZoomIntervals(): array
    {
        return [
            [
                'key' => 'markets_filters_zoom_monthly',
                'value' => 'month',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_monthly',
                'value' => 'month',
                'count' => 6,
            ],
            [
                'key' => 'markets_filters_zoom_yearly',
                'value' => 'year',
                'count' => 1,
            ],
            [
                'key' => 'markets_filters_zoom_all',
                'value' => '',
                'count' => '',
            ],

        ];
    }

    public function getCryptocurrenciesForAutocomplete()
    {
        return Cryptocurrency::select('symbol', 'slug', 'name')->orderBy('name', 'asc')->get()->toArray();
    }

}

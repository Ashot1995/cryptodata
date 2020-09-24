<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/4/19
 * Time: 1:07 PM
 */

namespace App\Services;


use App\Exchange;
use Illuminate\Support\Facades\DB;
use App\Cryptocurrency;

class ExchangeService
{
    public function getExchange($name) {
        $exchange = Exchange::leftJoin('exchange_quotes as exq1', 'exq1.exchange_id', '=', 'exchanges.exchange_id')
            ->select(
                'exchanges.id',
                'exchanges.exchange_id',
                'name',
                'urls',
                'exchanges.logo_2 as logo',
                'num_market_pairs',
                'exq1.volume_24h',
                'exq1.volume_7d',
                'exq1.volume_30d',
                DB::raw('(SELECT COUNT(DISTINCT ex.exchange_id) + 1 FROM exchanges as ex
               LEFT JOIN exchange_quotes as exq2 ON exq2.exchange_id = ex.exchange_id 
               WHERE (exq2.volume_24h > exq1.volume_24h AND exq2.symbol = exq1.symbol AND exq1.volume_24h IS NOT NULL)
               OR (exq1.volume_24h IS NULL AND exq2.volume_24h IS NOT NULL)) AS rank')
            )->where('name', $name)->first();
        return $exchange;
    }

    public function getExchangeMarketPairs($exchange, $limit, $skip) {
        $quote_id = $this->getIdCurrencyByTicker('USD');
        DB::statement(DB::raw('set @row:=' . $skip));
        $exchangeMarketPairs = $exchange->exchangeMarketPairs()
            ->select(
                DB::raw('@row:=@row+1 as rank'),
                DB::raw("CONCAT(c1.name, '/', c2.name) AS name"),
                DB::raw("c1.logo_2 AS logo"),
                DB::raw("CONCAT(c1.symbol, '/', c2.symbol) AS symbol"),
                'exchange_pair_quotes.price as price',
                'exchange_pair_quotes.convert_price as price_usd',
                'exchange_pair_quotes.volume_24h as volume_24h',
                'exchange_pair_quotes.percent_value_24h as percent_value_24h')
            ->join('cryptocurrencies as c1', 'market_pairs.string1_id', '=', 'c1.cryptocurrency_id')
            ->join('cryptocurrencies as c2', 'market_pairs.string2_id', '=', 'c2.cryptocurrency_id')
            ->where('exchange_pair_quotes.symbol', $quote_id)
            ->whereDate('exchange_pair_quotes.updated_at', Date('Y-m-d'))
            ->paginate($limit)->toArray();
        return $exchangeMarketPairs;
    }
    public function getExchangeMarketPairsWithoutDate($exchange, $limit, $skip) {
        $quote_id = $this->getIdCurrencyByTicker('USD');
        DB::statement(DB::raw('set @row:=' . $skip));
        $exchangeMarketPairs = $exchange->exchangeMarketPairs()
            ->select(
                DB::raw('@row:=@row+1 as rank'),
                DB::raw("CONCAT(c1.name, '/', c2.name) AS name"),
                DB::raw("c1.logo_2 AS logo"),
                DB::raw("CONCAT(c1.symbol, '/', c2.symbol) AS symbol"),
                'exchange_pair_quotes.price as price',
                'exchange_pair_quotes.convert_price as price_usd',
                'exchange_pair_quotes.volume_24h as volume_24h',
                'exchange_pair_quotes.percent_value_24h as percent_value_24h')
            ->join('cryptocurrencies as c1', 'market_pairs.string1_id', '=', 'c1.cryptocurrency_id')
            ->join('cryptocurrencies as c2', 'market_pairs.string2_id', '=', 'c2.cryptocurrency_id')
            ->where('exchange_pair_quotes.symbol', $quote_id)
            ->paginate($limit)->toArray();
        return $exchangeMarketPairs;
    }

    public function getIdCurrencyByTicker(string $ticker)
    {
        $crypto = Cryptocurrency::where('symbol', strtoupper($ticker))->select('cryptocurrency_id')->first();
        if ($crypto === null) {
            return false;
        }
        return($crypto->cryptocurrency_id);
    }

    public function getExchangeForAutocomplete()
    {
        return Exchange::orderBy('name', 'asc')->pluck('name')->toArray();
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/27/19
 * Time: 1:02 PM
 */

namespace App\Services;


use App\MarketPair;

class MarketPairService
{
    public function getPairsByCurrency($cryptocurrencyId)
    {
        $marketPairs = MarketPair::leftJoin('cryptocurrencies as c1', 'market_pairs.string1_id', '=',
            'c1.cryptocurrency_id')
            ->leftJoin('cryptocurrencies as c2', 'market_pairs.string2_id', '=', 'c2.cryptocurrency_id')
            ->select(
                'market_pairs.id',
                'c1.symbol as coin1_symbol',
                'c2.symbol as coin2_symbol',
                'c1.name as coin1_name',
                'c2.name as coin2_name',
                'string1_id',
                'string2_id'
            )
            ->where('string1_id', $cryptocurrencyId)
            ->groupBy('market_pairs.id')
            ->get();
        return $marketPairs;
    }

}

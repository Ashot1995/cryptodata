<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\MarketPair
 *
 * @property int $id
 * @property int|null $string1_id
 * @property int|null $string2_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $allowed_ohlcv
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Request[] $requests
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair whereAllowedOhlcv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair whereString1Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair whereString2Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketPair whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MarketPair extends Model
{
    protected $table = 'market_pairs';
    protected $hidden = ['pivot'];
    protected $fillable = ['string1_id', 'string2_id'];

    public static function getPairId($str1, $str2)
    {
        $marketPair = MarketPair::leftJoin('cryptocurrencies as c1', 'market_pairs.string1_id', '=',
            'c1.cryptocurrency_id')
            ->leftJoin('cryptocurrencies as c2', 'market_pairs.string2_id', '=', 'c2.cryptocurrency_id')
            ->select('market_pairs.id')
            ->Where(function ($q) use ($str1, $str2) {
                $q->where('c1.symbol', $str1)
                    ->where('c2.symbol', $str2);
            })->orWhere(function ($q) use ($str1, $str2) {
                $q->where('c1.symbol', $str2)
                    ->where('c2.symbol', $str1);
            })->first();

        if ($marketPair) {
            return $marketPair->id;
        }
        return;

    }

    public static function getPairIdStrong($str1, $str2)
    {
        $marketPair = MarketPair::leftJoin('cryptocurrencies as c1', 'market_pairs.string1_id', '=',
            'c1.cryptocurrency_id')
            ->leftJoin('cryptocurrencies as c2', 'market_pairs.string2_id', '=', 'c2.cryptocurrency_id')
            ->select('market_pairs.id', 'market_pairs.string1_id', 'market_pairs.string2_id')
            ->where('c1.symbol', $str1)
            ->where('c2.symbol', $str2)
            ->first();

        if ($marketPair) {
            return [$marketPair->id, $marketPair->string1_id, $marketPair->string2_id];
        } else {
            return false;
        }

    }

    public function requests()
    {
        return $this->hasMany('App\Request', 'market_pair_id');
    }

}

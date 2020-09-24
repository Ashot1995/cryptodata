<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Cryptocurrency
 * @mixin \Eloquent
 * @property int $cryptocurrency_id
 * @property int $id
 * @property string $name
 * @property string $symbol
 * @property string $slug
 * @property float|null $circulating_supply
 * @property float|null $max_supply
 * @property string|null $date_added
 * @property string|null $last_updated
 * @property int|null $num_market_pairs
 * @property int|null $platform_id
 * @property int|null $cmc_rank
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property float|null $total_supply
 * @property string|null $currency_type
 * @property int $is_active
 * @property string|null $first_historical_data
 * @property string|null $last_historical_data
 * @property string|null $pairs_updated_date
 * @property string|null $logo
 * @property string|null $logo_2
 * @property string|null $urls
 * @property int|null $market_cap_order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Coefficient[] $coefficients
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketPair[] $marketPairsLeft
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketPair[] $marketPairsRight
 * @property-read \App\Platform|null $platform
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Quote[] $quotes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Tag[] $tags
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereCirculatingSupply($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereCmcRank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereCryptocurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereCurrencyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereDateAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereFirstHistoricalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereLastHistoricalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereLastUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereLogo2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereMarketCapOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereMaxSupply($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereNumMarketPairs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency wherePairsUpdatedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency wherePlatformId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereTotalSupply($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cryptocurrency whereUrls($value)
 * @mixin \Eloquent
 */
class Cryptocurrency extends Model
{
    protected $table = 'cryptocurrencies';
    protected $primaryKey = 'cryptocurrency_id';
    protected $fillable = [
        'id',
        'name',
        'symbol',
        'slug',
        'circulating_supply',
        'max_supply',
        'date_added',
        'last_updated',
        'num_market_pairs',
        'platform_id',
        'cmc_rank',
        'total_supply',
        'currency_type',
        'is_active',
        'first_historical_data',
        'last_historical_data',
        'pairs_updated_date',
        'logo',
        'logo_2',
        'urls',
        'market_cap_order'
    ];

    //

    public function platform()
    {
        return $this->belongsTo('App\Platform', 'platform_id');
    }

    public function quotes()
    {
        return $this->hasMany('App\Quote', 'cryptocurrency_id');
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag', 'cryptocurrencies_tags', 'cryptocurrency_id', 'tag_id');
    }
    public function cryptocurrenciesHistorical()
    {
        return $this->hasMany('App\CryptocurrencyHistorical', 'cryptocurrency_id');
    }
    public function ohlcvQuotesHourly()
    {
        return $this->hasMany('App\OhlcvModels\ohlcv_cmc_1h', 'base_id');
    }
    public function ohlcvQuotes4h()
    {
        return $this->hasMany('App\OhlcvModels\ohlcv_cmc_4h', 'base_id');
    }
    public function ohlcvQuotes12h()
    {
        return $this->hasMany('App\OhlcvModels\ohlcv_cmc_12h', 'base_id');
    }
    public function ohlcvQuotesDaily()
    {
        return $this->hasMany('App\OhlcvModels\ohlcv_cmc_1d', 'base_id');
    }
    public function ohlcvQuotesWeekly()
    {
        return $this->hasMany('App\OhlcvModels\ohlcv_cmc_1w', 'base_id');
    }
    public function ohlcvQuotesMonthly()
    {
        return $this->hasMany('App\OhlcvModels\ohlcv_cmc_1m', 'base_id');
    }
//    public function ohlcvQuotes()
//    {
//        return $this->hasMany('App\OhlcvQuote', 'cryptocurrency_id');
//    }
    public function coefficients()
    {
        return $this->hasMany('App\Coefficient', 'cryptocurrency_id');
    }
    public function marketPairsLeft()
    {
        return $this->hasMany('App\MarketPair', 'string1_id');
    }
    public function marketPairsRight()
    {
        return $this->hasMany('App\MarketPair', 'string2_id');
    }
}

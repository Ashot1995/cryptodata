<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Exchange
 *
 * @property int $exchange_id
 * @property int $id
 * @property string|null $logo
 * @property string|null $logo_2
 * @property string|null $name
 * @property string|null $urls
 * @property int|null $is_active
 * @property string|null $first_historical_data
 * @property string|null $last_historical_data
 * @property int|null $quote_id
 * @property int|null $num_market_pairs
 * @property string|null $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketPair[] $exchangeMarketPairs
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExchangeQuote[] $quotes
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereExchangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereFirstHistoricalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereLastHistoricalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereLogo2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereNumMarketPairs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereQuoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exchange whereUrls($value)
 * @mixin \Eloquent
 */
class Exchange extends Model
{
    protected $table = 'exchanges';
    protected $primaryKey = 'exchange_id';
    protected $fillable = ['exchange_id', 'id', 'slug', 'name', 'is_active', 'num_market_pairs'];

    protected $guarded = [];

    public function exchangeMarketPairs()
    {
        return $this->belongsToMany('App\MarketPair', 'exchange_pair_quotes', 'exchange_id', 'market_pair_id');
    }

    public function quotes()
    {
        return $this->hasMany(ExchangeQuote::class, 'exchange_id', 'exchange_id');
    }
}

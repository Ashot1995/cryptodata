<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Quote
 *
 * @property int $id
 * @property int $cryptocurrency_id
 * @property string $symbol
 * @property float|null $price
 * @property float|null $volume_24h
 * @property float|null $percent_change_24h
 * @property float|null $percent_change_1h
 * @property float|null $percent_change_7d
 * @property float|null $market_cap
 * @property string $last_updated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereCryptocurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereLastUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereMarketCap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote wherePercentChange1h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote wherePercentChange24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote wherePercentChange7d($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Quote whereVolume24h($value)
 * @mixin \Eloquent
 */
class Quote extends Model
{
    protected $fillable = ['symbol'];
}

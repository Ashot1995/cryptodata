<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ExchangePairQuotes
 *
 * @property int $id
 * @property int $market_pair_id
 * @property int $exchange_id
 * @property string $symbol
 * @property float|null $price
 * @property float|null $convert_price
 * @property float|null $volume_24h
 * @property float|null $percent_value_24h
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereConvertPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereExchangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereMarketPairId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes wherePercentValue24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangePairQuotes whereVolume24h($value)
 * @mixin \Eloquent
 */
class ExchangePairQuotes extends Model
{
    protected $table = 'exchange_pair_quotes';
    protected $primaryKey = 'exchange_id';
    protected $guarded = [];
    //
}

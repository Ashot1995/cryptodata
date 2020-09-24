<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ExchangeQuote
 *
 * @property int $id
 * @property int $exchange_id
 * @property string $symbol
 * @property float|null $volume_24h
 * @property float|null $volume_24h_adjusted
 * @property float|null $volume_7d
 * @property float|null $volume_30d
 * @property float|null $percent_change_volume_24h
 * @property float|null $percent_change_volume_7d
 * @property float|null $percent_change_volume_30d
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereExchangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote wherePercentChangeVolume24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote wherePercentChangeVolume30d($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote wherePercentChangeVolume7d($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereVolume24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereVolume24hAdjusted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereVolume30d($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExchangeQuote whereVolume7d($value)
 * @mixin \Eloquent
 */
class ExchangeQuote extends Model
{
    protected $table = 'exchange_quotes';
}

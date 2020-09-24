<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GlobalMetricsHistorical
 *
 * @property int $id
 * @property string|null $timestamp
 * @property float|null $btc_dominance
 * @property string $convert
 * @property float|null $total_market_cap
 * @property float|null $total_volume_24h
 * @property string|null $timestamp_quote
 * @property string|null $interval
 * @property int|null $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereBtcDominance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereConvert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereTimestampQuote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereTotalMarketCap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereTotalVolume24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistorical whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GlobalMetricsHistorical extends Model
{
    protected $table = 'global_metrics_historical_quotes';
    protected $primaryKey = 'id';

    protected $guarded = [];
}

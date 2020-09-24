<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GlobalMetricsQuote
 *
 * @property int $id
 * @property int $global_metric_id
 * @property string $symbol
 * @property float|null $total_market_cap
 * @property float|null $total_volume_24h
 * @property string|null $last_updated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereGlobalMetricId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereLastUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereTotalMarketCap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereTotalVolume24h($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsQuote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GlobalMetricsQuote extends Model
{
    protected $guarded = [];
}

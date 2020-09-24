<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GlobalMetric
 *
 * @property int $id
 * @property int|null $active_cryptocurrencies
 * @property int|null $active_market_pairs
 * @property int|null $active_exchanges
 * @property float|null $eth_dominance
 * @property float|null $btc_dominance
 * @property string|null $last_updated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GlobalMetricsQuote[] $quotes
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereActiveCryptocurrencies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereActiveExchanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereActiveMarketPairs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereBtcDominance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereEthDominance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereLastUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetric whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GlobalMetric extends Model
{
    protected $table = 'global_metrics';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public static $symbols = [
        2781 => 'USD',
        2806 => 'RUB',
        3551 => 'KZT',
        2821 => 'ARS',
        3575 => 'XAU',
        2790 => 'EUR',
        2797 => 'JPY',
        2787 => 'CNY',
        2784 => 'CAD',
        3538 => 'EGP',
        2791 => 'GBP',
        2782 => 'AUD',
        3527 => 'AMD',
        3533 => 'BYN',
        3548 => 'KGS',
        2798 => 'KRW',
        2808 => 'SGD',
        2807 => 'SEK',
        2824 => 'UAH',
        2796 => 'INR',
        2785 => 'CHF',
        2813 => 'AED',
        3572 => 'UZS',
        2805 => 'PLN',
        3565 => 'RSD',
        2810 => 'TRY',
        3571 => 'UYU',
        3568 => 'TND',
        3564 => 'QAR',
        2801 => 'NOK',
    ];

    public function quotes()
    {
        return $this->hasMany('App\GlobalMetricsQuote', 'global_metric_id');
    }
}

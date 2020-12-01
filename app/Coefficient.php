<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Coefficient
 *
 * @property int $id
 * @property int $cryptocurrency_id
 * @property string $convert
 * @property float|null $volatility
 * @property float|null $sharpe
 * @property string $interval
 * @property string $c_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereCDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereConvert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereCryptocurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereSharpe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Coefficient whereVolatility($value)
 * @mixin \Eloquent
 */
class Coefficient extends Model
{
    protected $fillable = ['cryptocurrency_id', 'convert', 'c_date', 'interval', 'volatility', 'sharpe'];
}

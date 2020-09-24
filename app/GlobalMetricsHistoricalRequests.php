<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GlobalMetricsHistoricalRequests
 *
 * @property int $id
 * @property string|null $time_start
 * @property string|null $time_end
 * @property int|null $count
 * @property string|null $interval
 * @property string|null $convert
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereConvert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereTimeEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereTimeStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GlobalMetricsHistoricalRequests whereUpdatedAt($value)
 * @mixin \Eloquent
 * @author Ashot Gharakeshishyan
 */
class GlobalMetricsHistoricalRequests extends Model
{
    //
}

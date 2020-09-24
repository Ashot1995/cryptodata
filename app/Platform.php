<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Platform
 *
 * @property int $platform_id
 * @property int $id
 * @property string $name
 * @property string $symbol
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform wherePlatformId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Platform whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Platform extends Model
{
    protected $primaryKey = 'platform_id';
    protected $fillable = ['id', 'name', 'symbol', 'slug'];
}

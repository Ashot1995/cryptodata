<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TnIndex
 *
 * @property int $id
 * @property int|null $Tn200
 * @property int|null $Tn100
 * @property int|null $Tn50
 * @property int|null $Tn10
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereTn10($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereTn100($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereTn200($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereTn50($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TnIndex whereUpdatedAt($value)
 * @mixin \Eloquent
 */

class TnIndex extends Model
{
    protected $table = 'tn_indexes';
    protected $primaryKey = 'id';

    protected $guarded = [];
}

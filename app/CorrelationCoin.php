<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorrelationCoin extends Model
{
    protected $fillable = ['cryptocurrency_id', 'symbol'];
}

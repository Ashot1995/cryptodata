<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ObserverOhlcv extends Model
{
    protected $table = 'observer_ohlcv';
    protected $fillable = ['base_id', 'quote_id', 'time_start', 'time_end', 'exchange', 'interval', 'fixed'];
    //
}

<?php

namespace App\OhlcvModels;

use Illuminate\Database\Eloquent\Model;

class ohlcv_okex_15m extends Model
{
    public $table = 'ohlcv_okex_15m';

    public $fillable = ['base_id', 'quote_id', 'open', 'high', 'low', 'close', 'timestamp', 'volume',];
}
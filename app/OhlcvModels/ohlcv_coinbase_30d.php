<?php

namespace App\OhlcvModels;

use Illuminate\Database\Eloquent\Model;

class ohlcv_coinbase_30d extends Model
{
    public $table = 'ohlcv_coinbase_30d';

    public $fillable = ['base_id', 'quote_id', 'open', 'high', 'low', 'close', 'timestamp', 'volume',];
}


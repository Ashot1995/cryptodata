<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OhlcvPair extends Model
{
    protected $fillable = ['30d', '1w', '1d', '12h', '4h', '1h', '30m', '15m', '5m', '1m', 'exchange', 'base_id', 'quote_id'];
}

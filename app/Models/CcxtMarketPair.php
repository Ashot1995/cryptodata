<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcxtMarketPair extends Model
{
    public $table = 'ccxt_market_pairs';

    public $fillable = ['base_coin_id', 'quote_coin_id', 'exchange_id'];
}
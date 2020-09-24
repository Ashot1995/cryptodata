<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcxtExchangesMarketsQuote extends Model
{
    public $table = 'ccxt_exchange_quotes';

    public $fillable = ['market_pair_id', 'exchange_id', 'timestamp', 'symbol', 'price', 'change_24h', 'base_volume_24h', 'quote_volume_24h', 'percent_value_24h'];
}
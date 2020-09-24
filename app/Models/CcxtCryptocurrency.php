<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcxtCryptocurrency extends Model
{
    public $table = 'ccxt_cryptocurrencies';

    public $fillable = ['name', 'symbol', 'slug'];
}
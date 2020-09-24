<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcxtExchanges extends Model
{
    public $table = 'ccxt_exchanges';

    public $fillable = ['name', 'symbol'];
}
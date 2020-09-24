<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CryptocurrencyHistorical extends Model
{
    protected $table = 'cryptocurrencies_historical';
    protected $primaryKey = 'cryptocurrencies_historical_id';
    protected $fillable = ['cryptocurrency_id','circulating_supply','max_supply','total_supply'];
}

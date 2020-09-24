<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UndefinedTicker extends Model
{
    protected $table = 'undefined_tickers';
    protected $fillable = ['ticker', 'stock']; 
}

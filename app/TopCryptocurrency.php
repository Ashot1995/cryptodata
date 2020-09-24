<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class TopCryptocurrency extends Model
{
    protected $table = 'top_cryptocurrencies';
    protected $primaryKey = 'id';

    protected $guarded = [];
}

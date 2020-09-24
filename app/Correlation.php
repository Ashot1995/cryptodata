<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Correlation extends Model
{
    protected $table = 'correlations';
    protected $fillable = ['base_id', 'quote_id', 'timestamp', 'correlation', 'interval'];
}

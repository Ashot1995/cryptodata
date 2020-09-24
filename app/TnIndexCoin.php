<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TnIndexCoin extends Model
{
	protected $fillable = ['name', 'cryptocurrency_id'];
   	

   	public function Cryptocurrency()
    {
        return $this->hasOne('App\Cryptocurrency', 'cryptocurrency_id', 'cryptocurrency_id');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CryptocurrencyTag
 *
 * @property int $id
 * @property int $tag_id
 * @property int $cryptocurrency_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CryptocurrencyTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CryptocurrencyTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CryptocurrencyTag whereCryptocurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CryptocurrencyTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CryptocurrencyTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CryptocurrencyTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CryptocurrencyTag extends Model
{
    //
    protected $table = 'cryptocurrencies_tags';

}

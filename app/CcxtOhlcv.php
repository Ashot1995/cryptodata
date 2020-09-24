<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CcxtOhlcv
 *
 * @property int $id
 * @property string $base
 * @property string $quote
 * @property float|null $open
 * @property float|null $high
 * @property float|null $low
 * @property float|null $close
 * @property float|null $volume
 * @property string|null $timestamp
 * @property string $interval
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class CcxtOhlcv extends Model
{
    public $table = 'ccxt_ohlcv';

    public $fillable = ['base', 'quote', 'open', 'high', 'low', 'close', 'timestamp', 'volume', 'interval'];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Request
 *
 * @property int $id
 * @property string $request_name
 * @property string $api_coin_request
 * @property string|null $currency_name
 * @property string|null $currency_symbol
 * @property int $credit_count
 * @property int $success_count
 * @property int $daily_request_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $market_pair_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereApiCoinRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereCreditCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereCurrencyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereCurrencySymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereDailyRequestCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereMarketPairId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereRequestName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereSuccessCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Request whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Request extends Model
{
    //
}

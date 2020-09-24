<?php

const DAY_DURATION_IN_SECOND = 3600 * 24;
const HOUR_DURATION_IN_SECOND = 3600;
const MINUTE_DURATION_IN_SECOND = 60;

return [
    'coins_save' => HOUR_DURATION_IN_SECOND,
    'exchange_info' => HOUR_DURATION_IN_SECOND * 24,
    'exchange_listings' => MINUTE_DURATION_IN_SECOND * 10,
    'cryptocurrency_historical' => '07:00',
    'cryptocurrency_historical_daily' => '07:18',
    'tn_indexes' => '07:40',
    'pairquote' => '07:03',
    'global_metrics' => HOUR_DURATION_IN_SECOND * 24,
    'global_historical_hourly' => HOUR_DURATION_IN_SECOND * 24,
    'global_historical_daily' => HOUR_DURATION_IN_SECOND * 24,
    'global_historical_weekly' => DAY_DURATION_IN_SECOND * 7,
    'crypto_sortino' => DAY_DURATION_IN_SECOND * 7,
    'coefficients_get_monthly' => DAY_DURATION_IN_SECOND * 30,
    'coefficients_get_weekly' => DAY_DURATION_IN_SECOND * 7,
    'top_save' => DAY_DURATION_IN_SECOND * 30,
    'update_exchange_ohlcv_full_data_interval_minute' => MINUTE_DURATION_IN_SECOND,
    'cryptocurrency_listing_update' => MINUTE_DURATION_IN_SECOND * 10,
    'cryptocurrency_ohlcv_cmc__daily' => '06:04',
    'cryptocurrency_ohlcv_cmc__weekly' => DAY_DURATION_IN_SECOND * 7,
    'cryptocurrency_ohlcv_cmc__monthly' => DAY_DURATION_IN_SECOND * 30,
    'cryptocurrency_1000000000' => 1000000000,
    'ath_price' => '07:25',
    'annualized_dayli' => '07:45',
    'volatility_dayli' => '07:55',
    'ohlcv_build' => '06:45',
];

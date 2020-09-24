<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/15/19
 * Time: 10:28 AM
 */

namespace App\DataProviders;


class CryptoCurrencyBaseProvider
{
    const BASE_PATH = 'cryptocurrency.coinmarketcup';
    const CONFIG_BASE_URL = 'base_url';
    const API_KEY = 'api_key';

    public $config;

    public function getApiUrl()
    {
        return $this->config[$this->namespace][self::CONFIG_BASE_URL];
    }

    public function getConfigPath()
    {
        return self::BASE_PATH;
    }

    public function getApiKey()
    {
        return $this->config[self::API_KEY];
    }
}

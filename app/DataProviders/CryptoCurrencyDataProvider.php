<?php

namespace App\DataProviders;

use GuzzleHttp\Client;

class CryptoCurrencyDataProvider extends CryptoCurrencyBaseProvider
{
    protected $namespace = 'cryptocurrency';
    public $config;
    public $limit;

    public function __construct()
    {
        $this->config = \Config::get($this->getConfigPath());
        $this->limit = $this->config[$this->namespace]['limit'];
    }

    public function getData(): array
    {
        $client = new Client();
        $url = $this->getApiUrl();
        $apiKey = $this->getApiKey();
        $response = $client->get($url,
            [
                'headers' => ['X-CMC_PRO_API_KEY' => $apiKey],
                'query' => ['limit' => $this->limit],
            ]);

        $body = $response->getBody();
        $result = json_decode($body, true);
        if (!$result['status']['error_code'] === 0)  {
           return $data = [];
        }
        $data = $result['data'];
        return $data;
    }
}

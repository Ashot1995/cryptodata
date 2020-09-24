<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/14/19
 * Time: 6:14 PM
 */

namespace App\DataProviders;

use App\Services\CoinBaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;


class CryptoCurrencyOhlcvDataProvider extends CryptoCurrencyBaseProvider
{
    protected $namespace = 'cryptocurrency_ohlcv';

    private $id = null;
    private $symbol = null;
    private $timePeriod = null;
    private $interval = null;
    private $convert = null;
    private $timeEnd = null;
    private $timeStart = null;

    public function __construct()
    {
        $this->config = \Config::get(self::BASE_PATH);
    }

    public function getData(): array
    {
        $url = $this->getApiUrl();
        $apiKey = $this->getApiKey();
        $query = $this->setQueryData();

        try {
            $client = new Client();
            $response = $client->get($url,
                [
                    'headers' => ['X-CMC_PRO_API_KEY' => $apiKey],
                    'query' => $query
                ]);

            $body = $response->getBody();
            $result = json_decode($body, true);
            CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], $this->symbol, $this->convert, $url);

        } catch (ClientException $exception) {
            $result = json_decode($exception->getResponse()->getBody()->getContents(), true);
            if($result['status']['credit_count'] !== null) {
                CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], $this->symbol, $this->convert, $url);
            }
        }

        return $result ? $result : [];
    }

    public function setFields(int $id, string $symbol, string $timePeriod, string $interval, string $timeEnd, string $timeStart, string $convert)
    {
        $this->id = $id;
        $this->symbol = $symbol;
        $this->timePeriod = $timePeriod;
        $this->interval = $interval;
        $this->convert = $convert;
        $this->timeEnd = $timeEnd;
        $this->timeStart = $timeStart;

    }

    private function setQueryData(): array
    {
        $query = [];

        if ($this->id) {
            $query['id'] = $this->id;
        } else if ($this->symbol) {
            $query['symbol'] = $this->symbol;
        }

        if ($this->timePeriod) {
            $query['time_period'] = $this->timePeriod;
        }

        if ($this->interval) {
            $query['interval'] = $this->interval;
        }

        if ($this->convert) {
            $query['convert'] = $this->convert;
        }

        if ($this->timeEnd) {
            $query['time_end'] = $this->timeEnd;
        }
        if ($this->timeStart) {
            $query['time_start'] = $this->timeStart;
        }

        return $query;

    }


}

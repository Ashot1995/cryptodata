<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/18/19
 * Time: 12:38 PM
 */

namespace App\DataProviders;

use App\Services\CoinBaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;


class GlobalMetricsHistoryProvider extends CryptoCurrencyBaseProvider
{
    protected $namespace = 'global_metrics_historical';

    private $interval = null;
    private $convert = null;
    private $timeEnd = null;
    private $timeStart = null;
    private $count = null;

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
            CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', $url);

        } catch (ClientException $exception) {
            $result = json_decode($exception->getResponse()->getBody()->getContents(), true);
            CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', $url);
        }

        return $result;
    }

    public function setFields(string $interval, string $timeEnd, string $timeStart, string $convert, $count)
    {
        $this->interval = $interval;
        $this->convert = $convert;
        $this->timeEnd = $timeEnd;
        $this->timeStart = $timeStart;
        $this->count = $count;

    }

    private function setQueryData(): array
    {
        $query = [];

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

        if ($this->count) {
            $query['count'] = $this->count;
        }

        return $query;

    }

}

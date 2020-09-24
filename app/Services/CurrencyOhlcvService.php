<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 3/15/19
 * Time: 2:04 PM
 */

namespace App\Services;

use App\DataProviders\CryptoCurrencyOhlcvDataProvider;
use App\Http\DateFormat\DateFormat;


class CurrencyOhlcvService
{
    const DEFAULT_COINS_QUOTES_SYMBOL = 'USD';

    public function getCryptoCurrencyOhlcvApiData(int $id, string $symbol, string $timePeriod, string $interval, string $timeEnd, $timeStart, string $convert = '')
    {
        $dataProvider = new CryptoCurrencyOhlcvDataProvider();

        $dataProvider->setFields($id, $symbol, $timePeriod, $interval, $timeEnd, $timeStart, $convert);

        $result = $dataProvider->getData();
        return $result;
    }

    public function getOhlcvForPair($baseId, $quoteId, $timeStart, $timeEnd, $model)
    {
        $modelName = "App\\" . $model;

        $ohlcvQuote = $modelName::where('base_id', $baseId)
            ->where('quote_id', $quoteId)
            ->where('timestamp', '>=', $timeStart)
            ->where('timestamp', '<=', $timeEnd)
            ->get();

        return $ohlcvQuote;
    }

    public function saveCryptoCurrencyOhlcvData($allQuotes, $modelName, $cryptocurrency_id, $quoteId, $convert)
    {
        $modelName = "App\\" . $modelName;
        $bulkInsert = [];
        foreach ($allQuotes as $data) {
            $dateTimestamp = new \DateTime($data['quote'][$convert]['timestamp']);
            $dateTimestampFormat = $dateTimestamp->format(DateFormat::DATE_TIME_FORMAT);

            $bulkInsert[] = ['base_id' => $cryptocurrency_id,
                'quote_id' => $quoteId,
                'open' => $data['quote'][$convert]['open'],
                'high' => $data['quote'][$convert]['high'],
                'low' => $data['quote'][$convert]['low'],
                'close' => $data['quote'][$convert]['close'],
                'volume' => $data['quote'][$convert]['volume'],
                'market_cap' => $data['quote'][$convert]['market_cap'],
                'timestamp' => $dateTimestampFormat,
                'time_open' => $data['time_open'],
                'time_close' => $data['time_close'],
                'created_at' =>date(DateFormat::DATE_TIME_FORMAT),
                'updated_at' =>date(DateFormat::DATE_TIME_FORMAT)

            ];

        }

        if(!empty($bulkInsert)) {
            $count = count($bulkInsert);
            $modelName::where('base_id', $cryptocurrency_id)
                ->where('quote_id', $quoteId)
                ->where('timestamp', '>=', $bulkInsert[0]['timestamp'])
                ->where('timestamp', '<=', $bulkInsert[$count-1]['timestamp'])->delete();
            $ArraysPart = array_chunk($bulkInsert, 3000);
            foreach ($ArraysPart as $part) {
                $modelName::insert($part);
            }
        }
    }
}

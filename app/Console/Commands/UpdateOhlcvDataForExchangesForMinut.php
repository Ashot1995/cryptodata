<?php

namespace App\Console\Commands;

use App\CcxtOhlcv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;


class UpdateOhlcvDataForExchangesForMinut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:exchange_ohlcv_full_data_interval_minute {exchangeName=binance} {symbol=BTC/USDT} {interval=1m}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exchangeName = $this->argument('exchangeName');
        $symbol = $this->argument('symbol');
        $defaultInterval = $this->argument('interval');
        $exchanges = \ccxt\Exchange::$exchanges;
        if (!in_array($exchangeName, $exchanges)) {
            dd('Обменника с таким именем не существует');
        }
        $exchangeClass = "\\ccxt\\" . $exchangeName;
        $exchange = new $exchangeClass();
        $exchange->load_markets();
        if(!isset($exchange->markets[$symbol])) {
            dd('У обменника ' . $exchangeName . ' нету пары ' . $symbol);
        }
        if (!$exchange->has('fetchOHLCV')) {
            dd('Обменник не содержит информацию о OHLCV');
        }
        if (!in_array($defaultInterval, $exchange->timeframes)) {
            dd('Такого временного интервала нет у обменника ' . $exchangeName);
        }
        $startFrom = CcxtOhlcv::query()->orderBy('timestamp', 'DESC')->first();
        if ($startFrom) {
            $startFrom = strtotime($startFrom->timestamp)
                ? (int)(strtotime($startFrom->timestamp) . '000')
                : (int)(\Carbon\Carbon::now()->subDay(1)->timestamp . '000');
        }
        $formattedData = $this->getDataForExchangeBySymbol($exchange, $symbol, $defaultInterval, $startFrom);
        array_shift($formattedData);
        $chuncks = array_chunk($formattedData, 5000);
        foreach ($chuncks as $insert) {
            CcxtOhlcv::insert($insert);
        }
        $this->info('Сохранено записей в базу ' . count($formattedData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $defaultInterval);
        sleep(Config::get('commands_sleep.update_exchange_ohlcv_full_data_interval_minute'));
    }

    protected function getDataForExchangeBySymbol($exchange, string $symbol, string $interval, $startFromDate = false)
    {
        $formattedInsertData = [];
        try {
            $now = (int) (\Carbon\Carbon::now()->timestamp . '000');
            $since = $startFromDate;
            list($baseSymbol, $quoteSymbol) = explode('/', $symbol);

            $fullOhlyData = [];
            for ($startFrom = $since; $startFrom < $now; $startFrom = end($fullOhlyData)[0]) {
                try {
                    $ohly = $exchange->fetchOHLCV($symbol, $interval, $startFrom, $exchange->rateLimit);
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    continue;
                }
                if (count($ohly) === 1 && $ohly[0][0] === $startFromDate) {
                    $ohly = [];
                }
                $fullOhlyData = array_merge_recursive($fullOhlyData, $ohly);
                usleep ($exchange->rateLimit * 1000);
                if (count($ohly) < $exchange->rateLimit) {
                    break;
                }
                $this->info(count($fullOhlyData));

            }
            foreach ($fullOhlyData as $datum) {
                $formattedInsertData[] = [
                    'base' => $baseSymbol,
                    'quote' => $quoteSymbol,
                    'timestamp' => date('Y-m-d H:i:s', substr($datum[0], 0, 10)),
                    'open' => $datum[1],
                    'high' => $datum[2],
                    'low' => $datum[3],
                    'close' => $datum[4],
                    'volume' => $datum[5],
                    'interval' => $interval,
                    'exchange_name' => $exchange->name
                ];
            }
            $this->info('Собрано записей в колекцию ' . count($formattedInsertData) . ' для пары ' . $symbol . ' в обменнике ' . $exchange->name . ' с интервалом ' . $interval);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $formattedInsertData;
    }
}
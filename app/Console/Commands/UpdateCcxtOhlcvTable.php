<?php

namespace App\Console\Commands;

use App\CcxtOhlcv;
use Illuminate\Console\Command;


class UpdateCcxtOhlcvTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ccxt_ohlc_table {exchangeName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
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
        $exchanges = \ccxt\Exchange::$exchanges;
        if (!in_array($exchangeName, $exchanges)) {
            dd('Обменника с таким именем не существует');
        }
        $exchangeClass = "\\ccxt\\" . $exchangeName;
        $exchange = new $exchangeClass();
        if (!$exchange->has('fetchOHLCV')) {
            dd('Обменник не содержит информацию о OHLCV');
        }
        try {
            $now = (int) (\Carbon\Carbon::now()->timestamp . '000');
            $since = (int) (\Carbon\Carbon::now()->subMonth()->timestamp . '000');
            $markets = $exchange->load_markets();
            $interval = '1m';
            foreach ($markets as $marketId => $market) {
                if ($market['base'] === 'BTC' || $market['base'] === 'ETH' || $market['base'] === 'XRP') {
                    $fullOhlyData = [];
                    for ($startFrom = $since; $startFrom < $now; $startFrom = end($fullOhlyData)[0]) {
                        $ohly = $exchange->fetchOHLCV($market['symbol'], $interval, $startFrom, $exchange->rateLimit);
                        $fullOhlyData = array_merge_recursive($fullOhlyData, $ohly);
                        sleep(4);
                    }

                    $formattedInsertData = [];
                    foreach ($fullOhlyData as $datum) {
                        $formattedInsertData[] = [
                            'base' => $market['base'],
                            'quote' => $market['quote'],
                            'timestamp' => date('Y-m-d H:i:s', substr($datum[0], 0, 10)),
                            'open' => $datum[1],
                            'high' => $datum[2],
                            'low' => $datum[3],
                            'close' => $datum[4],
                            'volume' => $datum[5],
                            'interval' => $interval,
                            'exchange_name' => $exchangeName

                        ];
                    }
                    $chunckInsert = array_chunk($formattedInsertData, 2000);
                    foreach ($chunckInsert as $insert) {
                        CcxtOhlcv::insert($insert);
                    }
                    $this->info('Сохранено ' . count($formattedInsertData) . ' записей для пары ' . $marketId . ' в обменнике ' . $exchangeName);
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

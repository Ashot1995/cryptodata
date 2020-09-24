<?php

namespace App\Console\Commands;

use App\Cryptocurrency;
use App\Services\CoinBaseService;
use App\Services\CryptoCurrencyService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use App\Quote;
use Illuminate\Support\Facades\Config;
use  App\Services\SleepService;

class QuoteHistorycal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roi:historical  {--time_end=} {--time_start=}';

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
        $convert = 'USD';
        $cryptoService = new CryptoCurrencyService();
        $topCrypts = Cryptocurrency::pluck('id')->toArray();
        foreach ($topCrypts as $id) {
            $price_now_query = Quote::where('cryptocurrency_id', $id )->where('symbol', $convert)->select('price')->first();
            if ($price_now_query) {
                $price_now = $price_now_query->price;
            }
            $cryptoPrice = $cryptoService->getCryptocurrenciesPriceForROI($id);
            $parcentChangesWeekly = ((float)$cryptoPrice['week'] != 0) ? round(((float)$price_now - (float)$cryptoPrice['week']) / (float)$cryptoPrice['week'] * 100, 2) : null;
            $parcentChangesMonthly = ((float)$cryptoPrice['month'] != 0) ? round(((float)$price_now - (float)$cryptoPrice['month']) / (float)$cryptoPrice['month'] * 100, 2) : null;
            $parcentChangesThreeMonth =((float)$cryptoPrice['threeMonth'] != 0) ? round(((float)$price_now -(float)$cryptoPrice['threeMonth']) / (float)$cryptoPrice['threeMonth'] * 100, 2) : null;

            $cryptoService->getCryptocurrencySavePercentChanges($parcentChangesWeekly, $parcentChangesMonthly,
                $parcentChangesThreeMonth, $id);
        }
        $sleep = new SleepService;
        sleep($sleep->intervalSleep('everyDay'));
    }

}

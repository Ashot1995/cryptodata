<?php

namespace App\Console\Commands;

use App\Cryptocurrency;
use App\Services\CryptoCurrencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use  App\Services\SleepService;
use Illuminate\Support\Facades\Log;

class CryptoATN extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'atn:price {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'compare price ATN crypto';

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
        $dateTo = !empty($this->option('date')) ? $this->option('date') : date('Y-m-d ');
        $dateFrom = date('Y-m-d', strtotime($dateTo . "-7 days"));
        $cryptoService = new CryptoCurrencyService();
        $CryptoId = Cryptocurrency::pluck('cryptocurrency_id')->toArray();
        foreach ($CryptoId as $id) {

            $maxPrice = $cryptoService->getCryptocurrencyMaxPrice($id);
            if ($maxPrice != 0) {
                $cryptoService->getCryptocurrencySavePrice($id, $maxPrice);
                $this->info('Done for cryptocurrency_id=' . $id);
                Log::info('atn:pricem, done for cryptocurrency_id=' . $id);
            }
        }

        $sleep = new SleepService;
        echo "string";
        sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.ath_price')));
    }
}

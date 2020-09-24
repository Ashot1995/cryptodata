<?php

namespace App\Console\Commands;

use App\Services\CryptoCurrencyService;
use App\Services\TnIndexService;
use App\TnIndex;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use  App\Services\SleepService;
class TnIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tn:indexes {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */


    protected $description = 'Save to tn_indexes';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    const DEFAULT_COINS_QUOTES_SYMBOL = 'USD';

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
        $this->info('Start update tn indexes ' . date('H:i:s d-m-Y'));
        \Log::info('Start update tn indexes ' . date('H:i:s d-m-Y'));
        $date = !empty($this->option('date')) ? $this->option('date') : date('Y-m-d', strtotime("-1 day"));

        $topCrypts200 = TopCryptocurrency::limit(200)->pluck('cryptocurrency_id')->toArray();
        $topCrypts100 = array_slice($topCrypts200, 0, 100);
        $topCrypts50 = array_slice($topCrypts200, 0, 50);
        $topCrypts10 = array_slice($topCrypts200, 0, 10);

        $tnIndex = TnIndex::whereDate('timestamp', $date)->first();

        if (!$tnIndex) {
            $tnIndex = new TnIndex();
        }

        $tnIndexService = new TnIndexService();
        $cryptoService = new CryptoCurrencyService();
        $quote = $cryptoService->getCryptoCurrencyBySymbol(self::DEFAULT_COINS_QUOTES_SYMBOL);

        if (!$quote) {
            return;
        }

        $tn200 = $tnIndexService->getTnByTops($topCrypts200, 200, $date, $quote->cryptocurrency_id);
        $tn100 = $tnIndexService->getTnByTops($topCrypts100, 100, $date, $quote->cryptocurrency_id);
        $tn50 = $tnIndexService->getTnByTops($topCrypts50, 50, $date, $quote->cryptocurrency_id);
        $tn10 = $tnIndexService->getTnByTops($topCrypts10, 10, $date, $quote->cryptocurrency_id);

        if ($tn200 && $tn100 && $tn50 && $tn10) {
            $tnIndex->Tn200 = $tn200;
            $tnIndex->Tn100 = $tn100;
            $tnIndex->Tn50 = $tn50;
            $tnIndex->Tn10 = $tn10;
            $tnIndex->timestamp = $date;
            $tnIndex->save();

            $sleep = new SleepService;
            $this->info('stop succesfull update tn indexes ' . date('H:i:s d-m-Y'));
            \Log::info('stop succesfull update tn indexes ' . date('H:i:s d-m-Y'));

            sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.tn_indexes')));
        }else{
            sleep(600);
        }

    }
}

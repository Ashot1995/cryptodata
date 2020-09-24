<?php

namespace App\Console\Commands;

use App\Services\CoefficientService;
use App\Services\CryptoCurrencyService;
use App\Services\TnIndexService;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use App\Http\DateFormat\DateFormat;
use Illuminate\Support\Facades\Config;

class CryptoParseSortino extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:sortino {--date=}';

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
        $cIds = TopCryptocurrency::limit(200)->pluck('cryptocurrency_id')->toArray();
        $dateTo = !empty($this->option('date')) ? $this->option('date') : date(DateFormat::DATE_FORMAT,
            strtotime("-1 day"));
        $dateFrom = date('Y-m-d', strtotime($dateTo . "-6 days"));
        $cryptoService = new CryptoCurrencyService();
        $tnIndexService = new TnIndexService();
        $coefficientService = new CoefficientService();
        $tnIndexes = $tnIndexService->getIndexes("tn100", $dateFrom, $dateTo);
        $this->info("tnIndexesCount = " . $tnIndexes->count());
        if ($tnIndexes->count() != 7) {
            $r = $tnIndexes->avg('tn100');
            $cryptocurrenciesWithQuotes = $cryptoService->getCryptocurrencyWithOhlcv($cIds, $dateFrom, $dateTo);

            //    S = R / O
            //    R =  (tn100_1+...+tn100_7) / 7 --$tnIndexesAvg
            //    O = sqrt (X1^2 + ... + X7^2)
            $sum = 0;
            foreach ($cryptocurrenciesWithQuotes as $cryptocurrency) {
                $this->info($cryptocurrency->ohlcvQuotesDaily->count());

                if ($cryptocurrency->ohlcvQuotesDaily->count() == 7) {

                    foreach ($cryptocurrency->ohlcvQuotesDaily as $x) {
                        $sum += (pow($x->close, 2));
                    }

                    $o = sqrt($sum);
                    $s = $r / $o;

                    $coefficientService->getCoefficentsAndSave($cryptocurrency->cryptocurrency_id, $dateTo, CoefficientService::WEEKLY_INTERVAL, 0, 0, 0, 0, $s);

                }

            }
        }

        sleep(Config::get('commands_sleep.crypto_sortino'));
    }
}

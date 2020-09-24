<?php

namespace App\Console\Commands;


use App\Cryptocurrency;
use App\Http\DateFormat\DateFormat;
use App\Services\CoefficientService;
use App\Services\CryptoCurrencyService;
use App\Services\TnIndexService;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CoefficientsDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coefficients:daily {--date=}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Calculate volatility, sharpe, alpha, beta';

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
     * TN-384
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // for usd
        $date = !empty($this->option('date')) ? $this->option('date') : date(DateFormat::DATE_FORMAT, strtotime("-1 day"));
        $lastMonth = strtotime($date . "-1 month");
        $tn100Monthly = 0;
        $tn100Sum = 0;
        $crytoService = new CryptoCurrencyService();
        $tnIndexService = new TnIndexService();
        $coefficientService = new CoefficientService();

        $tnIndexesforMonth = $tnIndexService->getTnIndexesforMonth($lastMonth);
        $daysCount = cal_days_in_month(CAL_GREGORIAN, date('m', $lastMonth), date('Y', $lastMonth));

        $tnIndex = $tnIndexService->getTnIndexbyDate($date);

        $this->info("daysCount = " . $daysCount);
        $this->info("tnIndexesforMonth = " . $tnIndexesforMonth->count());

        if ($tnIndexesforMonth->count() === $daysCount && $tnIndex) {
            $tn100Monthly = $tnIndexesforMonth->avg('tn100');
            false;
            $tn100Sum = $tnIndex->tn100 - $tn100Monthly;
        }

        $cIds = TopCryptocurrency::limit(200)->pluck('cryptocurrency_id')->toArray();
        $cryptocurrenciesWithQuotes = $crytoService->getCryptocurrencyWithHourlyOhlcv($cIds, $date);
        foreach ($cryptocurrenciesWithQuotes as $cryptocurrency) {
            $sumForBeta = 0;
            $xMonthly = 0;

            $monthlyQuotes = $crytoService->getMonthlyQuotes($cryptocurrency, $lastMonth);

            $xDaily = $crytoService->getDailyQuotes($cryptocurrency, $date);

            $this->info($monthlyQuotes->count());
            if ($monthlyQuotes->count() == $daysCount && $xDaily) {
                $xMonthly = $monthlyQuotes->avg('close');

                $sumForBeta = ($xDaily->close - $xMonthly) * ($tnIndex->tn100 - $tn100Monthly);
            }

            $v = 0;
            $count = $cryptocurrency->ohlcvQuotesHourly->count();
            if ($cryptocurrency->ohlcvQuotesHourly && $count == 24) {

                //S = R / V,
                //R = (Xn - X1) / X1,


//                $m = $cryptocurrency->ohlcvQuotes->sum('close');
//                $m = $m / $count;
//
//                foreach ($cryptocurrency->ohlcvQuotes as $cQuote) {
//                    $v += ($m - $cQuote->close) * ($m - $cQuote->close);
//                }
//
//                $v = $v / ($count - 1);


                // calculate beta
                // B = SUM / TN100_SUM^2,

                // calculate alpha
                //A = Xср_мес - (Tn100_ср_мес - B_за_день * (Tn100_за_день - Tn100_ср_мес) )


                $beta = $tn100Sum ? ($sumForBeta / pow($tn100Sum, 2)) : null;
                $alpha = ($tnIndex && $xMonthly) ? $xMonthly - ($tn100Monthly - $beta * ($tnIndex->tn100 - $tn100Monthly)) : null;

                $firstQuote = $cryptocurrency->ohlcvQuotesHourly[0];
                $lastQuote = $cryptocurrency->ohlcvQuotesHourly[$count - 1];
                $r = ($lastQuote->close - $firstQuote->close) / $firstQuote->close;
                $s = round($r / $v, 4);
               $result = $coefficientService->getCoefficentsAndSave($cryptocurrency->cryptocurrency_id, $date, CoefficientService::DAILY_INTERVAL, $alpha, $beta, $s);
                if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                    $this->info('Done for ' . $cryptocurrency->cryptocurrency_id);
                } else if(!empty($result['status'])) {
                    $this->info('Fails for  ' . $cryptocurrency->cryptocurrency_id . ' ' . $result['status']['error_message']);
                    Log::info($this->description . ' fails for  ' .$cryptocurrency->cryptocurrency_id. ' ' . $result['status']['error_message']);
                } else {
                    $this->info('Fails for  ' . $cryptocurrency->cryptocurrency_id );
                    Log::info($this->description . ' fails for  ' . $cryptocurrency->cryptocurrency_id);
                }
            }

        }

    }
}

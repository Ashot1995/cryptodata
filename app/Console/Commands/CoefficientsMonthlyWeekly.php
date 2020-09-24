<?php

namespace App\Console\Commands;

use App\Coefficient;
use App\Http\DateFormat\DateFormat;
use App\Services\CoefficientService;
use App\Services\CryptoCurrencyService;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
class CoefficientsMonthlyWeekly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coefficients:get {--date=} {--interval=}';

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
        // for usd
        $interval = !empty($this->option('interval')) ? $this->option('interval') : 'weekly';
        $dateTo = !empty($this->option('date')) ? $this->option('date') : date(DateFormat::DATE_FORMAT,
            strtotime("-1 day"));
        if ($interval == 'monthly') {
            $dateFrom = date('Y-m-t', strtotime($dateTo. "-2 month"));
            $dateTo = date('Y-m-t', strtotime($dateTo. "-1 month"));
            $daysCount = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($dateTo)), date('Y', strtotime($dateTo))) + 1;
        } else {
            if (date('w', strtotime($dateTo)) == 1) {
                $dateFrom = date('Y-m-d', strtotime($dateTo . "last Monday -1 day"));
            }else{
                $dateFrom = date('Y-m-d', strtotime($dateTo . " -1 week last Monday  -1 day"));

            }
            $dateTo = date('Y-m-d', strtotime($dateTo . "last Sunday"));
            $daysCount = 8;
        }
        $cIds = TopCryptocurrency::limit(200)->pluck('cryptocurrency_id')->toArray();

            // dump($dateFrom);
            // dump($dateTo);
            // dd();
        $cryptoService = new CryptoCurrencyService();
        $coefficientService = new CoefficientService();
        $cryptocurrenciesWithQuotes = $cryptoService->getCryptocurrencyWithOhlcv($cIds, $dateFrom, $dateTo);
        foreach ($cryptocurrenciesWithQuotes as $cryptocurrency) {

            $vol = 0;
            $m = 0;
            $count = count($cryptocurrency->ohlcvQuotesDaily);
            $this->info($cryptocurrency->cryptocurrency_id);
            $this->info($cryptocurrency->symbol);
            $this->info($count . '==' . $daysCount);
            dump($daysCount);
            dump($dateFrom);
            // dd($count);
            if ($cryptocurrency->ohlcvQuotesDaily && $count == $daysCount) {
                //   M = (X1 + X2 + ... + Xn)/n
                //    Xn = ln(X_n/X_n-1)
                foreach ($cryptocurrency->ohlcvQuotesDaily as $key => $cQuote) {
                    //X1 = ln(X0/X1)

                    if ($key + 1 != $cryptocurrency->ohlcvQuotesDaily->count()) {
                        $x1 = $cryptocurrency->ohlcvQuotesDaily[$key]->close;
                        $x2 = $cryptocurrency->ohlcvQuotesDaily[$key + 1]->close;
                        dd($m);
                        if ($x1 && $x2) {
                            $m += log($x2 / $x1);
                        }
                    }
                }
                $m = $m / $count;


                //Vw = 100 * Vol / Vol_max
                //SUM = (M - X1)^2+(M - X2)^2 + ... + (M - X24)^2

                foreach ($cryptocurrency->ohlcvQuotesDaily as $key2 => $cQuote) {
                    //    Xn = ln(X_n/X_n-1)
                    if ($key2 + 1 != $cryptocurrency->ohlcvQuotesDaily->count()) {
                        $x1 = $cryptocurrency->ohlcvQuotesDaily[$key2]->close;
                        $x2 = $cryptocurrency->ohlcvQuotesDaily[$key2 + 1]->close;

                        if ($x1 && $x2) {
                            $vol += pow($m - log($x2 / $x1), 2); //sum

                        }
                    }
                }

                $vol = $vol / ($count - 1);
                $maxVol = $coefficientService->getMaxVolatility($cryptocurrency->cryptocurrency_id, $interval);
                dump($cryptocurrency->cryptocurrency_id);
                dd($maxVol);
                if(!$maxVol) {
                    $maxVol = $vol;
                }
                dd($maxVol);

                $vw = 100 * $vol / $maxVol;

                // S = R / V
                // R = (Xn - X1) / X1,

                $firstQuote = $cryptocurrency->ohlcvQuotesDaily[1];
                $lastQuote = $cryptocurrency->ohlcvQuotesDaily[$count - 1];
                $r = $firstQuote->close ? ($lastQuote->close - $firstQuote->close) / $firstQuote->close : null;
                $s = round($r / $vol, 4);
                $coefficient = Coefficient::where('cryptocurrency_id', $cryptocurrency->cryptocurrency_id)
                    ->where('interval', $interval)
                    ->whereDate('c_date', $dateTo)
                    ->first();

                if (!$coefficient) {
                    $coefficient = new Coefficient();
                }

                $coefficient->cryptocurrency_id = $cryptocurrency->cryptocurrency_id;
                $coefficient->convert = 'USD';
                $coefficient->volatility = $vol;
                $coefficient->volatility_w = $vw;
                $coefficient->sharpe = $s;
                $coefficient->interval = $interval;
                $coefficient->c_date = $dateTo;

                if ($coefficient->save()) {
                    $this->info('Done for ' .$cryptocurrency->symbol);
                } else if(!empty($result['status'])) {
                    $this->info('Fails for  ' . $cryptocurrency->symbol . ' ' . $result['status']['error_message']);
                    Log::info($this->description . ' fails for ' . $cryptocurrency->symbol . ' ' . $result['status']['error_message']);
                } else {
                    $this->info('Fails for ' . $cryptocurrency->symbol);
                    Log::info($this->description . ' fails for ' . $cryptocurrency->symbol);
                }
            }
        }

        if ($interval === 'monthly') {
            sleep(Config::get('commands_sleep.coefficients_get_monthly'));
        } else {
            sleep(Config::get('commands_sleep.coefficients_get_weekly'));
        }
    }
}

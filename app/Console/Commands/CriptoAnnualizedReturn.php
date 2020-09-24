<?php

namespace App\Console\Commands;

use App\Http\DateFormat\DateFormat;
use App\Services\CoefficientService;
use App\Services\TnIndexService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CriptoAnnualizedReturn extends Command
{
    const INTERVAL_MONTHLY = "monthly";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'annualized:return {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save Annualized Return.';

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
        $dateTo = !empty($this->option('date')) ? $this->option('date') : date(DateFormat::DATE_FORMAT, strtotime('last day of previous month'));
        $dateFrom = date('Y-m-01', strtotime($dateTo));
        $tnIndexService = new TnIndexService();
        $coefficientsService = new CoefficientService();
        $tnIndexesAvg = [];

        for ($i = 0; $i < 4; $i++) {

            if ($i === 3) {
                $dateTo2 = $dateTo;
            } else {
                $dateTo2 = date(DateFormat::DATE_FORMAT, strtotime($dateFrom . ' + 6 days'));
            }

            try {
                $tnIndex = $tnIndexService->getIndexes('tn100', $dateFrom, $dateTo2);
                $this->info('Done for TnIndexes in time from ' . $dateFrom .' to '.$dateTo );

                $tnIndexesAvg[] = $tnIndex->avg('tn100');
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                Log::info($this->description . ' ' . $e->getMessage());
                    return;
            }

            $dateFrom = date(DateFormat::DATE_FORMAT, strtotime($dateTo2 . ' + 1 day'));

        };

        $s = (100 + $tnIndexesAvg[0]) * (100 + $tnIndexesAvg[1]) * (100 + $tnIndexesAvg[2]) * (100 + $tnIndexesAvg[3]);
        $ar = pow($s, (1 / 4)) - 100;

        $coefficientsService->saveCoefficient($ar, $dateTo, self::INTERVAL_MONTHLY);

    }

}

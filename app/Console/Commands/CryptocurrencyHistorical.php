<?php

namespace App\Console\Commands;

use App\Cryptocurrency;
use App\Services\CurrencyOhlcvService;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use  App\Services\SleepService;
use Illuminate\Support\Facades\Log;


class CryptocurrencyHistorical extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptocurrency:historical {--time_end=} {--time_start=} {--interval=} {--time_period=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'save ohlcv for top 100, see TN-419';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    const DEFAULT_COINS_QUOTES_SYMBOL = 'USD';
    const INTERVAL_MODELS = [
        'daily' => 'OhlcvModels\ohlcv_cmc_1d',
        'hourly' => 'OhlcvModels\ohlcv_cmc_1h',
    ];

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
        $this->info('Start save ohlcv for top 100 ' . date('H:i:s d-m-Y'));
        // get ids from 100
        // save historical data for 100 with convert value USD
        $interval = !empty($this->option('interval')) ? $this->option('interval') : 'hourly';
        $timePeriod = !empty($this->option('time_period')) ? $this->option('time_period') : 'hourly';

        if ($interval == 'hourly') {
            $allCrypts = TopCryptocurrency::select('cryptocurrency_coin_id as id', 'cryptocurrency_id')->limit(100)->get();
        } else {
            $allCrypts = Cryptocurrency::select('id', 'cryptocurrency_id')->get();
        }
        $query['interval'] = $interval;
        $query['time_period'] = $timePeriod;
        $convert = self::DEFAULT_COINS_QUOTES_SYMBOL;
        $timeEnd = !empty($this->option('time_end')) ? date('Y-m-d  23:59:59',
            strtotime($this->option('time_end'))) : date('Y-m-d 23:59:59', strtotime("-1 day"));
        $timeStart = !empty($this->option('time_start')) ? date('Y-m-d  23:59:59',
            strtotime($this->option('time_start'))) : date('Y-m-d H:i:s', strtotime($timeEnd . "-1 day"));
        $query['time_end'] = $timeEnd;
        $query['time_start'] = $timeStart;
        $service = new CurrencyOhlcvService();
        $quote = Cryptocurrency::where('symbol', $convert)->first();

        foreach ($allCrypts as $currency) {
            if (!$quote) {
                return false;
            }

            $quoteId = $quote->cryptocurrency_id;
            $result = $service->getCryptoCurrencyOhlcvApiData($currency->id, '', $timePeriod, $interval, $timeEnd,
                $timeStart, $convert);
            if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                $service->saveCryptoCurrencyOhlcvData($result['data']['quotes'], self::INTERVAL_MODELS[$interval],
                    $currency->cryptocurrency_id, $quoteId, $convert);
                $this->info('Done for ' . $result['data']['symbol'] . ' / ' . $convert);

            } else {
                if (!empty($result['status'])) {
                    $this->info('cryptocurrency_id ' . $currency->cryptocurrency_id . ' ' . $result['status']['error_message']);
                    Log::info($this->description . ' fails for pair ' . $currency->cryptocurrency_id . ' / ' . $convert . ' ' . $result['status']['error_message']);
                }
            }
            $microSec = 1000000;
            if (((int)date('i') == 0) || ((int)date('i') == 2)) {
                $timeSleep = 2.8;
            }else{
                $timeSleep = 1.2;
            }
            usleep($timeSleep * $microSec);

        }
        $sleep = new SleepService;

        $this->info('finish ' . $interval . ' save ohlcv for top 100 ' . date('H:i:s d-m-Y'));

        if ($interval === 'hourly' && $timePeriod === 'hourly') {
            sleep($sleep->intervalSleep('everyHour'));
        } elseif ($interval === 'daily' && $timePeriod === 'daily') {
            sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.cryptocurrency_historical_daily')));
        }
    }
}

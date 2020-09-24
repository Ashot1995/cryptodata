<?php 

namespace App\Console\Commands;

use App\TopCryptocurrency;
use App\Exchange;
use App\MarketPair;
use App\ExchangePairQuotes;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Services\ExchangePairQuoteService;
use App\Services\CoinBaseService;
use App\Cryptocurrency;
use App\Services\SleepService;
use Illuminate\Support\Facades\Config;


class UpdateExchangePairQuote extends Command
{
	/**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pairquote:daily {--limit=5000} {--coins=100} {--exclude_top=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange pairs quotes TOP 100 cryptocurrencies';

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

        $time_start = time();
        $limit = $this->option('limit');
        $limit_coins = (int)$this->option('coins');
        $count_exclude_coins = $this->option('exclude_top');

        if ($limit_coins < 200) {
            $topId = TopCryptocurrency::leftJoin('cryptocurrencies', 'top_cryptocurrencies.cryptocurrency_id', '=', 'cryptocurrencies.cryptocurrency_id')
            ->select(
                    'top_cryptocurrencies.cryptocurrency_id as cryptocurrency_id',
                    'cryptocurrencies.symbol as symbol'
                    )
            ->limit($limit_coins)
            ->get();
        }else{
            $notIcludedId = TopCryptocurrency::leftJoin('cryptocurrencies', 'top_cryptocurrencies.cryptocurrency_id', '=', 'cryptocurrencies.cryptocurrency_id')
                    ->limit($count_exclude_coins)
                    ->pluck('top_cryptocurrencies.cryptocurrency_id as cryptocurrency_id')
                    ->toArray();
            $topId = Cryptocurrency::select('cryptocurrency_id', 'symbol')->whereNotIn('cryptocurrency_id', $notIcludedId)->limit($limit_coins)->get();
        }
        $this->info('Start update exchange pairs quotes TOP ' . count($topId) . ' ' . date('H:i:s d-m-Y'));


        $exchanges = Exchange::select('id as cmc_id', 'exchange_id')->get();
        $exchanges_array = [];
        foreach ($exchanges as $key => $value) {
        	$exchanges_array[$value->cmc_id] = $value->exchange_id;
        }
        $credit_count = 0;
        $array_block_minute = [0,1,2];
        $sleep = new SleepService;
        $quoteService = new ExchangePairQuoteService;

        $now_minute = (int)date('i');
        $count_in_minute = 0;
        
        foreach ($topId as $key_topId => $value_topId) {
            if ( (int)date('i') != $now_minute ) {
                $now_minute = (int)date('i');
                $count_in_minute = 0;
            }

            // запрещенные минуты
            if (array_search($now_minute, $array_block_minute) !== false) {
                $sleep_time = (int)strtotime(date('Y-m-d H:03:00')) - (int)time() + 1;
                sleep($sleep_time);
            }

            dump('now_minute: ' . $now_minute);
            dump('count_in_minute: ' . $count_in_minute);

            $now_credit = $quoteService->saveTopPairsFromCMC($value_topId->symbol, $exchanges_array, $limit);
            if ($now_credit === false) {
                sleep($sleep->intervalSleep('everyMinute') + 1);
                $now_credit = $quoteService->saveTopPairsFromCMC($value_topId->symbol, $exchanges_array, $limit);
            }

            $count_in_minute += $now_credit;
            $credit_count += $now_credit;
            dump('Summ credit = ' . $credit_count);

            //Максимум 50 запросов
            if ( $count_in_minute >= 50 ) {
                sleep($sleep->intervalSleep('everyMinute'));
            }
            //Максимум 25 запросов в десятиминутки
            if ( ($now_minute % 10 == 0) && ($count_in_minute >= 25) ) {
                sleep($sleep->intervalSleep('everyMinute'));
            }

            

        	
        }

        dump('Summ credit = ' . $credit_count);


        $this->info('Time start ' . date('H:i:s d-m-Y', $time_start) );
        $this->info('Finish update exchange pairs quotes TOP ' . $limit_coins . ' ' . date('H:i:s d-m-Y') );

        if ((int)$limit_coins < 200) {
            sleep($sleep->intervalSleepEveryDayByTime(Config::get('commands_sleep.pairquote')));
        }else{
            sleep((int)strtotime('next sunday +8 hour') - (int)time());
        }
        

    }

}

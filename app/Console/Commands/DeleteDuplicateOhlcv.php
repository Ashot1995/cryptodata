<?php

namespace App\Console\Commands;


use App\Cryptocurrency;
use App\Http\DateFormat\DateFormat;
use App\Services\CoefficientService;
use App\Services\CryptoCurrencyService;
use App\Services\TnIndexService;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use App\OhlcvPair;
use DB;

class DeleteDuplicateOhlcv extends Command
{
	 /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ohlcv:duplicate {--exchange=binance} {--timeframe=1h}';

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
    	$exchange = $this->option('exchange');
    	$timeframe = $this->option('timeframe');
    	$className = 'App\OhlcvModels\ohlcv_' . $exchange . '_' . $timeframe;
    	$tableName = 'ohlcv_' . $exchange . '_' . $timeframe;
    	$start = time();
    	$exchange_pairs = OhlcvPair::where('exchange', $exchange)->where($timeframe, 1)->select('base_id', 'quote_id')->get()->toArray();
    	foreach ($exchange_pairs as $pair) {
    		$base_id = $pair['base_id'];
    		$quote_id = $pair['quote_id'];
                dump($base_id);
                dump($quote_id);
    	
            $duplicate = $className::select('id', 'timestamp')->where('base_id', $base_id)->where('quote_id', $quote_id)->get();
            if (count($duplicate) > 0) {
                $duplicate_array = $duplicate->groupBy('timestamp')->toArray();
            
                foreach ($duplicate_array as $timestamp => $dubl) {
                    if (count($dubl) > 1) {
                    dump($timestamp);
                        $f_id = $dubl[0]['id'];
                    // dd($f_id);
                        $className::where('timestamp', $timestamp)->where('id', '!=', $f_id)->delete();
                    }
                }

            }
     
            dump('TIME: '. ((int)time() - (int)$start));

    	}

    }
}
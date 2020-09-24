<?php

namespace App\Console\Commands;

use App\Services\CryptoCurrencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Services\SleepService;

class CryptocurrencyListing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptocurrency:listing_update {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update cryptocurrency listing data';

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
        $limit = (int)$this->option('limit');
        $service = new CryptoCurrencyService();
        $data = $service->getCryptoCurrencyApiDataByLimit($limit);
        $service->updateCryptoCurrencyData($data);

        $sleep = new SleepService;
        sleep($sleep->intervalSleep('everyTenMinute'));
        // 25 запросов
    }
}

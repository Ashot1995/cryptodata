<?php

namespace App\Console\Commands;

use App\Exchange;
use App\ExchangeQuote;
use App\Services\CoinBaseService;
use App\Services\SleepService;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class ExchangeListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:listings {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'save from exchange/listings/latest';

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
        //convert=usd
        $limit = !empty($this->option('limit')) ? $this->option('limit') : 100;

        $client = new Client();
        $response = $client->get(env('API_COIN') . 'exchange/listings/latest',
            [
                'headers' => ['X-CMC_PRO_API_KEY' => env('API_COIN_KEY')],
                'query' => ['limit' => $limit],
            ]);

        $body = $response->getBody();
        $result = json_decode($body, true);
        CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'exchange/listings/latest');
        foreach ($result['data'] as $key => $dataItem) {
            if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                $this->info('Done for ' . $dataItem['name']);
            } else {
                if (!empty($result['status'])) {
                    $this->info('Fails for  ' . $dataItem['name'] . ' ' . $result['status']['error_message']);
                    Log::info($this->description . ' fails for  ' . $dataItem['name'] . ' ' . $result['status']['error_message']);
                } else {
                    $this->info('Fails for  ' . $dataItem['name']);
                    Log::info($this->description . ' fails for  ' . $dataItem['name']);
                }
            }
            $exchange = Exchange::firstOrNew(['id' => $dataItem['id'], 'slug' => $dataItem['slug']]);
            $exchange->id = $dataItem['id'];
            $exchange->name = $dataItem['name'];
            $exchange->slug = $dataItem['slug'];
            $exchange->num_market_pairs = $dataItem['num_market_pairs'];
            $exchange->save();

            foreach ($dataItem['quote'] as $quoteKey => $quoteValue) {
                $quote = ExchangeQuote::where(['symbol' => $quoteKey, 'exchange_id' => $exchange->exchange_id])->first();

                if (!$quote) {
                    $quote = new ExchangeQuote();
                }

                $quote->exchange_id = $exchange->exchange_id;
                $quote->symbol = $quoteKey;
                $quote->volume_24h = !empty($quoteValue['volume_24h']) ? $quoteValue['volume_24h'] : null;
                $quote->volume_24h_adjusted = !empty($quoteValue['volume_24h_adjusted']) ? $quoteValue['volume_24h_adjusted'] : null;
                $quote->volume_7d = !empty($quoteValue['volume_7d']) ? $quoteValue['volume_7d'] : null;
                $quote->volume_30d = !empty($quoteValue['volume_30d']) ? $quoteValue['volume_30d'] : null;
                $quote->percent_change_volume_24h = !empty($quoteValue['percent_change_volume_24h']) ? $quoteValue['percent_change_volume_24h'] : null;
                $quote->percent_change_volume_7d = !empty($quoteValue['percent_change_volume_7d']) ? $quoteValue['percent_change_volume_7d'] : null;
                $quote->percent_change_volume_30d = !empty($quoteValue['percent_change_volume_30d']) ? $quoteValue['percent_change_volume_30d'] : null;
                $quote->save();

            }

        }

        $sleep = new SleepService;
        sleep($sleep->intervalSleep('everyTenMinute'));
        // 4 запроса

    }
}

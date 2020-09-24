<?php

namespace App\Console\Commands;

use App\CryptocurrencyHistorical;
use App\Services\CoinBaseService;
use App\Services\CryptoCurrencyService;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use App\Quote;
use App\Tag;
use App\CryptocurrencyTag;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CoinsSaveToBase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coins:save {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get coins from coinmarketcap and save indo database';

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
        $limit = !empty($this->option('limit')) ? $this->option('limit') : 5000;
        $convert = 'USD';
        $client = new Client();
        $response = $client->get(env('API_COIN') . 'cryptocurrency/listings/latest',
            [
                'headers' => ['X-CMC_PRO_API_KEY' => env('API_COIN_KEY')],
                'query' => ['limit' => $limit, 'sort' => 'market_cap'],
            ]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '',
            env('API_COIN') . 'cryptocurrency/listings/latest');

        /**
         * save or update :
         * platforms
         * cryptocurrency
         * quotes
         * tags
         */

        $service = new CryptoCurrencyService();

        foreach ($result['data'] as $key => $dataItem) {
            $fiats = config('fiats');

            // start platform save part

            if ($dataItem['platform'] && !empty($dataItem['platform']['id'])) {
                $platform =$service->savePlatformData($dataItem['platform']);
            }

            // end platform save part

            // start cryptocurrency save part
            $cryptocurrency = Cryptocurrency::where('id', $dataItem['id'])->first();
            $callInfo = false;

            if (!$cryptocurrency) {
                $cryptocurrency = new Cryptocurrency();
                $callInfo = true;
            }

            $cryptocurrency->id = $dataItem['id'];
            $cryptocurrency->name = !empty($dataItem['name']) ? $dataItem['name'] : null;
            $cryptocurrency->symbol = !empty($dataItem['symbol']) ? $dataItem['symbol'] : null;
            $cryptocurrency->slug = !empty($dataItem['slug']) ? $dataItem['slug'] : null;
            $cryptocurrency->circulating_supply = !empty($dataItem['circulating_supply']) ? $dataItem['circulating_supply'] : null;
            $cryptocurrency->max_supply = !empty($dataItem['max_supply']) ? $dataItem['max_supply'] : null;
            $cryptocurrency->date_added = !empty($dataItem['date_added']) ? $dataItem['date_added'] : null;
            $cryptocurrency->last_updated = !empty($dataItem['last_updated']) ? $dataItem['last_updated'] : null;
            $cryptocurrency->num_market_pairs = !empty($dataItem['num_market_pairs']) ? $dataItem['num_market_pairs'] : 0;
            $cryptocurrency->total_supply = !empty($dataItem['total_supply']) ? $dataItem['total_supply'] : null;
            $cryptocurrency->platform_id = !empty($platform) ? $platform->platform_id : null;
            $cryptocurrency->cmc_rank = !empty($dataItem['cmc_rank']) ? $dataItem['cmc_rank'] : null;
            $cryptocurrency->market_cap_order = $key + 1;

            $cryptocurrenciesHistorical = new CryptocurrencyHistorical();
            $cryptocurrenciesHistorical->cryptocurrency_id = $dataItem['id'];
            $cryptocurrenciesHistorical->circulating_supply = !empty($dataItem['circulating_supply']) ? $dataItem['circulating_supply'] : null;
            $cryptocurrenciesHistorical->max_supply = !empty($dataItem['max_supply']) ? $dataItem['max_supply'] : null;
            $cryptocurrenciesHistorical->total_supply = !empty($dataItem['total_supply']) ? $dataItem['total_supply'] : null;
            $cryptocurrenciesHistorical->price = $dataItem['quote'][$convert]['price'];

            if (!empty($result['status']) && $result['status']['error_code'] == 0) {
                $cryptocurrenciesHistorical->save();
                $this->info('Done for ' . $dataItem['symbol'] );
            } else if(!empty($result['status'])) {
                $this->info('Fails for  ' . $dataItem['symbol'] . ' ' . $result['status']['error_message']);
                Log::info($this->description . ' fails for  ' . $dataItem['symbol'] . ' ' . $result['status']['error_message']);
            } else {
                $this->info('Fails for  ' . $dataItem['symbol'] );
                Log::info($this->description . ' fails for  ' . $dataItem['symbol']);
            }

            if (in_array($dataItem['symbol'], $fiats)) {
                $cryptocurrency->currency_type = 'fiat';
            } else {
                $cryptocurrency->currency_type = 'cryptocurrency';
            }
            $cryptocurrency->save();
            // end cryptocurrency save part

            if ($cryptocurrency) {
                // start quotes save part

                foreach ($dataItem['quote'] as $quoteKey => $quoteValue) {
                    $quotes = Quote::where(['symbol' => $quoteKey, 'cryptocurrency_id' => $cryptocurrency->cryptocurrency_id])->first();
                    if (!$quotes) {
                        $quotes = new Quote();
                    }
                    $quotes->cryptocurrency_id = $cryptocurrency->cryptocurrency_id;
                    $quotes->symbol = $quoteKey;
                    $quotes->price = !empty($quoteValue['price']) ? $quoteValue['price'] : null;
                    $quotes->volume_24h = !empty($quoteValue['volume_24h']) ? $quoteValue['volume_24h'] : null;
                    $quotes->percent_change_1h = !empty($quoteValue['percent_change_1h']) ? $quoteValue['percent_change_1h'] : null;
                    $quotes->percent_change_24h = !empty($quoteValue['percent_change_24h']) ? $quoteValue['percent_change_24h'] : null;
                    $quotes->percent_change_7d = !empty($quoteValue['percent_change_7d']) ? $quoteValue['percent_change_7d'] : null;
                    $quotes->market_cap = !empty($quoteValue['market_cap']) ? $quoteValue['market_cap'] : null;
                    $quotes->last_updated = !empty($quoteValue['last_updated']) ? $quoteValue['last_updated'] : null;
                    $quotes->save();

                }

                // end quotes save part

                // start tags save part
                foreach ($dataItem['tags'] as $tagItem) {
                    $tag = Tag::where('name', $tagItem)->first();

                    if (!$tag) {
                        $tag = new Tag();
                        $tag->name = $tagItem;
                        $tag->save();
                    }

                    // todo need to check maybe just use relationship is enough and don't need add or update data
                    $cryptocurrenciesTags = CryptocurrencyTag::where(['tag_id' => $tag->id, 'cryptocurrency_id' => $cryptocurrency->cryptocurrency_id])->first();

                    if (!$cryptocurrenciesTags) {
                        $cryptocurrenciesTags = new CryptocurrencyTag();
                        $cryptocurrenciesTags->tag_id = $tag->id;
                        $cryptocurrenciesTags->cryptocurrency_id = $cryptocurrency->cryptocurrency_id;
                        $cryptocurrenciesTags->save();
                    }
                }
                // end tags save part

                if ($callInfo) {
                    Artisan::call('cryptocurrency:info', ["--id" => $cryptocurrency->id]);
                    Artisan::call('cryptocurrency:map', ["--symbol" => $cryptocurrency->symbol]);
                }
            }

        }
        sleep(Config::get('commands_sleep.coins_save'));
    }
}

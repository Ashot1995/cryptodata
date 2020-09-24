<?php

namespace App\Console\Commands;

use App\Services\CoinBaseService;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Cryptocurrency;
use App\MarketPair;
use Illuminate\Support\Facades\Artisan;

class MarketPairs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:pairs';

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
        $cryptocurrencies = Cryptocurrency::select('cryptocurrency_id', 'id', 'num_market_pairs', 'symbol', 'pairs_updated_date')->orderBy('cryptocurrency_id')->with('marketPairsLeft')->with('marketPairsRight')->get();
        $client = new Client();

        foreach ($cryptocurrencies as $key => $value) {

            if ($value->pairs_updated_date == date('Y-m-d')) {
                continue;
            }

            if ($value) {
                $this->updateByValue($value, $client);
            }
            sleep(1);
        }

    }

    public function updateByValue($value, $client) {
        $numMarketPairs = 100;
        $numMarketPairs2 = 0;

        if ($value->num_market_pairs && $value->num_market_pairs >= 5000) {
            $numMarketPairs = 5000;
            $numMarketPairs2 = $value->num_market_pairs - 5000;
        } elseif ($value->num_market_pairs) {
            $numMarketPairs = $value->num_market_pairs;
        }

        try {
            $response = $client->get(env('API_COIN') . 'cryptocurrency/market-pairs/latest',
                [
                    'headers' => ['X-CMC_PRO_API_KEY' => env('API_COIN_KEY')],
                    'query' => ['id' => $value->id, 'limit' => $numMarketPairs],
                ]);
            $body = $response->getBody();
            $result = json_decode($body, true);
            CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'cryptocurrency/market-pairs/latest');

        } catch (ClientException $exception) {
            $result = json_decode($exception->getResponse()->getBody()->getContents(), true);
            $this->info($result['status']);
            CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'cryptocurrency/market-pairs/latest');
        }

        if ($numMarketPairs2) {
            try {
                $response2 = $client->get(env('API_COIN') . 'cryptocurrency/market-pairs/latest',
                    [
                        'headers' => ['X-CMC_PRO_API_KEY' =>  env('API_COIN_KEY')],
                        'query' => ['id' => $value->id, 'limit' => $numMarketPairs2, 'start' => 5001],
                    ]);
                $body2 = $response2->getBody();
                $result2 = json_decode($body2, true);
                CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'cryptocurrency/market-pairs/latest');
                $result['data']['market_pairs'] = array_merge($result['data']['market_pairs'], $result2['data']['market_pairs']);

            } catch (ClientException $exception) {
                $result2 = json_decode($exception->getResponse()->getBody()->getContents(), true);
                $this->info($result['status']);
                CoinBaseService::saveRequestCommands($result2['status']['error_code'], $result2['status']['credit_count'], '', '', env('API_COIN') . 'cryptocurrency/market-pairs/latest');
            }

        }

        $value->pairs_updated_date = date('Y-m-d');
        $value->save();

        if (!empty($result['data'])) {

            foreach ($result['data']['market_pairs'] as $dataItem) {

                if ($dataItem['market_pair']) {
                    $marketPair = $dataItem['market_pair'];
                    $marketPairArr = explode('/', $marketPair);
                    $firstCryptocurrency = Cryptocurrency::select('cryptocurrency_id', 'id')->where('symbol', $marketPairArr[0])->first();
                    $secondCryptocurrency = Cryptocurrency::select('cryptocurrency_id', 'id')->where('symbol', $marketPairArr[1])->first();

                    if (!$firstCryptocurrency) {
                        Artisan::call('cryptocurrency:map', ["--symbol" => $marketPairArr[0]]);
                        $firstCryptocurrency = Cryptocurrency::select('cryptocurrency_id', 'id')->where('symbol', $marketPairArr[0])->first();
                    }

                    if (!$secondCryptocurrency) {
                        Artisan::call('cryptocurrency:map', ["--symbol" => $marketPairArr[1]]);
                        $secondCryptocurrency = Cryptocurrency::select('cryptocurrency_id', 'id')->where('symbol', $marketPairArr[1])->first();
                    }

                    if ($firstCryptocurrency && $secondCryptocurrency) {
                        $firstId = $firstCryptocurrency->cryptocurrency_id;
                        $secondId = $secondCryptocurrency->cryptocurrency_id;

                        $couple1 = MarketPair::where(['string1_id' => $firstId, 'string2_id' => $secondId])->first();

                        if (!$couple1) {
                            $couple = new MarketPair();
                            $couple->string1_id = $firstId;
                            $couple->string2_id = $secondId;
                            $couple->save();
                        }

                        $this->info("Done for " . $marketPair);

                    } else {
                        $this->info("empty data for " . $marketPairArr[0] . " and " . $marketPairArr[1]);
                    }
                }
            }

        } else {
            $this->info("empty data for " . $value->symbol . ' with Id' . $value->id );
        }

    }
}

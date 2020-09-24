<?php

namespace App\Console\Commands;

use App\Services\CoinBaseService;
use Illuminate\Console\Command;
use App\Cryptocurrency;
use GuzzleHttp\Client;
use App\Console\Commands\MarketPairs;
use Illuminate\Support\Facades\Log;


class UpdatePairs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:pairs';

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
        // todo данная команда нигде не используется
        $client = new Client();
        $response = $client->get(env('API_COIN') . 'cryptocurrency/listings/latest',
            [
                'headers' => ['X-CMC_PRO_API_KEY' =>  env('API_COIN_KEY')],
            ]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'cryptocurrency/listings/latest');

        $marketPairs = new MarketPairs();

        foreach ($result['data'] as $coinData ) {
            $cryptocurrency = Cryptocurrency::where('id', $coinData['id'])->first();

            if ($coinData['num_market_pairs'] != $cryptocurrency['num_market_pairs']) {
                $marketPairs->updateByValue($cryptocurrency, $client);
                $cryptocurrency->num_market_pairs = $coinData['num_market_pairs'];
                $cryptocurrency->save();
                \Log::info('updated for ' . $cryptocurrency->symbol);
            }
        }

        // get from Cryptocurrency map
    }
}

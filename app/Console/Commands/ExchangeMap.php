<?php

namespace App\Console\Commands;

use App\Services\CoinBaseService;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Exchange;
use DateTime;
use Illuminate\Support\Facades\Log;

class ExchangeMap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:map {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get CoinMarketCap ID map and save to DB';

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
        $query = [];
        $query['limit'] = $limit;
        $client = new Client();
        $response = $client->get(env('API_COIN') . 'exchange/map',
            [
                'headers' => ['X-CMC_PRO_API_KEY' => env('API_COIN_KEY')],
                'query' => $query,
            ]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'exchange/map');


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
            $exchange = Exchange::firstOrNew(['id' => $dataItem['id']]);

            $exchange->logo = isset($dataItem['logo']) ? $dataItem['logo'] : null;
            $exchange->name = isset($dataItem['name']) ? $dataItem['name'] : null;
            $exchange->slug = isset($dataItem['slug']) ? $dataItem['slug'] : null;
            $exchange->is_active = isset($dataItem['is_active']) ? $dataItem['is_active'] : null;
            $firstData = new DateTime($dataItem['first_historical_data']);
            $exchange->first_historical_data = $firstData->format('Y-m-d H:i:s');
            $lastData = new DateTime($dataItem['last_historical_data']);
            $exchange->last_historical_data =  $lastData->format('Y-m-d H:i:s');
            $exchange->save();

        }
    }
}

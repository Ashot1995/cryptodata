<?php

namespace App\Console\Commands;

use App\Services\CoinBaseService;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Cryptocurrency;
use App\Platform;
use DateTime;
use Illuminate\Support\Facades\Log;

class CryptocurrencyMap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptocurrency:map {--limit=} {--symbol=}';

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
        $symbol = !empty($this->option('symbol')) ? $this->option('symbol') : '';
        $query = [];
        $fiats = config('fiats');

        if ($limit) {
            $query['limit'] = $limit;
        }

        if ($symbol){
            $query['symbol'] = $symbol;
        }

        $client = new Client();
        $response = $client->get(env('API_COIN') . 'cryptocurrency/map',
            [
                'headers' => ['X-CMC_PRO_API_KEY' =>  env('API_COIN_KEY')],
                'query' => $query,
            ]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        CoinBaseService::saveRequestCommands($result['status']['error_code'], $result['status']['credit_count'], '', '', env('API_COIN') . 'cryptocurrency/map');


        foreach ($result['data'] as $key => $dataItem) {
            // start platform save part

            if (!empty($result['status']) && $result['status']['error_code'] == 0) {

                $this->info('Done for ' . $dataItem['symbol'] );
            } else if(!empty($result['status'])) {
                $this->info('Fails for pair ' . $dataItem['symbol'] . ' ' . $result['status']['error_message']);
                Log::info($this->description . ' fails for pair ' . $dataItem['symbol'] . ' ' . $result['status']['error_message']);
            } else {
                $this->info('Fails for pair ' . $dataItem['symbol'] );
                Log::info($this->description . ' fails for pair ' . $dataItem['symbol']);
            }

            if ($dataItem['platform'] && !empty($dataItem['platform']['id'])) {
                $platform = Platform::where('id', $dataItem['platform']['id'])->first();

                if (!$platform) {
                    $platform = new Platform();
                };

                $platform->id = $dataItem['platform']['id'];
                $platform->name = !empty($dataItem['platform']['name']) ? $dataItem['platform']['name'] : null;
                $platform->symbol = !empty($dataItem['platform']['symbol']) ? $dataItem['platform']['symbol'] : null;
                $platform->slug = !empty($dataItem['platform']['slug']) ? $dataItem['platform']['slug'] : null;
                $platform->save();
            }

            $cryptocurrency = Cryptocurrency::firstOrNew(['id'=>$dataItem['id']]);

            $cryptocurrency->id = $dataItem['id'];
            $cryptocurrency->name = !empty($dataItem['name']) ? $dataItem['name'] : null;
            $cryptocurrency->symbol = !empty($dataItem['symbol']) ? $dataItem['symbol'] : null;
            $cryptocurrency->slug = !empty($dataItem['slug']) ? $dataItem['slug'] : null;
            $cryptocurrency->is_active = !empty($dataItem['is_active']) ? $dataItem['is_active'] : 0;

            if (in_array($dataItem['symbol'], $fiats)) {
                $cryptocurrency->currency_type = 'fiat';
            } else {
                $cryptocurrency->currency_type = 'cryptocurrency';
            }

            $firstData = new DateTime($dataItem['first_historical_data']);
            $cryptocurrency->first_historical_data = $firstData->format('Y-m-d H:i:s');
            $lastData = new DateTime($dataItem['last_historical_data']);
            $cryptocurrency->last_historical_data =  $lastData->format('Y-m-d H:i:s');
            $cryptocurrency->save();
        }
    }
}

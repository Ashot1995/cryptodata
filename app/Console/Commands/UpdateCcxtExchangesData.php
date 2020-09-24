<?php

namespace App\Console\Commands;

use App\Models\CcxtCryptocurrency;
use App\Models\CcxtExchanges;
use App\Models\CcxtMarketPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;


class UpdateCcxtExchangesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ccxt_exchanges_pairs_and_currencies';

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
        $exchanges = \ccxt\Exchange::$exchanges;
        $this->info('Старт стбора данных по обменникам с API');
        $exchangesFullData = Cache::remember('ccxt_exchanges_full_data', 120, function () use ($exchanges) {
            return $this->getFullData($exchanges);
        });
        $this->info('Данные собраны');
        $this->saveExchangesAndCryptocurrenciesDataToDB($exchangesFullData);
        $this->saveMarketPairsDataToDB($exchangesFullData);

        return $exchangesFullData;
    }

    protected function getFullData($exchanges)
    {
        $exchangesFullData = [];
        $left = count($exchanges);
        foreach ($exchanges as $exchangeName) {
            if ($exchangeName === 'anxpro') {
                continue;
            }
            $exchangeClass = "\\ccxt\\" . $exchangeName;
            $exchange = new $exchangeClass();

            try {
                $exchange->load_markets();
            } catch (\Exception $e) {

            }
            $insert = [
                'name' => $exchange->name,
                'slug' => $exchange->id,
            ];
            $exchangesInsertData[] = $insert;
            $insert['markets'] = $exchange->markets;
            $insert['symbols'] = $exchange->symbols;
            $insert['currencies'] = $exchange->currencies;
            $exchangesFullData[] = $insert;
            --$left;
            $this->info($left);
        }
        return $exchangesFullData;
    }

    protected function saveExchangesAndCryptocurrenciesDataToDB(array $exchangesFullData)
    {
        $exchangesInsert = [];
        $cryptoCurrencyInsert = [];
        foreach ($exchangesFullData as $datum) {
            $exchangesInsert[] = [
                'name' => $datum['name'],
                'slug' => $datum['slug']
            ];
            if (empty($datum['currencies'])) {
                continue;
            }
            foreach ($datum['markets'] as $code => $market) {
                if (!array_key_exists($market['base'], $cryptoCurrencyInsert)) {
                    $base = $market['base'];
                    $cryptoCurrencyInsert[$base] = [
                        'symbol' => $base,
                        'slug' => $base . '_' . strtolower($base)
                    ];
                } elseif (!array_key_exists($market['quote'], $cryptoCurrencyInsert)) {
                    $quote = $market['quote'];
                    $cryptoCurrencyInsert[$quote] = [
                        'symbol' => $quote,
                        'slug' => $quote . '_' . strtolower($quote)
                    ];
                } else {
                    continue;
                }
            }
        }
        CcxtExchanges::insert($exchangesInsert);
        $this->info('Сохранено ' . count($exchangesInsert) . ' обменников');
        CcxtCryptocurrency::insert($cryptoCurrencyInsert);
        $this->info('Сохранено ' . count($cryptoCurrencyInsert) . ' валют');

    }

    protected function saveMarketPairsDataToDB(array $exchangesFullData)
    {
        $exchanges = CcxtExchanges::query()->get()->keyBy('name')->toArray();
        $currencies = CcxtCryptocurrency::query()->get()->keyBy('symbol')->toArray();
        $marketPairsInsert = [];
        foreach ($exchangesFullData as $exchangeApi) {
            $name = $exchangeApi['name'];
            if (!$exchangeApi['markets']) {
                continue;
            }
            foreach ($exchangeApi['markets'] as $market) {
                if (!isset($exchanges[$name])) {
                    continue;
                }
                $base = $market['base'];
                $quote = $market['quote'];
                $key = $base . '/' . $quote;
                if (array_key_exists($key, $marketPairsInsert)) {
                    continue;
                }
                $marketPairsInsert[$key] = [
                    'base_coin_id' => $currencies[$base]['id'],
                    'quote_coin_id' => $currencies[$quote]['id']
                ];

            }
        }
        $chunckedMarketPairsData = array_chunk($marketPairsInsert, 2000);
        foreach ($chunckedMarketPairsData as $insert) {
            CcxtMarketPair::insert($insert);
        }
        $this->info('Сохранено ' . count($marketPairsInsert) . ' пар');
    }
}
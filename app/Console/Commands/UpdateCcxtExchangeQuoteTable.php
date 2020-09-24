<?php

namespace App\Console\Commands;

use App\Models\CcxtExchanges;
use App\Models\CcxtExchangesMarketsQuote;
use App\Models\CcxtMarketPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class UpdateCcxtExchangeQuoteTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ccxt_exchanges_quote_table';

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
        $exchangeQuotesInsertData = Cache::remember('ccxt_exchange_quote_v1', 3600, function () {
            return $this->getFullData();
        });
        $formattedData = [];
        foreach ($exchangeQuotesInsertData as $datum) {
            $formattedData[] = [
                'exchange_id' => $datum['exchange_id'],
                'market_pair_id' => $datum['market_pair_id'],
                'symbol' => $datum['symbol'],
                'timestamp' => null,
                'change_24h' => $datum['ticker']['change'],
                'price' => $datum['ticker']['average'],
                'base_volume_24h' => $datum['ticker']['baseVolume'],
                'quote_volume_24h' => $datum['ticker']['quoteVolume'],
                'percent_value_24h' => $datum['ticker']['percentage'],
            ];
        }
        $chankedInsert = array_chunk($formattedData, 2000);
        foreach ($chankedInsert as $insert) {
            CcxtExchangesMarketsQuote::insert($insert);
        }
        $this->info('Сохранено ' . count($formattedData) . ' записей в таблицу ccxt_exchange_quotes');
    }

    protected function getFullData()
    {
        $exchanges = \ccxt\Exchange::$exchanges;
        $exchangesDB = CcxtExchanges::query()->get()->keyBy('name')->toArray();
        $ccxtMarkertPairsDB = CcxtMarketPair::query()
            ->select(
                'ccxt_market_pairs.id as id',
                DB::raw('concat(c1.symbol, "/", c2.symbol) as symbols')
            )
            ->join('ccxt_cryptocurrencies as c1', 'c1.id', '=',  'ccxt_market_pairs.base_coin_id')
            ->join('ccxt_cryptocurrencies as c2', 'c2.id', '=',  'ccxt_market_pairs.quote_coin_id')
            ->get()->keyBy('symbols')->toArray();
        $exchangeQuotesInsertData = [];
        $exchangeCount = 0;
        foreach ($exchanges as $exchangeName) {
            if ($exchangeName === 'anxpro') {
                continue;
            }
            $exchangeClass = "\\ccxt\\" . $exchangeName;
            $exchange = new $exchangeClass();
            if (!$exchange->has('fetchMarkets')) {
                continue;
            }
            if (!$exchange->has('fetchTicker')) {
                continue;
            }
            try {
                $exchange->load_markets();
            } catch (\Exception $e) {
                $this->info('Нет доступа к обменнику' . $exchangeName);
                continue;
            }

            if ($exchange->has('fetchTickers')) {
                $exName = $exchange->name;
                try {
                    $tickers = $exchange->fetchTickers();
                } catch (\Exception $e) {
                    $this->info('Нет данных для всех пар в обменнике' . $exchangeName);
                    continue;
                }
                foreach ($tickers as $symbol => $ticker) {
                    if (!isset($ccxtMarkertPairsDB[$symbol])) {
                        continue;
                    }
                    $exchangeQuotesInsertData[] = [
                        'exchange_id' => $exchangesDB[$exName]['id'],
                        'market_pair_id' => $ccxtMarkertPairsDB[$symbol]['id'],
                        'symbol' => $symbol,
                        'ticker' => $ticker
                    ];
                }
                ++$exchangeCount;
                $this->info('Добавлены тикеры для ' . $exchangeName . ' ' . $exchangeCount);
            } else {
                $markets = $exchange->markets;
                foreach ($markets as $market) {
                    $symbol = $market['symbol'];
                    $exName = $exchange->name;
                    try {
                        $ticker = $exchange->fetchTicker($symbol);
                    } catch (\Exception $e) {
                        $this->info('Нет данных для пар ' . $symbol . ' ' . $exchangeName);
                        continue;
                    }
                    if (!isset($ccxtMarkertPairsDB[$symbol])) {
                        continue;
                    }
                    $exchangeQuotesInsertData[] = [

                        'exchange_id' => $exchangesDB[$exName]['id'],
                        'market_pair_id' => $ccxtMarkertPairsDB[$symbol]['id'],
                        'symbol' => $symbol,
                        'ticker' => $ticker
                    ];
                }
                ++$exchangeCount;
                $this->info('Добавлены тикеры для ' . $exchangeName . ' ' . $exchangeCount);
            }
        }
        return $exchangeQuotesInsertData;
    }
}

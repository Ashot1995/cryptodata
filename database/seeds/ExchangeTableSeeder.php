<?php

use Illuminate\Database\Seeder;

class ExchangeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('exchanges')) {
            $exchangesExists = \App\Exchange::pluck('id', 'slug')->all();
            $lastExchange = \App\Exchange::orderBy('id', 'DESC')->first();
            $initialId = $lastExchange ? $lastExchange->id : 1;

            $exchanges = [
              [
                  'exchange_id' => 0,
                  'name' => 'Coinmarketcap',
                  'slug' => 'CMC',
                  'is_active' => 1,
                  'num_market_pairs' => 16055
              ]
            ];

            foreach ($exchanges as $exchange) {
                if (array_key_exists($exchange['slug'], $exchangesExists)) {
                    continue;
                }
                $exchange['id'] = ++$initialId;
                \App\Exchange::insert($exchange);
            }
        }
    }
}

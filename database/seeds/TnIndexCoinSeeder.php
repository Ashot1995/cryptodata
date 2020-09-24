<?php

use App\TnIndexCoin;
use Illuminate\Database\Seeder;
use App\TopCryptocurrency;


class TnIndexCoinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $topCrypts200 = TopCryptocurrency::limit(200)->pluck('cryptocurrency_id')->toArray();
        $topCrypts100 = array_slice($topCrypts200, 0, 100);
        $topCrypts50 = array_slice($topCrypts200, 0, 50);
        $topCrypts10 = array_slice($topCrypts200, 0, 10);
        $indexes = [
            'Tn10' => $topCrypts10,
            'Tn50' => $topCrypts50,
            'Tn100' => $topCrypts100,
        	'Tn200' => $topCrypts200,
        ];
        $data_array = [];
        foreach ($indexes as $index_name => $index_value) {
        	foreach ($index_value as $coin) {
        		$data_array[] = [
        			'index_name' => $index_name,
                    'cryptocurrency_id' => $coin,
	                'default' => true,
	                'created_at' => date('Y-m-d H:i:s'),
	                'updated_at' => date('Y-m-d H:i:s'),
        		];
        	}
        }
        TnIndexCoin::insert($data_array);
    }
}

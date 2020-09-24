<?php

namespace App\Console\Commands;


use App\Cryptocurrency;
use App\Http\DateFormat\DateFormat;
use App\Services\CoefficientService;
use App\Services\CryptoCurrencyService;
use App\Services\TnIndexService;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\OhlcvPair;
use DB;

class ParseATH extends Command
{
	 /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:ath {--min=10} {--max=20}';

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
        $string = "";
        $crypt = json_decode($string);
        foreach ($crypt as $key => $value) {
            
            Cryptocurrency::where('cryptocurrency_id', $value->cryptocurrency_id)->update(['ath' => $value->ath, 'ath_date' => $value->ath_date]);
        }
        dd('finish');
        $crypt = Cryptocurrency::select('cryptocurrency_id', 'ath', 'ath_date')->whereNotNull('ath')->get()->toArray();
        $crypt = json_encode(json_encode($crypt));
        dd($crypt);
        dd();

        $min = $this->option('min');
    	$max = $this->option('max');
        $cryptocurrency = Cryptocurrency::select('cryptocurrency_id', 'slug')->orderBY('cryptocurrency_id', 'asc')->whereNull('ath')->get()->toArray();
        $client = new Client();
        $start = time();
        foreach ($cryptocurrency as $key => $value) {
                dump($value['slug']);
            
            try {
                $response = $client->get('https://coinmarketcap.com/currencies/' . $value['slug']);
                $response = $response->getBody()->getContents();
                $str = substr($response, strpos($response, 'All Time High'));
                $str = substr($str, 0,strpos($str, '</td>'));
                $str = strip_tags($str);
                $str = explode("\n", $str);
                // $str = str_replace("\n", "", $str);
                $array = [];
                foreach ($str as $key1 => $value1) {
                    if (($value1 != '') && ($value1 != ' ')) {
                        $array[] = $value1;
                    }
                }
                dump($array);
                if (count($array) > 3) {
                    $ath = (float)$array[1];
                    $date = str_replace('(', '', $array[3]);
                    $date = str_replace(')', '', $date);
                    $date = str_replace(',', '', $date);
                    dump($date);
                    $date = date('Y-m-d H:i:s', strtotime($date));

                    
                    dump($date);
                    dump($ath);
                   

                    Cryptocurrency::where('cryptocurrency_id', $value['cryptocurrency_id'])->update(['ath' => $ath, 'ath_date' => $date]);
                }else{
                    Cryptocurrency::where('cryptocurrency_id', $value['cryptocurrency_id'])->update(['ath' => 0]);
                }
                dump($value['cryptocurrency_id']);
            } catch (ClientException $exception) {
                dump($exception->getResponse()->getStatusCode());
            }

                dump('TIME: '. ((int)time() - (int)$start) . ' sek');
                
                sleep(rand( $min , $max ));
        }
    	

    }
}
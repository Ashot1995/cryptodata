<?php

namespace App\Console\Commands;

use App\Cryptocurrency;
use App\TopCryptocurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopCryptocurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'top:save {--limit=}';

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
        $limit = !empty($this->option('limit')) ? $this->option('limit') : 200;
        $topCrypts = Cryptocurrency::whereNotNull('market_cap_order')->limit($limit)->orderBy('market_cap_order')
            ->get([
                'cryptocurrency_id',
                'id as cryptocurrency_coin_id',
                DB::raw('CURRENT_TIMESTAMP as created_at'),
                DB::raw('CURRENT_TIMESTAMP as updated_at')
            ])->toArray();
        TopCryptocurrency::query()->truncate();
        TopCryptocurrency::insert($topCrypts);
        $this->info('Обновлены топ крипты');
        Log::info('Обновлены топ крипты');
        sleep(Config::get('commands_sleep.top_save'));
    }
}

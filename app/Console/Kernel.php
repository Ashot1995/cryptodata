<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('coins:save')
                  ->daily();
        $schedule->command('exchange:info')
            ->dailyAt('00:30');
        $schedule->command('exchange:listings')
            ->everyFiveMinutes();
        $schedule->command('cryptocurrency:historical')
            ->dailyAt('07:00');
        $schedule->command('pairquote:daily')
            ->dailyAt('00:30');

        $schedule->command('cryptocurrency:historical --interval=daily --time_period=daily')
            ->dailyAt('07:18');

        $schedule->command('tn:indexes')
            ->dailyAt('07:30');
        $schedule->command('coefficients:daily')
            ->dailyAt('07:33');
        $schedule->command('global:metrics')
            ->dailyAt('07:36');
        $schedule->command('global:historical')
            ->dailyAt('07:39');
        $schedule->command('global:historical --interval=daily')
        ->dailyAt('07:42');
        $schedule->command('coefficients:get --interval=weekly')
            ->weeklyOn(1, '07:45');
        $schedule->command('roi:historical')
            ->weeklyOn(1, '04:45');
        $schedule->command('crypto:sortino')
            ->weeklyOn(1, '07:55');
        $schedule->command('atn:price')
            ->weeklyOn(1, '08:00');
        $schedule->command('coefficients:get --interval=monthly')
            ->monthlyOn(1, '07:48');
        $schedule->command(' annualized:return')
            ->monthlyOn(1, '06:48');
        $schedule->command('top:save')
            ->monthly();


        $schedule->command('update:exchange_ohlcv_full_data_interval_minute')
            ->everyMinute();
        $schedule->command('cryptocurrency:listing_update')
            ->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

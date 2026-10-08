<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CronDataBackUp;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CronDataBackUp::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('backup:run')->daily(); //Run the task every day at midnight
        $schedule->command('backup:clean')->daily();
         
        $time = "23:58";
        $schedule->command('sustainability:autocreate-stage4')->weekly()->sundays()->at($time);
        $schedule->command('sustainability:autocreate-stage3-employee')->weekly()->sundays()->at($time);
        
        $stage6Commands = [
            'sustainability:auto-create-stage6-records-us',
            'sustainability:auto-create-stage6-records-uk',
            'sustainability:auto-create-stage6-records-eu',
            'sustainability:auto-create-stage6-records-ca',
            'sustainability:auto-create-stage6-records-in',
        ];
        foreach ($stage6Commands as $stage6Command) {
            $schedule->command($stage6Command)
                ->dailyAt($time)
                ->when(function () {
                    $epochDay = (int) floor(now()->timestamp / 86400);

                    return $epochDay % 2 === 0 || now()->endOfMonth()->isSameDay(now());
                });
        }
        $schedule->command('sustainability:autocreate-stage5')->when(fn () => now()->endOfMonth()->isSameDay(now()))->at($time);

        $schedule->command('allocation:get-container-allocation')->daily();

        // Wholesale client reminders: first at 7 days, then every 3 days until delivery
        $schedule->command('wholesale:send-client-reminders')->dailyAt('09:30');

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

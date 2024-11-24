<?php

declare(strict_types=1);

namespace App\Application\Console;

use Codefy\Framework\Console\ConsoleKernel;
use Codefy\Framework\Scheduler\Schedule;
use Qubus\Exception\Data\TypeException;

use function App\Shared\Helpers\home_url;
use function App\Shared\Helpers\set_url_scheme;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;

use const CURLOPT_RETURNTRANSFER;

class Kernel extends ConsoleKernel
{
    /**
     * Place all your scheduled tasks here.
     *
     * @param Schedule $schedule
     * @return void
     * @throws TypeException
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(command: 'email:send')->everyMinute();
        /*$schedule->command(command: function () {
            $command = set_url_scheme(home_url('cron/'));
            $ch = curl_init($command);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_exec($ch);
            curl_close($ch);
        })->everyMinute();*/
    }

    /**
     * Place all your commands here that need to be registered
     * to your application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $commands = $this->codefy->make('codefy.config')->getConfigKey('app.commands');

        foreach ($commands as $command) {
            $command = $this->codefy->make($command);
            $this->registerCommand($command);
        }
    }
}

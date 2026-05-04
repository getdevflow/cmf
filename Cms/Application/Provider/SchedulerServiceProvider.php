<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Scheduler\Schedule;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\EventDispatcher\ActionFilter\Action;
use ReflectionException;

use function App\Shared\Helpers\is_ssl;
use function App\Shared\Helpers\set_url_scheme;
use function Codefy\Framework\Helpers\env;

use const CURLOPT_RETURNTRANSFER;

class SchedulerServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        Action::getInstance()->addAction(hook: 'scheduler', callback: function (Schedule $schedule) {
            $schedule->php(script: 'codex queue:run')->everyMinute();
            $schedule->command(command: 'cache:clear')->hourly();
            $schedule->command(command: 'cookies:clear')->hourly();
            $schedule->command(command: 'logs:clear')->hourly();
        }, priority: 5);

        /** Cron Schedule */
        Action::getInstance()->addAction(hook: 'scheduler', callback: function (Schedule $schedule) {
            $schedule->command(command: function () {
                $protocol = is_ssl() ? 'https://' : 'http://';
                $cron = set_url_scheme(
                    url: env(key: 'APP_BASE_URL') . 'admin/cron/master/',
                    scheme: $protocol
                );
                $ch = curl_init($cron);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_exec($ch);
                curl_close($ch);
            })->everyMinute();
        }, priority: 5);
    }
}

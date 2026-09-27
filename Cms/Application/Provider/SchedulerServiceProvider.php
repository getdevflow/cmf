<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Scheduler\Schedule;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\EventDispatcher\ActionFilter\Action;
use ReflectionException;

class SchedulerServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        Action::getInstance()->addAction('scheduler', static function (Schedule $schedule): void {
            $schedule->command('queue:run')->everyMinute()->onlyOneInstance();
            $schedule->command('cache:clear')->hourly()->onlyOneInstance();
            $schedule->command('cookies:clear')->hourly()->onlyOneInstance();
            $schedule->command('logs:clear')->hourly()->onlyOneInstance();
            $schedule->command('cms:cron')->everyMinute()->onlyOneInstance();
        }, priority: 5);
    }
}

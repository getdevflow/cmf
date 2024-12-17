<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use App\Application\Devflow;
use App\Infrastructure\Services\Updater;
use Codefy\Framework\Console\ConsoleCommand;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;

final class UpdaterCheckCommand extends ConsoleCommand
{
    protected string $name = 'cms:update:check';

    protected string $description = 'Checks if new updates are available.';

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function handle(): int
    {
        $updater = new Updater();
        $updater->setCurrentVersion(Devflow::inst()->release());
        $updater->setUpdateUrl(updateUrl: 'https://devflow-cmf.s3.amazonaws.com/api/1.1/update-check');

        if ($updater->checkUpdate() !== false) {
            if ($updater->newVersionAvailable()) {
                $this->terminalInfo(sprintf(
                    'Release %s is available',
                    $updater->getLatestVersion()
                ));
            } else {
                $this->terminalInfo(sprintf(
                    'Devflow %s is at the latest release.',
                    Devflow::inst()->release()
                ));
            }
        }
        return ConsoleCommand::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionException;
use ReflectionException;

use function App\Shared\Helpers\cms_nodeq_login_details;
use function App\Shared\Helpers\cms_nodeq_reset_password;

class EmailSendCommand extends ConsoleCommand
{
    protected string $name = 'email:send';

    protected string $description = 'Send queued emails.';

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws EnvironmentIsBrokenException
     * @throws ReflectionException
     * @throws SessionException
     */
    public function handle(): int
    {
        $this->terminalRaw('<comment>Emails are being sent...</comment>');

        cms_nodeq_login_details();
        cms_nodeq_reset_password();

        $this->terminalRaw('<comment>Sending emails complete.</comment>');

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

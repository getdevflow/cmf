<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;

use function App\Shared\Helpers\generate_unique_key;

class GenerateSaltStringCommand extends ConsoleCommand
{
    protected string $name = 'generate:salt';

    protected string $description = 'Generates a random string for salt.';

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    public function handle(): int
    {
        $salt = generate_unique_key(length: 32);

        $this->terminalRaw(string: sprintf(
            'Salt: <comment>%s</comment>',
            $salt
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Key;

class GenerateEncryptionKeyCommand extends ConsoleCommand
{
    protected string $name = 'generate:key';

    protected string $description = 'Generates a random encryption key.';

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    public function handle(): int
    {
        $key = Key::createNewRandomKey()->saveToAsciiSafeString();

        $this->terminalRaw(string: sprintf(
            'Encryption Key: <comment>%s</comment>',
            $key
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

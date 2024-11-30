<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use App\Domain\User\Query\Trait\PopulateUserQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_false__;

final class FindUsersQueryHandler implements QueryHandler
{
    use PopulateUserQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    public function handle(FindUsersQuery|Query $query): array
    {
        $sql = "SELECT u.*, m.meta_value AS role FROM {$this->dfdb->basePrefix}user u 
                JOIN {$this->dfdb->basePrefix}usermeta m 
                ON (m.user_id = u.user_id AND m.meta_key = '{$this->dfdb->prefix}role')";

        $data = $this->dfdb->getResults(query: $sql, output: Database::ARRAY_A);

        $users = [];

        if (!is_false__($data)) {
            foreach ($data as $user) {
                $users[] = $this->populate($user);
            }
        }

        return $users;
    }
}

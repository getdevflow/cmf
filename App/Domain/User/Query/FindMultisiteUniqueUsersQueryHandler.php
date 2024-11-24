<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use App\Domain\User\Query\Trait\PopulateUserQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_null__;

final class FindMultisiteUniqueUsersQueryHandler implements QueryHandler
{
    use PopulateUserQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    public function handle(FindMultisiteUniqueUsersQuery|Query $query): array|null|object
    {
        $sql = "SELECT DISTINCT * FROM {$this->dfdb->basePrefix}user 
        JOIN {$this->dfdb->basePrefix}usermeta 
        ON {$this->dfdb->basePrefix}usermeta.user_id = {$this->dfdb->basePrefix}user.user_id 
        WHERE meta_key LIKE {$this->dfdb->prefix}%";

        $data = $this->dfdb->getResults(query: $sql, output: Database::ARRAY_A);

        $users = [];

        if (!is_null__($data)) {
            foreach ($data as $user) {
                $users[] = $this->populate($user);
            }
        }

        return $users;
    }
}

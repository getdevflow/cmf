<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use App\Domain\User\Query\Trait\PopulateUserQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function dd;
use function is_array;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

final class FindUserByTokenQueryHandler implements QueryHandler
{
    use PopulateUserQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function handle(FindUserByTokenQuery|Query $query): array|null|object
    {
        $sql = "SELECT u.*, m.meta_value AS role FROM {$this->dfdb->basePrefix}user u 
                JOIN {$this->dfdb->basePrefix}usermeta m 
                ON (m.user_id = u.user_id AND m.meta_key = '{$this->dfdb->prefix}role') 
                WHERE u.user_token = ?";

        $data = $this->dfdb->getRow(
            query: $this->dfdb->prepare(query: $sql, params: [$query->userToken->toNative()]),
            output: Database::ARRAY_A
        );

        if (is_null__(var: $data)) {
            return [];
        }

        $user = $this->populate(data: $data);

        if (is_array(value: $user)) {
            $user = convert_array_to_object(array: $user);
        }

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Infrastructure\Persistence\Database;
use Codefy\Framework\Http\BaseController;
use Exception;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\View\Renderer;

use function Qubus\Support\Helpers\is_false__;
use function sprintf;

final class ApiController extends BaseController
{
    public function __construct(
        SessionService $sessionService,
        Router $router,
        protected Database $dfdb,
        ?Renderer $view = null
    ) {
        parent::__construct($sessionService, $router, $view);
    }

    /**
     * @param ServerRequest $request
     * @param string $table
     * @return ResponseInterface
     * @throws Exception
     */
    public function all(ServerRequest $request, string $table): ResponseInterface
    {
        try {
            $this->dfdb
                    ->qb()
                    ->getConnection()
                    ->getPdo()
                    ->exec(sprintf('SELECT * FROM %s', $this->dfdb->prefix . $table));
        } catch (PDOException $e) {
            return JsonResponseFactory::create('Database table does not exist.', 404);
        }

        $query = $this->dfdb->qb()
                ->table($this->dfdb->prefix . $table);

        if (isset($request->getQueryParams()['by']) === true) {
            if (isset($request->getQueryParams()['order']) !== true) {
                $order = 'ASC';
            } else {
                $order = $request->getQueryParams()['order'];
            }
            $query->orderBy($request->getQueryParams()['by'], $order);
        }

        if (isset($request->getQueryParams()['limit']) === true) {
            $query->limit((int) $request->getQueryParams()['limit']);
            if (isset($request->getQueryParams()['offset']) === true) {
                $query->offset($request->getQueryParams()['offset']);
            }
        }

        $data = $query->find(function ($data) {
            $results = [];
            foreach ($data as $d) {
                $results[] = $d;
            }
            return $results;
        });

        if (is_false__($data)) {
            return JsonResponseFactory::create('No data.', 404);
        }

        return JsonResponseFactory::create($data);
    }

    /**
     * @param string $table
     * @param string $field
     * @param mixed $value
     * @return ResponseInterface
     * @throws Exception
     */
    public function column(string $table, string $field, mixed $value): ResponseInterface
    {
        try {
            $this->dfdb
                    ->qb()
                    ->getConnection()
                    ->getPdo()
                    ->exec(sprintf('SELECT * FROM %s', $this->dfdb->prefix . $table));
        } catch (PDOException $e) {
            return JsonResponseFactory::create('Database table does not exist.', 404);
        }

        $query = $this->dfdb->qb()
                ->table($this->dfdb->prefix . $table)
                ->where($field, $value)
                ->find(function ($data) {
                    $results = [];
                    foreach ($data as $d) {
                        $results[] = $d;
                    }
                    return $results;
                });

        if (is_false__($query)) {
            return JsonResponseFactory::create('No data.', 404);
        }

        return JsonResponseFactory::create($query);
    }
}

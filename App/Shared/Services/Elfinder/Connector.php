<?php

declare(strict_types=1);

namespace App\Shared\Services\Elfinder;

use App\Shared\Http\StreamedResponse;
use elFinderConnector;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;

use function fpassthru;
use function rewind;
use function stream_get_meta_data;

class Connector extends elFinderConnector
{
    protected ?Response $response = null;

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Output json
     *
     * @param  array $data data to output
     * @return void
     **/
    protected function output(array $data): void
    {

        $header = $data['header'] ?? '';
        unset($data['header']);

        $headers = array();
        if ($header) {
            foreach ((array) $header as $headerString) {
                if (strpos($headerString, ':') !== false) {
                    [$key, $value] = explode(':', $headerString, 2);
                    $headers[$key] = $value;
                }
            }
        }

        if (isset($data['pointer'])) {
            $this->response = new StreamedResponse(function () use ($data) {
                if (stream_get_meta_data($data['pointer'])['seekable']) {
                    rewind($data['pointer']);
                }
                fpassthru($data['pointer']);
                if (!empty($data['volume'])) {
                    $data['volume']->close($data['pointer'], $data['info']['hash']);
                }
            }, 200, $headers);
        } else {
            if (!empty($data['raw']) && !empty($data['error'])) {
                $this->response = new JsonResponse($data['error'], 500);
            } else {
                $this->response = new JsonResponse($data, 200, $headers);
            }
        }
    }
}

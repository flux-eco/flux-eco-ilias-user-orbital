<?php

namespace Flux\IliasUserImportApi\Adapters\Api;

use FluxIliasRestApiClient\Adapter\Api\IliasRestApiClient;
use Swoole\Http;
use Flux\IliasUserImportApi\Adapters\Medi;
use Flux\IliasUserImportApi\Adapters\Ilias;
use Flux\IliasUserImportApi\Core\Domain;
use Flux\IliasUserImportApi\Core\Ports;

class IliasUserImportApi
{

    private function __construct(
        private Ports\Service $actor
    )
    {

    }

    public static function new(): self
    {
        $iliasRestApiClient = IliasRestApiClient::new();

        return new self(
            Ports\Service::new(
                Ports\Outbounds::new(
                    Medi\MediExcelUserQueryRepository::new(IliasUserImportConfig::new()->excelImportDirectoryPath),
                    Ilias\IliasUserQueryRepositoryAdapter::new(
                        $iliasRestApiClient
                    ),
                    Medi\MediUserEventHandler::new($iliasRestApiClient)
                )
            )
        );
    }

    /**
     * @throws \Exception
     */
    final public function handleHttpRequest(Http\Request $request, Http\Response $response): void
    {
        $requestUri = $request->server['request_uri'];

        match (true) {
            str_contains($requestUri, Domain\Types\TaskType::IMPORT_USERS->value) => $this->actor->importUsers(
                $this->getAttribute(Domain\Types\AttributeType::CONTEXT_ID->value, $requestUri),
                $this->publish($response)
            ), //todo secret
            default => $this->publish($response)($requestUri)
        };
    }


    private function publish(Http\Response $response)
    {
        return function (object|string $responseObject) use ($response) {

            if (is_object($responseObject) && property_exists($responseObject, 'cookies') && count($responseObject->cookies) > 0) {
                foreach ($responseObject->cookies as $name => $value) {
                    $response->setCookie($name, $value, time() + 3600);
                }
            }

            $response->header('Content-Type', 'application/json');
            $response->header('Cache-Control', 'no-cache');

            match (true) {
                is_string($responseObject) => $response->end($responseObject),
                default => $response->end(json_encode($responseObject))
            };
        };
    }

    private function getAttribute(string $attributeName, string $requestUri): string
    {
        $explodedParam = explode($attributeName . "/", $requestUri, 2);
        if (count($explodedParam) === 2) {
            $explodedParts = explode("/", $explodedParam[1], 2);
            if (count($explodedParts) == 2) {
                return $explodedParts[0];
            }
            return $explodedParam[1];
        }
    }
}
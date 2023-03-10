<?php

namespace FluxEco\IliasUserOrbital\Adapters\Api;

use FluxIliasRestApiClient\Adapter\Api\IliasRestApiClient;
use Swoole\Http;
use FluxEco\IliasUserOrbital\Adapters;
use FluxEco\IliasUserOrbital\Core\Ports;

class HttpApi
{

    private function __construct(
        private Ports\Service $service
    ) {

    }

    public static function new() : self
    {
        $iliasRestApiClient = IliasRestApiClient::new();
        $config = Adapters\Config\Config::new();

        return new self(
            Ports\Service::new(
                Ports\Outbounds::new(
                    Adapters\Repositories\IliasUser\IliasUserRepository::new($iliasRestApiClient),
                    Adapters\Dispatchers\HttpMessageDispatcher::new($config),
                    Adapters\Repositories\IliasCourse\IliasCourseRepository::new($iliasRestApiClient)
                )
            )
        );
    }

    /**
     * @throws \Exception
     */
    final public function handleHttpRequest(Http\Request $request, Http\Response $response) : void
    {
        $requestUri = $request->server['request_uri'];

        match (true) {
            str_ends_with($requestUri,
                Ports\Messages\IncomingMessageName::CREATE_OR_UPDATE_USER->value) => $this->service->createOrUpdateUser(
                Ports\Messages\CreateOrUpdateUser::fromJson($request->rawContent()),
                $this->publish($response)
            ),
            str_ends_with($requestUri,
                Ports\Messages\IncomingMessageName::SUBSCRIBE_USER_TO_COURSES->value) => $this->service->subscribeUserToCourses(
                Ports\Messages\SubscribeUserToCourses::fromJson($request->rawContent()),
                $this->publish($response)
            ),
            str_ends_with($requestUri,
                Ports\Messages\IncomingMessageName::UNSUBSCRIBE_USER_FROM_COURSES->value) => $this->service->unsubscribeUserFromCourses(
                Ports\Messages\UnsubscribeUserFromCourses::fromJson($request->rawContent()),
                $this->publish($response)
            ),
            str_ends_with($requestUri,
                Ports\Messages\IncomingMessageName::SUBSCRIBE_USER_TO_COURSE->value) => $this->service->subscribeUserToCourse(
                Ports\Messages\SubscribeUserToCourse::fromJson($request->rawContent()),
                $this->publish($response)
            ),
            str_ends_with($requestUri,
                Ports\Messages\IncomingMessageName::SUBSCRIBE_USER_TO_COURSE_TREE->value) => $this->service->subscribeUserToCourseTree(
                Ports\Messages\SubscribeUserToCourseTree::fromJson($request->rawContent()),
                $this->publish($response)
            ),
            str_ends_with($requestUri,
                Ports\Messages\IncomingMessageName::SUBSCRIBE_USER_TO_ROLES->value) => $this->service->subscribeUserToRoles(
                Ports\Messages\SubscribeUserToRoles::fromJson($request->rawContent()),
                $this->publish($response)
            ),
            default => $this->publish($response)("address not valid: " . $requestUri)
        };
    }

    private function publish(Http\Response $response)
    {
        return function (object|string $responseObject) use ($response) {

            if (is_object($responseObject) && property_exists($responseObject,
                    'cookies') && count($responseObject->cookies) > 0) {
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

    private function getAttribute(string $attributeName, string $requestUri) : string
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
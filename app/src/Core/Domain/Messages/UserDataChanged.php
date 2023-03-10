<?php

namespace FluxEco\IliasUserOrbital\Core\Domain\Messages;

use FluxEco\IliasUserOrbital\Core\Domain\ValueObjects;

class UserDataChanged implements OutgoingMessage
{

    private function __construct(
        public ValueObjects\UserId $userId,
        public ValueObjects\UserData $userData,
    )
    {

    }

    public static function new(
        ValueObjects\UserId $userId,
        ValueObjects\UserData $userData
    ): self
    {
        return new self($userId, $userData);
    }

    public function getName(): MessageName
    {
        return MessageName::USER_DATA_CHANGED;
    }

    public function getAddress() : string
    {
        return MessageName::USER_DATA_CHANGED->value;
    }
}
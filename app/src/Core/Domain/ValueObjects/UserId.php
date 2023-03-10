<?php

namespace FluxEco\IliasUserOrbital\Core\Domain\ValueObjects;

class UserId
{
    private function __construct(
        public readonly string $id,
        public readonly IdType $idType
    ) {

    }

    public static function new(
        string $id,
        IdType $idType
    ) : self {
        return new self(
            $id,
            $idType
        );
    }

    public function isEqual(UserId $obj) : bool
    {
        return (serialize($this) === serialize($obj));
    }
}
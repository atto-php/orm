<?php

declare(strict_types=1);

namespace Atto\Orm\ValueObjects;

final class Field
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {
    }

    public function getParameterType(): string
    {
        return match ($this->type) {
            'int' => '\Doctrine\DBAL\ParameterType::INTEGER',
            'bool' => '\Doctrine\DBAL\ParameterType::BOOLEAN',
            default => '\Doctrine\DBAL\ParameterType::STRING'
        };
    }
}
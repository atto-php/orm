<?php

declare(strict_types=1);

namespace Atto\Orm\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity
{
    public function __construct(
        public readonly ?string $tableName = null
    ) {

    }
}
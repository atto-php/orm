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
}
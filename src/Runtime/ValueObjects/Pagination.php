<?php

namespace Atto\Orm\Runtime\ValueObjects;

class Pagination
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
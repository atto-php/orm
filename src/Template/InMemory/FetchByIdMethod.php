<?php

declare(strict_types=1);

namespace Atto\Orm\Template\InMemory;

final class FetchByIdMethod
{
    private const METHOD_CODE = <<<'EOF'
        public function fetchById(%1$s $id): ?%2$s 
        {
            return $this->entities[$id] ?? null;
        }
        EOF;

    public function __construct(
        private readonly string $idType,
        private readonly string $classname
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            self::METHOD_CODE,
            $this->idType,
            $this->classname,
        );
    }
}
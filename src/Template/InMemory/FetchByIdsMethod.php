<?php

declare(strict_types=1);

namespace Atto\Orm\Template\InMemory;

final class FetchByIdsMethod
{
    private const METHOD_CODE = <<<'EOF'
        /** @return %2$s[] */
        public function fetchByIds(%1$s ...$ids): array
        {
            $ids = array_combine($ids, $ids);
            return array_intersect_key($this->entities, $ids);
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
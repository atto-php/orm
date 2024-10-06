<?php

declare(strict_types=1);

namespace Atto\Orm\Template\InMemory;

final class SaveMethod
{
    private const METHOD_CODE = <<<'EOF'
        public function save(%1$s $entity): void
        {
            $data = $this->hydrator->extract($entity);
            if (!isset($this->entities[$data['%2$s']])) {
                $this->entities[$data['%2$s']] = $entity;
                $this->idMap[spl_object_id($entity)] = $data['%2$s'];
            }
        }
        EOF;

    public function __construct(
        private readonly string $idField,
        private readonly string $classname
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            self::METHOD_CODE,
            $this->classname,
            $this->idField
        );
    }
}
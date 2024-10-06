<?php

declare(strict_types=1);

namespace Atto\Orm\Template\Sqlite;

final class RemoveMethod
{
    private const METHOD_CODE = <<<'EOF'
        public function remove(%1$s $entity): void
        {
            if (!isset($this->idMap[spl_object_id($entity)])) {
                return;
            } 
            
            $id = $this->idMap[spl_object_id($entity)];
            
            $this->connection->delete(self::TABLE_NAME, ['%2$s' => $id]);
            unset($this->entities[$id]);
            unset($this->idMap[spl_object_id($entity)]);
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
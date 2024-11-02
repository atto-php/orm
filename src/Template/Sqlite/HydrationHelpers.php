<?php

declare(strict_types=1);

namespace Atto\Orm\Template\Sqlite;

final class HydrationHelpers
{
    private const METHOD_CODE = <<<'EOF'
        /** @return %1$s[] */
        protected function hydrateArray(QueryBuilder $query): array 
        {
            $data = $query->executeQuery();

            $entities = [];
            
            while ($row = $data->fetchAssociative()) {
                $entity = $this->hydrator->create($row);
                $this->entities[$row['%2$s']] = $entity;
                $this->idMap[spl_object_id($entity)] = $row['%2$s'];
                $entities[] = $entity;
            }
            
            return $entities;
        }
        
        /** @param array<string, mixed> $data */
        protected function hydrate(array $data): %1$s 
        {
            $entity = $this->hydrator->create($data);
            $this->entities[$data['%2$s']] = $entity;
            $this->idMap[spl_object_id($entity)] = $data['%2$s'];
            
            return $entity;
        }
        EOF;

    public function __construct(
        private readonly string $classname,
        private readonly string $idField,
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
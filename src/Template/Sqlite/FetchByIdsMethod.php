<?php

declare(strict_types=1);

namespace Atto\Orm\Template\Sqlite;

final class FetchByIdsMethod
{
    private const METHOD_CODE = <<<'EOF'
        /** @return %2$s[] */
        public function fetchByIds(%1$s ...$ids): array
        {
            $fetchRequired = array_diff($ids, array_keys($this->entities));
            $fetchRequired = array_combine($fetchRequired, $fetchRequired);
            $ids = array_combine($ids, $ids);
            
            if (!empty($fetchRequired)) {
                $qb = $this->connection->createQueryBuilder();
                $data = $qb->select('*')
                    ->from(static::TABLE_NAME)
                    ->where($qb->expr()->in('%4$s', ':ids'))
                    ->setParameter('ids', $ids, ArrayParameterType::%3$s)
                    ->executeQuery()
                ;

                while ($row = $data->fetchAssociative()) {
                    unset($fetchRequired[$row['%4$s']]);
                    $entity = $this->hydrator->create($row);
                    $this->entities[$row['%4$s']] = $entity;
                    $this->idMap[spl_object_id($entity)] = $row['%4$s'];
                }
                
                foreach($fetchRequired as $id => $field) {
                    $this->entities[$id] = null;
                }
            }
            
            return array_intersect_key($this->entities, $ids);
        }
        EOF;

    public function __construct(
        private readonly string $idField,
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
            $this->idType === 'int' ? 'INTEGER' : 'STRING',
            $this->idField
        );
    }
}
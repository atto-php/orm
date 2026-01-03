<?php

declare(strict_types=1);

namespace Atto\Orm\Template\Sqlite;

final class FetchByIdMethod
{
    private const METHOD_CODE = <<<'EOF'
        public function fetchById(%1$s $id): ?%2$s 
        {
            if (!isset($this->entities[$id])) {
                $qb = $this->connection->createQueryBuilder();
                $data = $qb->select('*')
                    ->from('`' . static::TABLE_NAME . '`')
                    ->where($qb->expr()->eq('`%3$s`', ':id'))
                    ->setParameter('id', $id)
                    ->executeQuery()
                    ->fetchAssociative()
                ;

                if (empty($data)) {
                    $this->entities[$id] = null;
                } else {
                    $entity = $this->hydrator->create($data);
                    $this->entities[$id] = $entity;
                    $this->idMap[spl_object_id($entity)] = $id;
                } 
            }
            return $this->entities[$id];
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
            $this->idField
        );
    }
}
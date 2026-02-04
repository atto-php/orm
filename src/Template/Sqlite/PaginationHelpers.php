<?php

namespace Atto\Orm\Template\Sqlite;

class PaginationHelpers
{
    public function __toString(): string
    {
        return <<<'EOF'
        protected function applyPagination(Pagination $pagination, QueryBuilder $qb): void 
        {
            $qb->setFirstResult($pagination->getOffset());
            $qb->setMaxResults($pagination->perPage);
        }
        EOF;
    }
}
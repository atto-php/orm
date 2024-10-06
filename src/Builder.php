<?php

declare(strict_types=1);

namespace Atto\Orm;

use Atto\Orm\Attribute\Entity;
use Atto\Orm\Attribute\Id;
use Atto\Orm\Template\Sqlite\FetchByIdMethod;
use Atto\Orm\Template\RepositoryClass;
use Atto\Orm\Template\Sqlite\FetchByIdsMethod;
use Atto\Orm\Template\Sqlite\RemoveMethod;
use Atto\Orm\Template\Sqlite\SaveMethod;

final class Builder
{
    public function generateCodeFor(
        string $class,
        string $repositoryType,
        string $repositoryNamespace = 'Generated',
        string $commonNamespace = ''
    ): RepositoryClass
    {
        $class = '\\' . ltrim($class, '\\');
        assert(class_exists($class));

        $refl = new \ReflectionClass($class);
        $entityAttribute = current($refl->getAttributes(Entity::class)) ?: null;
        $tableName = $entityAttribute?->newInstance()->tableName ?? $refl->getShortName();
        list($idType, $idField) = $this->findIdDetails($refl);

        $repositoryClassName = ClassName::fromFullyQualifiedName($class . 'Repository')
            ->removeNamespacePrefix($commonNamespace)
            ->addNamespacePrefix($repositoryNamespace);

        return match($repositoryType) {
            'in-memory' => RepositoryClass::inMemory(
                $repositoryClassName,
                $class,
                $idField,
                $idType,
                $tableName
            ),
            default => RepositoryClass::sqlite(
                $repositoryClassName,
                $class,
                $idField,
                $idType,
                $tableName
            ),
        };
    }

    private function findIdDetails(\ReflectionClass $refl): array
    {
        $idType = null;
        $idField = null;

        foreach ($refl->getProperties() as $property) {
            $isId = current($property->getAttributes(Id::class)) !== false;
            if ($isId && $idType !== null) {
                throw new \RuntimeException('Unable to support multiple id fields currently');
            }

            if ($isId) {
                $type = $property->getType();
                if (!($type instanceof \ReflectionNamedType)) {
                    throw new \RuntimeException('cannot handle multiple type hints');
                }

                if ($type === null) {
                    throw new \RuntimeException('id field must have a type hint');
                }

                if ($type->getName() !== 'string' && $type->getName() !== 'int') {
                    throw new \RuntimeException('Id field must be a string or an int');
                }

                $idType = $type->getName();
                $idField = $property->getName();
            }
        }

        if ($idType === null) {
            throw new \RuntimeException('entity must have exactly one id field');
        }

        return [$idType, $idField];
    }
}
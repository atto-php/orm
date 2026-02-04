<?php

declare(strict_types=1);

namespace Atto\Orm;

use Atto\Orm\Attribute\Entity;
use Atto\Orm\Attribute\Id;
use Atto\Orm\Template\RepositoryClass;
use Atto\Orm\ValueObjects\ClassName;
use Atto\Orm\ValueObjects\Field;

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
        $idField = null;

        foreach ($refl->getProperties() as $property) {
            $idInfo = $this->checkForIdField($property);
            if ($idInfo !== null) {
                if ($idField !== null) {
                    throw new \RuntimeException('Unable to support multiple id fields currently');
                }
                $idField = $idInfo;
            }
            $fields[] = new Field($property->getName(), $property->getType()?->getName() ?? '');
        }

        if ($idField === null) {
            throw new \RuntimeException('entity must have exactly one id field');
        }

        $repositoryClassName = ClassName::fromFullyQualifiedName($class . 'Repository')
            ->removeNamespacePrefix($commonNamespace)
            ->addNamespacePrefix($repositoryNamespace);

        $hydratorName = str_replace('Repository', 'Hydrator', $repositoryClassName->asString());
        $repositoryClassName = $repositoryClassName->addNamespacePostfix(
            match ($repositoryType) {
                'in-memory' => 'InMemory',
                'sqlite' => 'SQLite',
                default => 'Custom'
            }
        );

        return match($repositoryType) {
            'in-memory' => RepositoryClass::inMemory(
                $repositoryClassName,
                $class,
                $idField,
                $hydratorName,
                $tableName
            ),
            default => RepositoryClass::sqlite(
                $repositoryClassName,
                $class,
                $idField,
                $hydratorName,
                $tableName,
                ...$fields
            ),
        };
    }

    private function checkForIdField(\ReflectionProperty $property): ?Field
    {
        $isId = current($property->getAttributes(Id::class)) !== false;
        if (!$isId) {
            return null;
        }

        $type = $property->getType();
        if (!($type instanceof \ReflectionNamedType)) {
            throw new \RuntimeException('cannot handle multiple type hints');
        }

        if ($type === null) {
            throw new \RuntimeException('id field must have a type hint');
        }

        $typeName = $type->getName();
        if ($typeName !== 'string' && $typeName !== 'int') {
            throw new \RuntimeException('Id field must be a string or an int');
        }

        return new Field($property->getName(), $typeName);
    }
}
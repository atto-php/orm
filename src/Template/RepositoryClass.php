<?php

declare(strict_types=1);

namespace Atto\Orm\Template;

use Atto\Orm\ValueObjects\ClassName;
use Atto\Orm\Template\Sqlite\FetchByIdMethod;
use Atto\Orm\Template\Sqlite\FetchByIdsMethod;
use Atto\Orm\Template\Sqlite\RemoveMethod;
use Atto\Orm\Template\Sqlite\SaveMethod;
use Atto\Orm\ValueObjects\Field;

final class RepositoryClass
{
    /** @var array<string|\Stringable>  */
    private array $methods;
    /** @var string[] */
    private array $constructorParams = [];
    private array $imports = [];
    private const CLASS_CODE = <<< 'EOF'
        namespace %1$s;
        
        %7$s
        
        abstract class %2$s 
        {
            protected const TABLE_NAME = '%6$s';
            
            /** @var array<%3$s, %4$s>  */
            protected array $entities = [];
            /** @var array<string, %3$s>  */
            protected array $idMap = [];
            protected %5$s $hydrator;
            
            public function __construct(%8$s)
            {
                $this->hydrator = new %5$s();
            }
            
            %9$s
        }
        EOF;

    public function __construct(
        private readonly ClassName $repositoryClassName,
        private readonly string $targetClassName,
        private readonly string $idType,
        private readonly string $hydratorName,
        private readonly string $tableName,
    ){
    }

    public function getRepositoryClassName(): ClassName
    {
        return $this->repositoryClassName;
    }

    public static function sqlite(
        ClassName $repositoryClassName,
        string $targetClassName,
        Field $field,
        string $hydratorName,
        string $tableName,
        Field ...$fields
    ): self
    {
        $instance = new self($repositoryClassName, $targetClassName, $field->type, $hydratorName, $tableName);

        $instance->constructorParams = ['protected Connection $connection'];
        $instance->imports = [
            'use Doctrine\DBAL\Connection;',
            'use Doctrine\DBAL\ArrayParameterType;',
            'use Doctrine\DBAL\Query\QueryBuilder;'
        ];

        $instance->addMethod(new Sqlite\FetchByIdMethod($field->name, $field->type, $targetClassName));
        $instance->addMethod(new Sqlite\FetchByIdsMethod($field->name, $field->type, $targetClassName));
        $instance->addMethod(new Sqlite\SaveMethod($field->name, $targetClassName, ...$fields));
        $instance->addMethod(new Sqlite\RemoveMethod($field->name, $targetClassName));
        $instance->addMethod(new Sqlite\HydrationHelpers($targetClassName, $field->name));

        return $instance;
    }

    public static function inMemory(
        ClassName $repositoryClassName,
        string $targetClassName,
        Field $idField,
        string $hydratorName,
        string $tableName,
    ): self
    {
        $instance = new self($repositoryClassName, $targetClassName, $idField->type, $hydratorName, $tableName);

        $instance->constructorParams = [];
        $instance->imports = [];

        $instance->addMethod(new InMemory\FetchByIdMethod($idField->type, $targetClassName));
        $instance->addMethod(new InMemory\FetchByIdsMethod($idField->type, $targetClassName));
        $instance->addMethod(new InMemory\SaveMethod($idField->name, $targetClassName));
        $instance->addMethod(new InMemory\RemoveMethod($idField->name, $targetClassName));

        return $instance;
    }

    public function addMethod(string|\Stringable $method): void
    {
        $this->methods[] = $method;
    }

    public function __toString(): string
    {
        return sprintf(
            self::CLASS_CODE,
            $this->repositoryClassName->namespace,
            $this->repositoryClassName->name,
            $this->idType, //id type
            $this->targetClassName, //entity name
            $this->hydratorName, //hydrator name
            $this->tableName, //table name
            implode("\n", $this->imports),
            implode(",\n", $this->constructorParams), //constructor params
            implode("\n\n", $this->methods) //methods
        );
    }
}
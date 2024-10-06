<?php

declare(strict_types=1);

namespace Atto\Orm\Template;

use Atto\Orm\ClassName;
use Atto\Orm\Template\Sqlite\FetchByIdMethod;
use Atto\Orm\Template\Sqlite\FetchByIdsMethod;
use Atto\Orm\Template\Sqlite\RemoveMethod;
use Atto\Orm\Template\Sqlite\SaveMethod;

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
        string $idField,
        string $idType,
        string $hydratorName,
        string $tableName,
    ): self
    {
        $instance = new self($repositoryClassName, $targetClassName, $idType, $hydratorName, $tableName);

        $instance->constructorParams = ['private Connection $connection'];
        $instance->imports = ['use Doctrine\DBAL\Connection;', 'use Doctrine\DBAL\ArrayParameterType;'];

        $instance->addMethod(new Sqlite\FetchByIdMethod($idField, $idType, $targetClassName));
        $instance->addMethod(new Sqlite\FetchByIdsMethod($idField, $idType, $targetClassName));
        $instance->addMethod(new Sqlite\SaveMethod($idField, $targetClassName));
        $instance->addMethod(new Sqlite\RemoveMethod($idField, $targetClassName));

        return $instance;
    }

    public static function inMemory(
        ClassName $repositoryClassName,
        string $targetClassName,
        string $idField,
        string $idType,
        string $hydratorName,
        string $tableName,
    ): self
    {
        $instance = new self($repositoryClassName, $targetClassName, $idType, $hydratorName, $tableName);

        $instance->constructorParams = [];
        $instance->imports = [];

        $instance->addMethod(new InMemory\FetchByIdMethod($idType, $targetClassName));
        $instance->addMethod(new InMemory\FetchByIdsMethod($idType, $targetClassName));
        $instance->addMethod(new InMemory\SaveMethod($idField, $targetClassName));
        $instance->addMethod(new InMemory\RemoveMethod($idField, $targetClassName));

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
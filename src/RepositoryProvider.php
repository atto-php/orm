<?php

declare(strict_types=1);

namespace Atto\Orm;

use Atto\CodegenTools\ClassDefinition\SimplePHPClassDefinition;
use Atto\Orm\Attribute\Entity;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

final class RepositoryProvider
{
    private Builder $builder;
    public function __construct(
        private string $directory,
        private string $baseNamespace,
        private string $hydratorNamespace
    ) {
        $this->builder = new Builder();
    }

    public function provideFile(): \Generator
    {
        $classList = $this->getEntityClasses();

        while ($class = array_pop($classList)) {
            $repositoryCode = $this->builder->generateCodeFor($class, $this->hydratorNamespace, $this->baseNamespace);

            yield new SimplePHPClassDefinition(
                $repositoryCode->getRepositoryClassName()->namespace,
                $repositoryCode->getRepositoryClassName()->name,
                "<?php\n\n" . $repositoryCode
            );
        };
    }

    private function getEntityClasses(): array
    {
        $astLocator = (new BetterReflection())->astLocator();
        $directoriesSourceLocator = new DirectoriesSourceLocator([$this->directory], $astLocator);
        $reflector = new DefaultReflector($directoriesSourceLocator);
        $classes = [];

        foreach($reflector->reflectAllClasses() as $class) {
            if (count($class->getAttributesByName(Entity::class))) {
                $classes[] = $class->getName();
                require_once $class->getFileName();
            }
        }

        return $classes;
    }
}

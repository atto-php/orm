<?php

namespace Atto\Orm\Template;

use Atto\Orm\ValueObjects\ClassName;

class HydratorInstance implements \Stringable
{
    private array $subHydrators = [];
    public function __construct(private string $className)
    {
        if (class_exists($className)) {
            $reflection = new \ReflectionClass($className);
            $parameters = $reflection->getConstructor()->getParameters();
            foreach ($parameters as $parameter) {
                $this->subHydrators[] = new HydratorInstance(ClassName::fromFullyQualifiedName($parameter->getType())->asString());
            }
        }
    }

    public function __toString(): string
    {
        return sprintf('new %s(%s)', $this->className, implode(', ', $this->subHydrators));
    }
}
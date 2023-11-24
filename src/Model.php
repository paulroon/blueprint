<?php

namespace HappyCode\Blueprint;

use HappyCode\Blueprint\Error\BuildError;
use HappyCode\Blueprint\Error\ValidationError;

class Model
{
    private bool $isRootCollection = false;
    private array $definition = [];
    private ?string $useNamespace = null;

    private function __construct(
        private readonly ?string $modelName,
        array $definition
    ){
        foreach ($definition as $field => $itemValue) {
            if (is_string($itemValue)) {
                $definition[$field] = $this->mapStringNameToTypeDefinition($itemValue);
            }
        }
        $this->definition = $definition;
    }

    public function getModelName(): string
    {
        return $this->modelName ?? 'Root';
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public static function CollectionOf(Model $model): static
    {
        return (new static($model->getModelName(), $model->getDefinition()))
            ->setHelperNamespace($model->isUseFS() ? $model->getUseNamespace() : null)
            ->asRootCollection();
    }

    public static function Define(string $modelName, array $definition): static
    {
        return new static($modelName, $definition);
    }

    public static function __callStatic(string $modelName, array $args)
    {
        return static::Define($modelName, $args[0]);
    }

    private function asRootCollection(): static
    {
        $this->isRootCollection = true;
        return $this;
    }

    public function isRootCollection(): bool
    {
        return $this->isRootCollection;
    }

    /**
     * @throws ValidationError
     * @throws BuildError
     */
    public function adapt(string $fromData): ModelData|RootCollection
    {
        $this->syncDeepSettings();

        $data = json_decode($fromData, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationError(sprintf("Json could not be decoded: [%s]", json_last_error_msg()));
        }

        if (!$this->isRootCollection()) {
            // Filter out data that is not defined
            $definitionKeys = array_keys($this->definition);

            $filteredData = array_filter($data, function($dataKey) use($definitionKeys) {
                return in_array($dataKey, $definitionKeys);
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $filteredData = $data;
        }

        // Validate here as this is the first instance of hard data values
        Validator::Init($this, $filteredData)->validate();

        return Adapter::Init($this, $filteredData)->adapt();
    }

    private function syncDeepSettings(): void
    {
        /** @var Type $type */
        foreach ($this->definition as $field => $type) {
            if ($type->getTypeModel() instanceof Model) {
                $type->getTypeModel()->setHelperNamespace($this->useNamespace);
            }
        }
    }

    // Short syntax for type definitions
    private function mapStringNameToTypeDefinition(string $typeName): Type
    {
        $lcTypeName = strtolower($typeName);

        // ArrayOf example 'string[]'
        if (str_ends_with($lcTypeName, '[]')) {
            return Type::ArrayOf(
                $this->mapStringNameToTypeDefinition(substr($lcTypeName, 0, -2))
            );
        }

        // Enums example 'Dog|Cat'
        if (str_contains($lcTypeName, '|')) {
            return Type::Enum(values: explode('|', $typeName));
        }

        // Primitives
        return match ($lcTypeName) {
            'string', 'text' => Type::String(),
            'int', 'integer', 'number' => Type::Int(),
            'float', 'double', 'decimal' => Type::Float(),
            'bool', 'boolean' => Type::Boolean(),
            'date' => Type::DateTime(inputFormat: 'd/m/Y'),
            'datetime' => Type::DateTime(),
            default => Type::Enum(values: [$typeName])
        };
    }

    public function isUseFS(): bool
    {
        return !!$this->useNamespace;

    }

    public function setHelperNamespace(?string $namespace): Model
    {
        $this->useNamespace = $namespace;
        return $this;
    }

    public function getUseNamespace(): string
    {
        return $this->useNamespace;
    }

}
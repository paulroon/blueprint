<?php

namespace HappyCode\Blueprint;

class Type
{
    private ?Model $typeModel = null;

    private ?\Closure $transformerFn = null;

    private function __construct(
        private readonly string $label,
        private readonly bool $isPrimitive,
        private readonly bool $isArray,
        private readonly bool $isNullable,
        private readonly bool $isRequired,
        private readonly bool $isVirtual = false,
        private readonly bool $isHidden = false,
        private readonly array $enumValues = [],
        private readonly string $inputFormat = '',
        private readonly string $outputFormat = '',
    )
    {}

    public static function String(bool $isNullable = false, bool $isRequired = true, bool $isHidden = false): static
    {
        return new static('string', isPrimitive: true, isArray: false, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden);
    }

    public static function Int(bool $isNullable = false, bool $isRequired = true, bool $isHidden = false): static
    {
        return new static('int', isPrimitive: true, isArray: false, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden);
    }

    public static function Float(bool $isNullable = false, bool $isRequired = true, bool $isHidden = false): static
    {
        return new static('float', isPrimitive: true, isArray: false, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden);
    }

    public static function DateTime(bool $isNullable = false, bool $isRequired = true, bool $isHidden = false, string $inputFormat = 'd/m/Y H:i:s', ?string $outputFormat = null): static
    {
        if (!$outputFormat) {
            $outputFormat = $inputFormat;
        }
        return new static('datetime', isPrimitive: false, isArray: false, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden, inputFormat: $inputFormat, outputFormat: $outputFormat);
    }

    public static function Boolean(bool $isRequired = true, bool $isHidden = false): static
    {
        return new static('boolean', isPrimitive: true, isArray: false, isNullable: false, isRequired: $isRequired, isHidden: $isHidden);
    }

    public static function Enum(array $values, bool $isRequired = true, bool $isHidden = false): static
    {
        return new static('enum',
            isPrimitive: true, isArray: false, isNullable: false, isRequired: $isRequired, isHidden: $isHidden, enumValues: $values
        );
    }

    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    public static function Model(Model $model, bool $isNullable = false, bool $isRequired = true, bool $isHidden = false): static
    {
        return (new static($model->getModelName(),
            isPrimitive: false, isArray: false, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden
        ))->registerModel($model);
    }

    /**
     * A collection is shorthand for an array of objects
     */
    public static function Collection(Model $model, bool $isNullable = false, bool $isRequired = true, bool $isHidden = false): static
    {
        return (new static($model->getModelName(),
            isPrimitive: false, isArray: true, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden
        ))->registerModel($model);
    }

    public static function ArrayOf(Type $type, bool $isNullable = false, bool $isRequired = true, bool $isHidden = false): static
    {
        return (new static($type->getLabel(),
            isPrimitive: false, isArray: true, isNullable: $isNullable, isRequired: $isRequired, isHidden: $isHidden
        ));
    }

    public static function Virtual(callable $fn): static
    {
        return (new static('string',
            isPrimitive: true, isArray: false, isNullable: true, isRequired: false, isVirtual: true, isHidden: false
        ))->registerTransformer($fn);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isPrimitive(): bool
    {
        return $this->isPrimitive;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isVirtual(): bool
    {
        return $this->isVirtual;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    private function registerModel(Model $model): static
    {
        $this->typeModel = $model;
        return $this;
    }

    private function registerTransformer(callable $fn): static
    {
        $this->transformerFn = $fn;
        return $this;
    }

    public function getTransformerFn(): ?\Closure
    {
        return $this->transformerFn;
    }

    public function getTypeModel(): ?Model
    {
        return $this->typeModel;
    }

    public function getInputFormat(): string
    {
        return $this->inputFormat;
    }

    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    public function getReferencableTypeName(): string
    {
        $typeName = match (true) {
            in_array($this->getLabel(), ["enum", "datetime"]) => '\string',
            !!$this->getTypeModel() && !$this->isArray() => sprintf("%sModel", $this->getTypeModel()->getModelName()),
            !!$this->getTypeModel() && $this->isArray() => sprintf("\array|%sModel[]", $this->getTypeModel()->getModelName()),
            $this->isArray() => '\array',
            $this->isPrimitive() => '\\' . $this->getLabel(),
            default => '\mixed'
        };

        if ($this->isNullable()) {
            $typeName = "null|" . $typeName;
        }

        return $typeName;
    }

}

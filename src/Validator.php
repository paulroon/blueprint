<?php

namespace HappyCode\Blueprint;

use HappyCode\Blueprint\Error\ValidationError;

class Validator
{

    public function __construct(
        private readonly Model $model,
        private readonly array $data
    ){}

    public static function Init(Model $model, array $data): static
    {
        return new static($model, $data);
    }

    /**
     * @throws ValidationError
     */
    public function validate(): void
    {
        if ($this->model->isRootCollection()) {
            foreach ($this->data as $unitItem) {
                $this->validateDefinition($this->model->getDefinition(), $unitItem);
            }
        } else {
            $this->validateDefinition($this->model->getDefinition(), $this->data);
        }
    }

    /**
     * @throws ValidationError
     */
    private function validateDefinition(array $definition, array $data): void
    {
        /**
         * @var  string $field
         * @var  Type $type
         */
        foreach ($definition as $field => $type) {
            $locator = sprintf("%s::%s", $this->model->getModelName(), $field);
            $exists = in_array($field, array_keys($data));

            if ($type->isVirtual()) {
                continue;
            }

            if ($type->isRequired() && !$exists) {
                throw new ValidationError(sprintf("%s is required", $locator));
            }

            if (!$type->isNullable() && $exists && $data[$field] === null) {
                throw new ValidationError(sprintf("%s cannot be null", $locator));
            }

            if (null === $data[$field]) {
                continue; // we don't need to validate nullable fields being null
            }

            if ($type->isPrimitive()) {
                $this->validatePrimitive($type, $data, $field);
            }

            // Datetime
            if ($type->getLabel() === "datetime") {
                $asObj = \DateTime::createFromFormat($type->getInputFormat(), $data[$field]);
                if (
                    !is_string($data[$field]) ||
                    !$asObj ||
                    $asObj->format($type->getInputFormat()) !== $data[$field]
                ) {
                    throw new ValidationError(sprintf("%s must be a datetime string of format %s", $locator, $type->getInputFormat()));
                }
            }




            // Array Data
            if (is_array($data[$field])) {

                $customType = $type->getTypeModel();

                if ($type->isArray()) {

                    if ($customType === null) {
                        // primitive array - eg: [1,2,3,4] or ["s", "e", "x", "y"]
                        foreach ($data[$field] as $index => $item) {
                            try {
                                $this->validatePrimitive($type, $data[$field], $index);
                            } catch (ValidationError $e) {
                                throw new ValidationError(sprintf("%s item at pos[%s] is not a valid %s", $locator, $index, $type->getLabel()));
                            }

                        }
                    } else {
                        foreach ($data[$field] as $subUnit) {
                            Validator::Init($type->getTypeModel(), $subUnit)->validate();
                        }
                    }
                } else {
                    Validator::Init($type->getTypeModel(), $data[$field])->validate();
                }

            }
        }
    }

    /**
     * @throws ValidationError
     */
    private function validatePrimitive(Type $type, array $data, string $field): void
    {
        $value = $data[$field];
        $isValid = match ($type->getLabel()) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'int' => is_int($value),
            'float' => is_float($value),
            'enum' => in_array($value, $type->getEnumValues())
        };
        if (!$isValid) {
            if ($type->getLabel() === 'enum') {
                $enumValues = implode("','", $type->getEnumValues());
                throw new ValidationError(
                    sprintf("%s::%s must be one of ['%s']",
                        $this->model->getModelName(), $field, $enumValues
                    )
                );
            } else {
                throw new ValidationError(sprintf("%s::%s is not a valid %s", $this->model->getModelName(), $field, $type->getLabel()));
            }

        }
    }
}
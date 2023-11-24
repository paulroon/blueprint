<?php

namespace HappyCode\Blueprint;

use HappyCode\Blueprint\Error\BuildError;

class Adapter
{
    public function __construct(
        private readonly Model $model,
        private readonly array $data
    ){}

    public static function Init(Model $model, mixed $data): static
    {
        return new static($model, $data);
    }

    /**
     * @throws BuildError
     */
    public function adapt(): RootCollection|ModelData
    {
        if ($this->model->isRootCollection()) {
            $collectionItems = [];
            foreach ($this->data as $unitItem) {
                $collectionItems[] = $this->adaptDefinition($this->model, $unitItem);
            }
            return new RootCollection($collectionItems);
        } else {
            return $this->adaptDefinition($this->model, $this->data);
        }
    }


    /**
     * @throws BuildError
     */
    private function adaptDefinition(Model $model, array $data): ModelData
    {
        if ($model->isUseFS()) {
            $class = $this->ensureGeneratedConcreteClass($model);
            $class = new $class($data);
        } else {
            $class = new class($data) extends ModelData {};
        }

        /** @var Type $type */
        foreach ($model->getDefinition() as $field => $type) {

            if ($type->isHidden()) {
                continue;
            }

            $exists = in_array($field, array_keys($data));

            $value = $exists ? $data[$field] : null;

            if ($value === null || $type->isPrimitive()) {
                $returnValue = $value;
            } else {
                $typeModel = $type->getTypeModel();
                if ($type->isArray()) {
                    if (!$typeModel) {
                        $returnValue = $value;
                    } else {
                        $returnValue = [];
                        foreach ($data[$field] as $unitItem) {
                            $returnValue[] = Adapter::Init($typeModel, $unitItem)->adapt();
                        }
                    }

                } else if ($type->getLabel() === "datetime") {
                    $returnValue = \DateTime::createFromFormat($type->getInputFormat(), $data[$field])
                        ->format($type->getOutputFormat());
                } else {
                    $returnValue = Adapter::Init($typeModel, $data[$field])->adapt();
                }

            }

            $class->addGetterMethod($field, $returnValue);
        }


        /**
         * we do a second pass to ensure all natural (non-virtual) fields and getters have been created already
         * @var Type $type
         */
        foreach ($model->getDefinition() as $field => $type) {
            if ($type->isVirtual()) {
                $class->addGetterMethod($field, $type->getTransformerFn());
            }
        }

        return $class;
    }

    private function ensureGeneratedConcreteClass(Model $model): string
    {
        return ClassGenerator::ClassFromModel($model);
    }
}
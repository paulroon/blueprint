<?php

namespace HappyCode\Blueprint;

abstract class ModelData implements ModelDataInterface
{
    private array $getterMethods = [];

    public function __construct(protected readonly array $data)
    {}

    public function addGetterMethod(string $field, mixed $returnValue): void
    {
        $methodName = sprintf("get%s", ucfirst($field));
        if ($returnValue instanceof \Closure) {
            $returnValue = $returnValue($this->data);
        }
        $this->getterMethods[$methodName] = fn () => $returnValue;
    }

    /**
     * decode ModelData to json
     */
    public function json(): string
    {
        $jObj = [];
        foreach ($this->getterMethods as $methodName => $fn) {
            $fieldName = lcfirst(substr($methodName, 3));
            $value = $this->$methodName();
            if ($value instanceof ModelDataInterface) {
                $value = json_decode($value->json(), true);
            } elseif (is_array($value) && $value[0] instanceof ModelDataInterface) {
                $value = array_map(function($item) {
                    return json_decode($item->json(), true);
                }, $value);
            }
            $jObj[$fieldName] = $value;
        }

        return json_encode($jObj);
    }

    /**
     * @throws \Exception
     */
    public function __call(string $methodName, array $arguments) {
        if (isset($this->getterMethods[$methodName])) {
            return call_user_func_array($this->getterMethods[$methodName], $arguments);
        }
        throw new \Exception("Method $methodName does not exist");
    }

}
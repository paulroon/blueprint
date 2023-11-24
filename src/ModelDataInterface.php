<?php

namespace HappyCode\Blueprint;

interface ModelDataInterface
{
    public function addGetterMethod(string $field, mixed $returnValue): void;
}
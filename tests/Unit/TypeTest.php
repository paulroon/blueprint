<?php

namespace HappyCode\Test\Blueprint\Unit;

use HappyCode\Blueprint\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testInt()
    {
        $type = Type::Int();
        $this->assertInstanceOf(Type::class, $type);
        $this->assertTrue($type->isPrimitive(), "Int must be primitive");
        $this->assertFalse($type->isArray(), "Int is not an array");
        $this->assertFalse($type->isVirtual(), "Int is not a virtual field");
        $this->assertFalse($type->isNullable(), "Int is not nullable");
    }
//    public function testGetTransformerFn()
//    {
//
//    }
//
//    public function testDateTime()
//    {
//
//    }
//
//    public function testCollection()
//    {
//
//    }
//
//    public function testIsNullable()
//    {
//
//    }
//
//    public function testEnum()
//    {
//
//    }
//
//    public function testIsRequired()
//    {
//
//    }
//
//    public function testGetTypeModel()
//    {
//
//    }
//
//    public function testGetReferencableTypeName()
//    {
//
//    }
//
//    public function testGetEnumValues()
//    {
//
//    }
//
//    public function testFloat()
//    {
//
//    }
//
//    public function testIsVirtual()
//    {
//
//    }
//
//    public function testGetInputFormat()
//    {
//
//    }
//
//    public function testIsHidden()
//    {
//
//    }
//
//    public function testArrayOf()
//    {
//
//    }
//
//    public function testString()
//    {
//
//    }
//
//    public function testBoolean()
//    {
//
//    }
//
//    public function testModel()
//    {
//
//    }
//
//    public function testIsPrimitive()
//    {
//
//    }
//
//    public function testIsArray()
//    {
//
//    }
//
//    public function testGetOutputFormat()
//    {
//
//    }
//
//    public function testVirtual()
//    {
//
//    }
//
//    public function testGetLabel()
//    {
//
//    }
}

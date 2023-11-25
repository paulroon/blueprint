<?php

namespace HappyCode\Test\Blueprint\Unit;

use HappyCode\Blueprint\Model;
use HappyCode\Blueprint\Type;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->mockModel = $this->createMock(Model::class);
        parent::setUp();
    }

    public function testString()
    {
        $type = Type::String();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("string", $type->getLabel());
        $this->assertTrue($type->isPrimitive(), "String must be primitive");
        $this->assertTrue($type->isRequired(), "String is Required");
        $this->assertFalse($type->isArray(), "String is not an array");
        $this->assertFalse($type->isVirtual(), "String is not a virtual field");
        $this->assertFalse($type->isNullable(), "String is not nullable");
    }

    public function testInt()
    {
        $type = Type::Int();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("int", $type->getLabel());
        $this->assertTrue($type->isPrimitive(), "Int must be primitive");
        $this->assertTrue($type->isRequired(), "Int is Required");
        $this->assertFalse($type->isArray(), "Int is not an array");
        $this->assertFalse($type->isVirtual(), "Int is not a virtual field");
        $this->assertFalse($type->isNullable(), "Int is not nullable");
    }

    public function testFloat()
    {
        $type = Type::Float();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("float", $type->getLabel());
        $this->assertTrue($type->isPrimitive(), "Float must be primitive");
        $this->assertTrue($type->isRequired(), "Float is Required");
        $this->assertFalse($type->isArray(), "Float is not an array");
        $this->assertFalse($type->isVirtual(), "Float is not a virtual field");
        $this->assertFalse($type->isNullable(), "Float is not nullable");
    }

    public function testBoolean()
    {
        $type = Type::Boolean();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("boolean", $type->getLabel());
        $this->assertTrue($type->isPrimitive(), "Boolean must be primitive");
        $this->assertTrue($type->isRequired(), "Boolean is Required");
        $this->assertFalse($type->isArray(), "Boolean is not an array");
        $this->assertFalse($type->isVirtual(), "Boolean is not a virtual field");
        $this->assertFalse($type->isNullable(), "Boolean is not nullable");
    }

    public function testEnum()
    {
        $type = Type::Enum(values: ["Harry", "Ron", "Hermione"]);

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("enum", $type->getLabel());
        $this->assertTrue($type->isRequired(), "Enum is Required");
        $this->assertTrue($type->isPrimitive(), "Enum must be primitive");
        $this->assertEquals(["Harry", "Ron", "Hermione"], $type->getEnumValues(), "Enum must maintain a set of values");
        $this->assertFalse($type->isArray(), "Enum is not an array");
        $this->assertFalse($type->isVirtual(), "Enum is not a virtual field");
        $this->assertFalse($type->isNullable(), "Enum is not nullable");
    }

    public function testDateTime()
    {
        $defaultFormat = 'd/m/Y H:i:s';
        $altFormat = 'dmY';
        $type = Type::DateTime();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("datetime", $type->getLabel());
        $this->assertTrue($type->isRequired(), "DateTime is Required");
        $this->assertFalse($type->isPrimitive(), "DateTime must not be primitive");
        $this->assertFalse($type->isArray(), "DateTime is not an array");
        $this->assertFalse($type->isVirtual(), "DateTime is not a virtual field");
        $this->assertFalse($type->isNullable(), "DateTime is not nullable");
        $this->assertEquals($defaultFormat, $type->getInputFormat(), 'DateTime input defaults to standard format');
        $this->assertEquals($defaultFormat, $type->getOutputFormat(), 'DateTime output defaults to standard format');


        $type = Type::DateTime(inputFormat: $altFormat, outputFormat: $altFormat);
        $this->assertEquals($altFormat, $type->getInputFormat(), 'DateTime input can use alt format');
        $this->assertEquals($altFormat, $type->getOutputFormat(), 'DateTime output can use alt format');
    }

    public function testCollection()
    {
        $type = Type::Collection($this->mockModel);

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("", $type->getLabel());
        $this->assertTrue($type->isRequired(), "Collection is Required");
        $this->assertFalse($type->isPrimitive(), "Collection must not be primitive");
        $this->assertTrue($type->isArray(), "Collection is an array");
        $this->assertFalse($type->isVirtual(), "Collection is not a virtual field");
        $this->assertFalse($type->isNullable(), "Collection is not nullable");
        $this->assertInstanceOf(Model::class, $type->getTypeModel(), "Collection model should be a Model");
    }

    public function testModel()
    {
        $type = Type::Model($this->mockModel);

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("", $type->getLabel());
        $this->assertTrue($type->isRequired(), "Model is Required");
        $this->assertFalse($type->isPrimitive(), "Model must not be primitive");
        $this->assertFalse($type->isArray(), "Model must not be an array");
        $this->assertFalse($type->isVirtual(), "Model is not a virtual field");
        $this->assertFalse($type->isNullable(), "Model is not nullable");
    }

    public function testVirtual()
    {
        $type = Type::Virtual(function(){});

        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("string", $type->getLabel());
        $this->assertFalse($type->isRequired(), "Virtual must not be Required");
        $this->assertTrue($type->isPrimitive(), "Virtual must be primitive");
        $this->assertFalse($type->isArray(), "Virtual must not be an array");
        $this->assertTrue($type->isVirtual(), "Virtual must be a virtual field");
        $this->assertTrue($type->isNullable(), "Virtual must be nullable");
        $this->assertInstanceOf(\Closure::class, $type->getTransformerFn(), "Virtual must have a transformer Fn");
    }

    public function testArrayOf()
    {
        $type = Type::ArrayOf(Type::String());
        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals("string", $type->getLabel());
        $this->assertTrue($type->isRequired(), "ArrayOf must be Required");
        $this->assertTrue($type->isArray(), "ArrayOf must be an array");
        $this->assertFalse($type->isNullable(), "ArrayOf must not be nullable");
        $this->assertFalse($type->isPrimitive(), "ArrayOf must not be primitive");
        $this->assertFalse($type->isVirtual(), "ArrayOf must not be virtual");
    }

}

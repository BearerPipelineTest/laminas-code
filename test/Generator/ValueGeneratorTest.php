<?php

/**
 * @see       https://github.com/laminas/laminas-code for the canonical source repository
 * @copyright https://github.com/laminas/laminas-code/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-code/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Code\Generator;

use ArrayAccess;
use ArrayObject as SplArrayObject;
use Laminas\Code\Exception\InvalidArgumentException;
use Laminas\Code\Generator\ValueGenerator;
use Laminas\Stdlib\ArrayObject as StdlibArrayObject;

/**
 * @group Laminas_Code_Generator
 * @group Laminas_Code_Generator_Php
 *
 * @covers \Laminas\Code\Generator\ValueGenerator
 */
class ValueGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultInstance()
    {
        $valueGenerator = new ValueGenerator();

        $this->assertInstanceOf(SplArrayObject::class, $valueGenerator->getConstants());
    }

    public function testInvalidConstantsType()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            '$constants must be an instance of ArrayObject or Laminas\Stdlib\ArrayObject'
        );

        $constants = $this->getMock(ArrayAccess::class);
        new ValueGenerator(null, ValueGenerator::TYPE_AUTO, ValueGenerator::OUTPUT_MULTIPLE_LINE, $constants);
    }

    /**
     * @dataProvider constantsTypeProvider
     */
    public function testAllowedPossibleConstantsType($constants)
    {
        $valueGenerator = new ValueGenerator(null, ValueGenerator::TYPE_AUTO, ValueGenerator::OUTPUT_MULTIPLE_LINE, $constants);

        $this->assertSame($constants, $valueGenerator->getConstants());
    }

    public function constantsTypeProvider()
    {
        return [
            SplArrayObject::class => [new SplArrayObject()],
            StdlibArrayObject::class => [new StdlibArrayObject()],
        ];
    }

    /**
     * @return array
     */
    protected function generateArrayData($longOutput, $value)
    {
        $shortOutput = str_replace(
            ['array(', ')'],
            ['[', ']'],
            $longOutput
        );

        return [
            'auto'        => [
                ValueGenerator::TYPE_AUTO, $value, $longOutput
            ],
            'array'       => [
                ValueGenerator::TYPE_ARRAY, $value, $longOutput
            ],
            'array long'  => [
                ValueGenerator::TYPE_ARRAY_LONG, $value, $longOutput
            ],
            'array short' => [
                ValueGenerator::TYPE_ARRAY_SHORT, $value, $shortOutput
            ],
        ];
    }

    /**
     * Data provider for testPropertyDefaultValueCanHandleArray test
     *
     * @return array
     */
    public function simpleArrayProvider()
    {
        $value = ['foo'];

        $longOutput = <<<EOS
array(
    'foo',
)
EOS;

        return $this->generateArrayData($longOutput, $value);
    }

    /**
     * Data provider for testPropertyDefaultValueCanHandleComplexArrayOfTypes test
     *
     * @return array
     */
    public function complexArrayProvider()
    {
        $value = [
            5,
            'one' => 1,
            'two' => '2',
            'constant1' => "__DIR__ . '/anydir1/anydir2'",
            [
                'baz' => true,
                'foo',
                'bar',
                [
                    'baz1',
                    'baz2',
                    'constant2' => 'ArrayObject::STD_PROP_LIST',
                ]
            ],
            new ValueGenerator('PHP_EOL', 'constant')
        ];

        $longOutput = <<<EOS
array(
    5,
    'one' => 1,
    'two' => '2',
    'constant1' => __DIR__ . '/anydir1/anydir2',
    array(
        'baz' => true,
        'foo',
        'bar',
        array(
            'baz1',
            'baz2',
            'constant2' => ArrayObject::STD_PROP_LIST,
        ),
    ),
    PHP_EOL,
)
EOS;

        return $this->generateArrayData($longOutput, $value);
    }

    /**
     * Data provider for testPropertyDefaultValueCanHandleArrayWithUnsortedKeys test
     *
     * @return array
     */
    public function unsortedKeysArrayProvider()
    {
        $value = [
            1 => 'a',
            0 => 'b',
            'c',
            7 => 'd',
            3 => 'e'
        ];

        $longOutput = <<<EOS
array(
    1 => 'a',
    0 => 'b',
    'c',
    7 => 'd',
    3 => 'e',
)
EOS;

        return $this->generateArrayData($longOutput, $value);
    }

    /**
     * @dataProvider unsortedKeysArrayProvider
     */
    public function testPropertyDefaultValueCanHandleArrayWithUnsortedKeys($type, $value, $expected)
    {
        $valueGenerator = new ValueGenerator();
        $valueGenerator->setType($type);
        $valueGenerator->setValue($value);

        $this->assertEquals($expected, $valueGenerator->generate());
    }

    public function testPropertyDefaultValueConstructor()
    {
        $valueGenerator = new ValueGenerator();
        $this->isInstanceOf($valueGenerator, 'Laminas\Code\Generator\ValueGenerator');
    }

    public function testPropertyDefaultValueIsSettable()
    {
        $valueGenerator = new ValueGenerator();
        $valueGenerator->setValue('foo');
        $this->assertEquals('foo', $valueGenerator->getValue());
    }

    public function testPropertyDefaultValueCanHandleStrings()
    {
        $valueGenerator = new ValueGenerator();
        $valueGenerator->setValue('foo');
        $this->assertEquals("'foo'", $valueGenerator->generate());
    }

    /**
     * @dataProvider simpleArrayProvider
     */
    public function testPropertyDefaultValueCanHandleArray($type, $value, $expected)
    {
        $valueGenerator = new ValueGenerator();
        $valueGenerator->setType($type);
        $valueGenerator->setValue($value);

        $this->assertEquals($expected, $valueGenerator->generate());
    }

    public function testPropertyDefaultValueCanHandleUnquotedString()
    {
        $valueGenerator = new ValueGenerator();
        $valueGenerator->setValue('PHP_EOL');
        $valueGenerator->setType('constant');
        $this->assertEquals('PHP_EOL', $valueGenerator->generate());

        $valueGenerator = new ValueGenerator();
        $valueGenerator->setValue(5);
        $this->assertEquals('5', $valueGenerator->generate());

        $valueGenerator = new ValueGenerator();
        $valueGenerator->setValue(5.25);
        $this->assertEquals('5.25', $valueGenerator->generate());
    }

    /**
     * @dataProvider complexArrayProvider
     */
    public function testPropertyDefaultValueCanHandleComplexArrayOfTypes($type, $value, $expected)
    {
        $valueGenerator = new ValueGenerator();
        $valueGenerator->initEnvironmentConstants();
        $valueGenerator->setType($type);
        $valueGenerator->setValue($value);

        $this->assertEquals($expected, $valueGenerator->generate());
    }

    /**
     * @group 6023
     *
     * @dataProvider getEscapedParameters
     */
    public function testEscaping($input, $expectedEscapedValue)
    {
        $this->assertSame($expectedEscapedValue, ValueGenerator::escape($input, false));
    }

    /**
     * Data provider for escaping tests
     *
     * @return string[][]
     */
    public function getEscapedParameters()
    {
        return [
            ['\\', '\\\\'],
            ["'", "\\'"],
            ["\\'", "\\\\\\'"],
        ];
    }
}

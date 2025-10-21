<?php declare(strict_types=1);

namespace Bref\Tests\Event\Http;

use Bref\Event\Http\Psr7Bridge;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class Psr7BridgeMergeTest extends TestCase
{
    private function getMergeMethod()
    {
        $refClass = new ReflectionClass(Psr7Bridge::class);
        $method = $refClass->getMethod('mergeRecursivePreserveNumeric');
        $method->setAccessible(true);
        return $method;
    }

    public function testMergeRecursivePreserveNumericBasicExamples()
    {
        $method = $this->getMergeMethod();

        // Test case 1: References with numeric keys
        $a1 = [
            'references' => [
                0 => ['some_id' => '4390954279', 'url' => ''],
                1 => ['some_id' => '4313323164'],
            ],
        ];

        $b1 = [
            'references' => [
                1 => ['url' => ''],
            ],
        ];

        $expected1 = [
            'references' => [
                0 => ['some_id' => '4390954279', 'url' => ''],
                1 => ['some_id' => '4313323164', 'url' => ''],
            ],
        ];

        $result1 = $method->invoke(null, $a1, $b1);
        $this->assertEquals($expected1, $result1);

        // Test case 2: Delete categories with scalar arrays
        $a2 = [
            'delete' => [
                'categories' => ['123'],
            ],
        ];

        $b2 = [
            'delete' => [
                'categories' => ['456'],
            ],
        ];

        $expected2 = [
            'delete' => [
                'categories' => ['123', '456'],
            ],
        ];

        $result2 = $method->invoke(null, $a2, $b2);
        $this->assertEquals($expected2, $result2);
    }

    public function testMergeWithEmptyArrays()
    {
        $method = $this->getMergeMethod();

        // Test merging with empty array
        $a = ['key' => ['value1']];
        $b = ['key' => []];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['key' => ['value1']], $result);

        // Test merging empty array with non-empty
        $a = ['key' => []];
        $b = ['key' => ['value1']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['key' => ['value1']], $result);
    }

    public function testMergeWithScalarValues()
    {
        $method = $this->getMergeMethod();

        // Test scalar values override arrays
        $a = ['key' => ['nested' => 'value']];
        $b = ['key' => 'scalar'];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['key' => 'scalar'], $result);

        // Test arrays override scalars
        $a = ['key' => 'scalar'];
        $b = ['key' => ['nested' => 'value']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['key' => ['nested' => 'value']], $result);
    }

    public function testMergeWithMixedArrayTypes()
    {
        $method = $this->getMergeMethod();

        // Test associative array with list
        $a = ['data' => ['key1' => 'value1']];
        $b = ['data' => ['value2', 'value3']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['data' => ['key1' => 'value1', 0 => 'value2', 1 => 'value3']], $result);

        // Test list with associative array
        $a = ['data' => ['value1', 'value2']];
        $b = ['data' => ['key1' => 'value3']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['data' => [0 => 'value1', 1 => 'value2', 'key1' => 'value3']], $result);
    }

    public function testMergeWithNestedObjects()
    {
        $method = $this->getMergeMethod();

        // Test nested object merging
        $a = [
            'users' => [
                0 => ['id' => 1, 'name' => 'John'],
                1 => ['id' => 2, 'name' => 'Jane'],
            ],
        ];

        $b = [
            'users' => [
                0 => ['email' => 'john@example.com'],
                1 => ['email' => 'jane@example.com', 'age' => 25],
            ],
        ];

        $expected = [
            'users' => [
                0 => ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                1 => ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
            ],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);
    }

    public function testMergeWithDifferentArraySizes()
    {
        $method = $this->getMergeMethod();

        // Test when array A is larger
        $a = [
            'items' => [
                0 => ['id' => 1],
                1 => ['id' => 2],
                2 => ['id' => 3],
            ],
        ];

        $b = [
            'items' => [
                0 => ['name' => 'Item 1'],
                1 => ['name' => 'Item 2'],
            ],
        ];

        $expected = [
            'items' => [
                0 => ['id' => 1, 'name' => 'Item 1'],
                1 => ['id' => 2, 'name' => 'Item 2'],
                2 => ['id' => 3],
            ],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);

        // Test when array B is larger
        $a = [
            'items' => [
                0 => ['id' => 1],
            ],
        ];

        $b = [
            'items' => [
                0 => ['name' => 'Item 1'],
                1 => ['id' => 2, 'name' => 'Item 2'],
                2 => ['id' => 3, 'name' => 'Item 3'],
            ],
        ];

        $expected = [
            'items' => [
                0 => ['id' => 1, 'name' => 'Item 1'],
                1 => ['id' => 2, 'name' => 'Item 2'],
                2 => ['id' => 3, 'name' => 'Item 3'],
            ],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);
    }

    public function testMergeWithScalarArrays()
    {
        $method = $this->getMergeMethod();

        // Test scalar arrays (lists) are appended
        $a = ['tags' => ['php', 'testing']];
        $b = ['tags' => ['unit', 'integration']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['tags' => ['php', 'testing', 'unit', 'integration']], $result);

        // Test with empty scalar arrays
        $a = ['tags' => []];
        $b = ['tags' => ['new', 'tags']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['tags' => ['new', 'tags']], $result);
    }

    public function testMergeWithComplexNestedStructure()
    {
        $method = $this->getMergeMethod();

        $a = [
            'config' => [
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306,
                ],
                'cache' => [
                    'driver' => 'redis',
                    'servers' => ['server1', 'server2'],
                ],
            ],
            'features' => ['auth', 'logging'],
        ];

        $b = [
            'config' => [
                'database' => [
                    'name' => 'myapp',
                    'port' => 5432, // This should override
                ],
                'cache' => [
                    'servers' => ['server3'], // This should be appended to existing servers
                ],
            ],
            'features' => ['monitoring'], // This should be appended
        ];

        $expected = [
            'config' => [
                'database' => [
                    'host' => 'localhost',
                    'port' => 5432, // Overridden
                    'name' => 'myapp',
                ],
                'cache' => [
                    'driver' => 'redis',
                    'servers' => ['server1', 'server2', 'server3'], // Appended
                ],
            ],
            'features' => ['auth', 'logging', 'monitoring'], // Appended
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);
    }

    public function testMergeWithNullValues()
    {
        $method = $this->getMergeMethod();

        // Test null values
        $a = ['key' => 'value'];
        $b = ['key' => null];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['key' => null], $result);

        // Test null with array
        $a = ['key' => null];
        $b = ['key' => ['nested' => 'value']];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['key' => ['nested' => 'value']], $result);
    }

    public function testMergeWithBooleanValues()
    {
        $method = $this->getMergeMethod();

        $a = ['enabled' => true];
        $b = ['enabled' => false];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['enabled' => false], $result);

        $a = ['settings' => ['debug' => true]];
        $b = ['settings' => ['debug' => false]];
        $result = $method->invoke(null, $a, $b);
        $this->assertEquals(['settings' => ['debug' => false]], $result);
    }

    public function testMergeWithNumericKeys()
    {
        $method = $this->getMergeMethod();

        // Test with non-sequential numeric keys
        $a = [
            'items' => [
                0 => 'first',
                2 => 'third',
            ],
        ];

        $b = [
            'items' => [
                1 => 'second',
                3 => 'fourth',
            ],
        ];

        $expected = [
            'items' => [
                0 => 'first',
                1 => 'second',
                2 => 'third',
                3 => 'fourth',
            ],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);
    }

    public function testMergeWithStringKeys()
    {
        $method = $this->getMergeMethod();

        $a = [
            'user' => [
                'name' => 'John',
                'age' => 30,
            ],
        ];

        $b = [
            'user' => [
                'email' => 'john@example.com',
                'age' => 31, // This should override
            ],
        ];

        $expected = [
            'user' => [
                'name' => 'John',
                'age' => 31, // Overridden
                'email' => 'john@example.com',
            ],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);
    }

    public function testMergeWithEmptyFirstArray()
    {
        $method = $this->getMergeMethod();

        $a = [];
        $b = [
            'key1' => 'value1',
            'key2' => ['nested' => 'value2'],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($b, $result);
    }

    public function testMergeWithEmptySecondArray()
    {
        $method = $this->getMergeMethod();

        $a = [
            'key1' => 'value1',
            'key2' => ['nested' => 'value2'],
        ];
        $b = [];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($a, $result);
    }

    public function testMergeWithDeepNesting()
    {
        $method = $this->getMergeMethod();

        $a = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 'original',
                        'array' => ['a', 'b'],
                    ],
                ],
            ],
        ];

        $b = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 'updated',
                        'array' => ['c', 'd'],
                        'new' => 'added',
                    ],
                ],
            ],
        ];

        $expected = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 'updated',
                        'array' => ['a', 'b', 'c', 'd'],
                        'new' => 'added',
                    ],
                ],
            ],
        ];

        $result = $method->invoke(null, $a, $b);
        $this->assertEquals($expected, $result);
    }
}

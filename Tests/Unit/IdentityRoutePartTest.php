<?php declare(strict_types=1);

namespace J6s\TransliteratedRoutes\Tests\Unit;

use J6s\TransliteratedRoutes\IdentityRoutePart;
use J6s\TransliteratedRoutes\Tests\Fixture\TestObject;
use Neos\Flow\Mvc\Routing\ObjectPathMappingRepository;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Ramsey\Uuid\Uuid;

class IdentityRoutePartTest extends UnitTestCase
{

    /** @var IdentityRoutePart */
    protected $identityRoutePart;

    /** @var PersistenceManagerInterface */
    protected $mockPersistenceManager;

    public function setUp(): void
    {
        $this->identityRoutePart = $this->getAccessibleMock(IdentityRoutePart::class, ['dummy']);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->identityRoutePart->_set('persistenceManager', $this->mockPersistenceManager);

        $mockObjectPathMappingRepository = $this->createMock(ObjectPathMappingRepository::class);
        $this->identityRoutePart->_set('objectPathMappingRepository', $mockObjectPathMappingRepository);
    }

    /**
     * @test
     * @dataProvider provideStrings
     */
    public function replacesCharactersWithAsciiVariants(string $input, array $options, string $expectedOutput): void
    {
        $options['objectType'] = 'foo';
        $options['uriPattern'] = 'bar';
        $this->identityRoutePart->setOptions($options);
        $output = $this->identityRoutePart->_call('rewriteForUri', $input);
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @test
     * @dataProvider provideStrings
     */
    public function replacesCharactersInObjectProperties(string $input, array $options, string $expectedOutput): void
    {
        $object = new TestObject();
        $object->myVar = $input;

        $options['uriPattern'] = '{myVar}';
        $options['objectType'] = TestObject::class;
        $this->identityRoutePart->setOptions($options);
        $this->identityRoutePart->setName('test');
        $result = $this->identityRoutePart->_call('createPathSegmentForObject', $object);

        $this->assertEquals($expectedOutput, $result);
    }


    /**
     * @test
     * @dataProvider provideStrings
     */
    public function replacesCharactersInCompleteResolutionChain(
        string $input,
        array $options,
        string $expectedOutput
    ): void {
        $object = $this->setupTestObject($input);
        $routeVars = [ 'test' => $object ];

        $options['uriPattern'] = '{myVar}';
        $options['objectType'] = TestObject::class;
        $this->identityRoutePart->setOptions($options);
        $this->identityRoutePart->setName('test');
        $result = $this->identityRoutePart->resolve($routeVars);

        $this->assertTrue($result);
        $this->assertEquals($expectedOutput, $this->identityRoutePart->getValue());
    }


    public function provideStrings(): array
    {
        return [
            'no transliterations' => [
                'foo bar baz',
                [],
                'foo-bar-baz'
            ],
            'default replacements: german special chars' => [
                'ä ö ü ß',
                [],
                'ae-oe-ue-ss'
            ],
            'transliteration: é' => [
                'é',
                [],
                'e'
            ],
            'custom replacements' => [
                'ö',
                [ 'replacements' => [ 'ö' => 'o' ] ],
                'o'
            ],
            'custom replacements disable default ones' => [
                'ö ä',
                [ 'replacements' => [ 'ö' => 'o' ] ],
                // Transliteration default is ä -> a
                'o-a'
            ]
        ];
    }

    /** @test */
    public function extractsRelevantInformationFromConfiguration(): void
    {
        $this->identityRoutePart->setOptions([
            'uriPattern' => '__uriPattern__',
            'objectType' => '__objectType__',
        ]);

        $this->assertEquals('__uriPattern__', $this->identityRoutePart->getUriPattern());
        $this->assertEquals('__objectType__', $this->identityRoutePart->getObjectType());
    }


    /** @test */
    public function passesConfigurationDownToParent(): void
    {
        $options = [
            'uriPattern' => '__uriPattern__',
            'objectType' => '__objectType__',
        ];
        $this->identityRoutePart->setOptions($options);
        $this->assertEquals($options, $this->identityRoutePart->getOptions());
    }

    /**
     * @test
     * @dataProvider provideInvalidUtf8
     */
    public function skipsTransliterationIfStringContainsInvalidUtf8Symbols(string $invalid, string $expected): void
    {
        $output = $this->identityRoutePart->_call('rewriteForUri', $invalid);
        $this->assertEquals($expected, $output);
    }

    public function provideInvalidUtf8(): array
    {
        return [
            'control group' => [ 'foébar', 'foebar' ],
            '\xC2' => [ "foé\xC2bar", 'fobar' ],
            '\xAD' => [ "foé\xADbar", 'fobar' ],
            '\xE2' => [ "foé\xE2\x80bar", 'fobar' ],
        ];
    }

    /**
     * Parent object forces us not to have type annotations, so this test is the next best thing.
     * @test
     * @dataProvider provideInvalidInputData
     * @doesNotPerformAssertions
     */
    public function doesNotFailOnInvalidInputData($data): void
    {
        $this->identityRoutePart->_call('rewriteForUri', $data);
    }

    /**
     * Parent object forces us not to have type annotations, so this test is the next best thing.
     * @test
     * @dataProvider provideInvalidInputData
     */
    public function doesNotFailOnNullObjectProperty($data): void
    {
        $object = $this->setupTestObject($data);
        $routeVars = [ 'test' => $object ];

        $options['uriPattern'] = '{myVar}';
        $options['objectType'] = TestObject::class;
        $this->identityRoutePart->setOptions($options);
        $this->identityRoutePart->setName('test');
        $result = $this->identityRoutePart->resolve($routeVars);

        $this->assertTrue($result);
    }

    public function provideInvalidInputData(): array
    {
        return [
            [ null ],
            [ [ 'foo' => 'bar' ] ],
            [ 2 ],
            [ false ],
        ];
    }

    private function setupTestObject($myVar): TestObject
    {
        $object = new TestObject();
        $object->myVar = $myVar;

        $uuid = (string) Uuid::uuid4();
        $this->mockPersistenceManager->expects(self::once())
            ->method('getIdentifierByObject')
            ->with($object)
            ->will(self::returnValue($uuid));
        $this->mockPersistenceManager->expects(self::atLeastOnce())
            ->method('getObjectByIdentifier')
            ->with($uuid)
            ->will(self::returnValue($object));

        return $object;
    }
}

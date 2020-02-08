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

    protected function setUp(): void
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
    public function replacesCharactersInCompleteResolutionChain(string $input, array $options, string $expectedOutput): void
    {
        $object = new TestObject();
        $object->myVar = $input;
        $routeVars = [ 'test' => $object ];

        $uuid = (string) Uuid::uuid4();
        $this->mockPersistenceManager->expects(self::once())
            ->method('getIdentifierByObject')
            ->with($object)
            ->will(self::returnValue($uuid));
        $this->mockPersistenceManager->expects(self::atLeastOnce())
            ->method('getObjectByIdentifier')
            ->with($uuid)
            ->will(self::returnValue($object));


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

}

<?php


namespace J6s\TransliteratedRoutes\Tests\Unit;

// Extending original flow test to make sure we don't break any of the default behaviour.
use J6s\TransliteratedRoutes\IdentityRoutePart;
use Neos\Flow\Mvc\Routing\ObjectPathMappingRepository;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;

class FlowIdentityRoutePartTest extends \Neos\Flow\Tests\Unit\Mvc\Routing\IdentityRoutePartTest
{
    protected function setUp(): void
    {
        $this->identityRoutePart = $this->getAccessibleMock(
            IdentityRoutePart::class,
            ['createPathSegmentForObject']
        );

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->identityRoutePart->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->mockClassSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService
            ->expects(self::any())
            ->method('getClassSchema')
            ->will(self::returnValue($this->mockClassSchema));
        $this->identityRoutePart->_set('reflectionService', $this->mockReflectionService);

        $this->mockObjectPathMappingRepository = $this->createMock(ObjectPathMappingRepository::class);
        $this->identityRoutePart->_set('objectPathMappingRepository', $this->mockObjectPathMappingRepository);
    }
}

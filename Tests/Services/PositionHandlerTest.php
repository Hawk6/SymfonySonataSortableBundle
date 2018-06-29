<?php

namespace Hawk6\Bundle\SonataSortableBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Hawk6\Bundle\SonataSortableBundle\Services\PositionHandler;
use Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable as Sortable;
/**
 * Test of PositionHandler
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
class PositionHandlerTest extends WebTestCase
{

    protected $reader;
    protected $entityManager;
    protected $repository;
    protected $queryBuilder;
    protected $query;

    /**
     * @var $handler PositionHandler
     */
    private $handler = null;

    /**
     * Set up.
     */
    protected function setUp()
    {
        $this->handler = new PositionHandler();
        $this->reader = $this->createMock('Doctrine\Common\Annotations\AnnotationReader');
        $this->handler->setAnnotationReader($this->reader);

        $this->entityManager = $this->createMock('Doctrine\ORM\EntityManager', ['persist', 'flush'], [], '', false);
        $this->repository = $this->createMock('Doctrine\ORM\EntityRepository');
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));
        $this->queryBuilder = $this->createMock('Doctrine\ORM\QueryBuilder');
        $this->repository->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));
        $this->query = $this->getMockBuilder('Query')->setMethods(['getOneOrNullResult'])->getMock();
        $this->queryBuilder->expects($this->any())
            ->method('getQUery')
            ->will($this->returnValue($this->query));
        $this->handler->setEntityManager($this->entityManager);
    }


    /**
     * Test for getNewPosition: up direction
     */
    public function testGetNewPositionUp()
    {
        $item = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $maxPosition = 4;
        $item->setPosition(3);
        $newPosition = $this->handler->getNewPosition($item, 'up', $maxPosition);
        $this->assertSame($newPosition, 2);

        //position is less/eq than minimal
        $item->setPosition(0);
        $newPosition = $this->handler->getNewPosition($item, 'up', $maxPosition);
        $this->assertSame($newPosition, $item->getPosition());
    }

    /**
     * Test for getNewPosition: down direction
     */
    public function testGetNewPositionDown()
    {
        $item = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $maxPosition = 4;
        $item->setPosition(3);
        $newPosition = $this->handler->getNewPosition($item, 'down', $maxPosition);
        $this->assertSame($newPosition, 4);

        //position is greater than max
        $item->setPosition(5);
        $newPosition = $this->handler->getNewPosition($item, 'down', $maxPosition);
        $this->assertSame($newPosition, $item->getPosition());
    }

    /**
     * Test for getNewPosition: top direction
     */
    public function testGetNewPositionTop()
    {
        $item = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $maxPosition = 4;
        $item->setPosition(3);
        $newPosition = $this->handler->getNewPosition($item, 'top', $maxPosition);
        $this->assertSame($newPosition, PositionHandler::MIN_POSITION_VALUE);

        //position is less/eq than minimal
        $item->setPosition(5);
        $newPosition = $this->handler->getNewPosition($item, 'top', $maxPosition);
        $this->assertSame($newPosition, PositionHandler::MIN_POSITION_VALUE);
    }

    /**
     * Test for getNewPosition: bottom direction
     */
    public function testGetNewPositionBottom()
    {
        $item = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $maxPosition = 4;
        $item->setPosition(3);
        $newPosition = $this->handler->getNewPosition($item, 'bottom', $maxPosition);
        $this->assertSame($newPosition, $maxPosition);

        //position is greater than max
        $item->setPosition(5);
        $newPosition = $this->handler->getNewPosition($item, 'bottom', $maxPosition);
        $this->assertSame($newPosition, $item->getPosition());
    }

    /**
     * Test for getNewPosition: new direction
     */
    public function testGetNewPositionNew()
    {
        //position is greater than max
        $item = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $maxPosition = 4;

        $item->setPosition(5);
        $newPosition = $this->handler->getNewPosition($item, 'new', $maxPosition);
        $this->assertSame($newPosition, 5);

        //position is less than max
        $item->setPosition(3);
        $newPosition = $this->handler->getNewPosition($item, 'new', $maxPosition);
        $this->assertSame($newPosition, 5);
    }

    /**
     * Test for getNewPosition: invalid direction
     */
    public function testGetNewPositionInvalidDirectionTest()
    {
        $this->expectException('Hawk6\Bundle\SonataSortableBundle\Exception\InvalidArgumentException', 'Unknown position direction: fake');
        $item = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $maxPosition = 4;
        $item->setPosition(3);
        $newPosition = $this->handler->getNewPosition($item, 'fake', $maxPosition);
        $this->assertSame($newPosition, $item->getPosition());
    }

    /**
     * Test for setNewPosition
     */
    public function testSetNewPosition()
    {
        $this->markTestIncomplete('This test still needs some work.');

        $newItem = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $newItem->setPosition(1);

        $swappedItem = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $swappedItem->setPosition(2);

        $entityManager = $this->createMock('Doctrine\ORM\EntityManager', ['persist', 'flush'], [], '', false);
        $handler = $this->createMock(get_class($this->handler));
        $handler->setEntityManager($entityManager);
        $handler = \Mockery::mock($handler);
        $handler->shouldAllowMockingProtectedMethods()->shouldReceive('getEntityToSwap')->andReturn($swappedItem);

        //swap items
        $handler->setNewPosition($newItem, 2, 1);

        $this->assertSame($newItem->getPosition(),2);

        $this->assertSame($swappedItem->getPosition(),1);
    }

    /**
     * Test for getNewPositionForNewObject
     */
    public function testGetNewPositionForNewObject()
    {
        $newItem = $this->getMockForTrait('Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable');
        $newItem->setPosition(4);

        $handler = $this->createMock(get_class($this->handler));
        $handler->getNewPositionForNewObject($newItem);

        $this->assertSame($newItem->getPosition(), 4);
    }
}

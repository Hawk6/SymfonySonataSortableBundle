<?php

namespace Hawk6\Bundle\SonataSortableBundle\Admin\Behaviors;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Hawk6\Bundle\SonataSortableBundle\PositionHandler\PositionHandlerAwareTrait;

/**
 * sortable trait for use in admin forms.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
trait SortableTrait
{
    use PositionHandlerAwareTrait;

    /**
     * Override to order to sort by branch and position.
     *
     * @param string $context
     *
     * @return ProxyQuery
     */
    public function createQuery($context = 'list')
    {
        $queryBuilder = $this->getModelManager()->getEntityManager($this->getClass())->createQueryBuilder();
        /* @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $positions = $this->positionHandler->getPositionsFields($this->getClass());
        $queryBuilder->select('p')->from($this->getClass(), 'p');
        foreach ($positions->adminOrderBy as $key => $val) {
            $queryBuilder->addOrderBy(sprintf('p.%s', $key), $val);
        }

        $proxyQuery = new ProxyQuery($queryBuilder);

        return $proxyQuery;
    }

    /**
     * Setting new max available position for new object per branch.
     *
     * @param mixed $object
     *
     * @return void
     */
    public function prePersist($object)
    {
        $object->setPosition(
            $this->positionHandler->getNewPositionForNewObject($object)
        );
    }

    /**
     * Add up and down buttons in action of row.
     *
     * @param ListMapper $listMapper List mapper.
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->add(
            '_action',
            'actions',
            [
                'actions' => [
                    'move' => [
                        'template' => 'SonataSortableBundle:Default:_sort.html.twig',
                    ],
                ],
            ]
        );
    }

    /**
     * Configure routes.
     *
     * @param RouteCollection $collection Route collection.
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add(
            'move',
            $this->getRouterIdParameter().'/move/{position}'
        );

        $collection->add(
            'enable',
            $this->getRouterIdParameter().'/enable'
        );

        $collection->add(
            'disable',
            $this->getRouterIdParameter().'/disable'
        );

        $collection->add(
            'remove',
            $this->getRouterIdParameter().'/remove'
        );
    }
}

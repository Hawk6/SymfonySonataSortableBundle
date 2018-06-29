<?php
namespace Hawk6\Bundle\SonataSortableBundle\Services;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Hawk6\Bundle\SonataSortableBundle\Exception\InvalidArgumentException;
use Hawk6\Bundle\SonataSortableBundle\ORM\Mapping\PositionFields;

/**
 * Service to handle object position in admin lists.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
class PositionHandler
{
    const MIN_POSITION_VALUE = 1;

    const POSITION_CLASS = '\\Hawk6\\Bundle\\SonataSortableBundle\\ORM\\Mapping\\PositionFields';

    /**
     * @var string
     */
    const POSITION_UP     = 'up';
    const POSITION_DOWN   = 'down';
    const POSITION_TOP    = 'top';
    const POSITION_BOTTOM = 'bottom';
    const POSITION_NEW    = 'new';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Reader $reader
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * Gets max position.
     *
     * @param Object $entity
     *
     * @return integer
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxPosition($entity)
    {
        $positionsFields = $this->getPositionsFields($entity);
        $repository = $this->entityManager->getRepository(get_class($entity));
        $queryBuilder = $repository->createQueryBuilder('m');
        $queryBuilder->select('MAX(m.position) as max_position');

        if (!empty($positionsFields->searchFields)) { //In Twig render if empty it causes foreach crash...
            foreach ($positionsFields->searchFields as $val) {
                $queryBuilder->andWhere(sprintf('m.%s = :%s', $val, $val));
                $queryBuilder->setParameter($val, $this->getValue($entity, $val));
            }
        }
        $max = $queryBuilder->getQuery()->getOneOrNullResult();

        return $max === null ? $max : $max['max_position'];
    }

    /**
     * Gets new available position
     *
     * @param object  $object
     * @param string  $positionDirection Option/direction to which change position.
     * @param integer $maxPosition       Max position occurring in widgets in current branch.
     *
     * @return integer
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNewPosition($object, $positionDirection, $maxPosition)
    {
        $newPosition = $object->getPosition();

        switch ($positionDirection) {
            case self::POSITION_UP:
                if ($object->getPosition() > self::MIN_POSITION_VALUE) {
                    $newPosition = $this->findBestPositionUp($object);
                }
                break;

            case self::POSITION_DOWN:
                if ($object->getPosition() < $maxPosition) {
                    $newPosition = $object->getPosition() + 1;
                }
                break;

            case self::POSITION_TOP:
                if ($object->getPosition() > self::MIN_POSITION_VALUE) {
                    $newPosition = self::MIN_POSITION_VALUE;
                }
                break;

            case self::POSITION_BOTTOM:
                if ($object->getPosition() < $maxPosition) {
                    $newPosition = $maxPosition;
                }
                break;

            case self::POSITION_NEW:
                $newPosition = $maxPosition + 1;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position direction: %s', $positionDirection));
        }

        return $newPosition;
    }

    /**
     * Gets max position for widget in selected branch.
     *
     * @param object $entity
     *
     * @return integer
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNewPositionForNewObject($entity)
    {
        $maxPosition = $this->getMaxPosition($entity);

        return $this->getNewPosition($entity, 'new', $maxPosition);
    }

    /**
     * Swaps entities with their positions.
     *
     * @param object  $entity
     * @param integer $newPosition
     * @param integer $oldPosition
     *
     * @return object
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setNewPosition($entity, $newPosition, $oldPosition)
    {
        $swappedEntity = $this->getEntityToSwap($entity, $newPosition, $oldPosition);
        if (!empty($swappedEntity)) {
            if ($newPosition > $oldPosition) { //Moving down
                $betterOldPosition = $newPosition - 1;
            } elseif ($newPosition < $oldPosition) { // Moving up
                $betterOldPosition = $newPosition + 1;
            }
            $swappedEntity->setPosition($betterOldPosition);
            $entity->setPosition($newPosition);
            $this->entityManager->persist($swappedEntity);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } elseif ($newPosition === self::MIN_POSITION_VALUE && empty($swappedEntity)) {
            // When there is no entity to swap with on first position (somehow...)
            $entity->setPosition($newPosition);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            // Trigger the cleaning of next positions to fill up the gap.
            $this->cleanBeforeDelete($entity);
        }

        return $entity;
    }

    /**
     * Cleans before removing position.
     * Pulls up all positions of objects after the one that is being deleted.
     *
     * @param object $entity
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function cleanBeforeDelete($entity)
    {
        $positionsFields = $this->getPositionsFields($entity);
        $repository = $this->entityManager->getRepository(get_class($entity));
        $queryBuilder = $repository->createQueryBuilder('m');
        $queryBuilder->select('m');
        if (!empty($positionsFields->searchFields)) { //In Twig render if empty it causes foreach crash...
            foreach ($positionsFields->searchFields as $val) {
                $queryBuilder->andWhere(sprintf('m.%s = :%s', $val, $val));
                $queryBuilder->setParameter($val, $this->getValue($entity, $val));
            }
        }
        $queryBuilder->andWhere('m.position > :position');
        $queryBuilder->setParameter('position', $entity->getPosition());

        $results = $queryBuilder->getQuery()->getResult();

        foreach ($results as $result) {
            $result->setPosition($result->getPosition() - 1);
            $this->entityManager->persist($result);
        }

        $this->entityManager->flush();
    }

    /**
     * Retrieves position fields from entity annotations.
     *
     * @param object $object
     *
     * @return PositionFields
     */
    public function getPositionsFields($object)
    {
        return $this->reader->getClassAnnotation(
            new \ReflectionClass($object),
            self::POSITION_CLASS
        );
    }

    /**
     * Change enabled option of object.
     *
     * @param object  $entity
     * @param boolean $enabled
     *
     * @return object
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setEnabled($entity, $enabled)
    {
        $entity->setEnabled($enabled);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param object $entity
     * @param string $filed
     * @return mixed
     */
    protected function getValue($entity, $filed)
    {
        $method = sprintf('get%s', ucfirst($filed));

        return $entity->{$method}();
    }

    /**
     * Gets entity to swap position with.
     *
     * @param object  $entity
     * @param integer $newPosition
     * @param integer $oldPosition
     *
     * @return object
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getEntityToSwap($entity, $newPosition, $oldPosition)
    {
        $positionsFields = $this->getPositionsFields($entity);
        $repository = $this->entityManager->getRepository(get_class($entity));
        $queryBuilder = $repository->createQueryBuilder('m');
        $queryBuilder->select('m');
        if (!empty($positionsFields->searchFields)) { //In Twig render if empty it causes foreach crash...
            foreach ($positionsFields->searchFields as $val) {
                $queryBuilder->andWhere(sprintf('m.%s = :%s', $val, $val));
                $queryBuilder->setParameter($val, $this->getValue($entity, $val));
            }
        }

        if ($newPosition > $oldPosition) { //Moving down
            $queryBuilder->andWhere('m.position > :position');
            $queryBuilder->orderBy('m.position', 'ASC');
        } elseif ($newPosition < $oldPosition) { // Moving up
            $queryBuilder->andWhere('m.position < :position');
            $queryBuilder->orderBy('m.position', 'DESC');
        }

        $queryBuilder->setParameter('position', $oldPosition);
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds best position jumping eventual gaps.
     *
     * @param $entity
     * @return integer
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function findBestPositionUp($entity)
    {
        $result = $entity->getPosition() - 1;

        $positionsFields = $this->getPositionsFields($entity);
        $repository = $this->entityManager->getRepository(get_class($entity));
        $queryBuilder = $repository->createQueryBuilder('m');
        $queryBuilder->select('m');
        if (!empty($positionsFields->searchFields)) {
            foreach ($positionsFields->searchFields as $val) {
                $queryBuilder->andWhere(sprintf('m.%s = :%s', $val, $val));
                $queryBuilder->setParameter($val, $this->getValue($entity, $val));
            }
        }
        $queryBuilder->andWhere('m.position < :position');
        $queryBuilder->orderBy('m.position', 'DESC');
        $queryBuilder->setParameter('position', $entity->getPosition());
        $queryBuilder->setMaxResults(1);

        $queryResult = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!empty($queryResult)) {
            $result = $queryResult->getPosition();
        }

        return $result;
    }
}

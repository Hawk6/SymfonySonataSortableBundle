<?php

namespace Hawk6\Bundle\SonataSortableBundle\Twig;

use InvalidArgumentException;
use Twig_Extension;
use Twig_SimpleFunction;
use Hawk6\Bundle\SonataSortableBundle\PositionHandler\PositionHandlerAwareInterface;
use Hawk6\Bundle\SonataSortableBundle\PositionHandler\PositionHandlerAwareTrait;
use Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\SortableInterface;

/**
 * PositionExtension.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
class PositionExtension extends Twig_Extension implements PositionHandlerAwareInterface
{
    use PositionHandlerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction(
                'is_last_position',
                [$this, 'isLastPosition'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Return url.
     *
     * @param object $object
     *
     * @return bool
     */
    public function isLastPosition($object)
    {
        if (!$object instanceof SortableInterface) {
            throw new InvalidArgumentException('Object: '.get_class($object).' is not instance of SortableInterface');
        }

        if ($object->getPosition() < $this->positionHandler->getMaxPosition($object)) {
            return true;
        }

        return false;
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'position';
    }
}

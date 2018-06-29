<?php

namespace Hawk6\Bundle\SonataSortableBundle\PositionHandler;

use Hawk6\Bundle\SonataSortableBundle\Services\PositionHandler;

/**
 * Trait to get setter for an PositionHandler instance.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
trait PositionHandlerAwareTrait
{
    /**
     * @var PositionHandler
     */
    protected $positionHandler;

    /**
     * Inject an PositionHandler instance.
     *
     * @param PositionHandler $positionHandler
     *
     * @return PositionHandlerAwareInterface
     */
    public function setPositionHandler(PositionHandler $positionHandler)
    {
        $this->positionHandler = $positionHandler;

        return $this;
    }
}

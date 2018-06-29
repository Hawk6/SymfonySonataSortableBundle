<?php

namespace Hawk6\Bundle\SonataSortableBundle\PositionHandler;

use Hawk6\Bundle\SonataSortableBundle\Services\PositionHandler;

/**
 * Interface to get setter for an PositionHandler instance.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
interface PositionHandlerAwareInterface
{
    /**
     * Inject an PositionHandler instance.
     *
     * @param PositionHandler $positionHandler
     *
     * @return PositionHandlerAwareInterface
     */
    public function setPositionHandler(PositionHandler $positionHandler);
}

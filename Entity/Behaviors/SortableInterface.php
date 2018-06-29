<?php

namespace Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors;

/**
 * Empty interface for generic detection object which using sorting mechanism.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
interface SortableInterface
{
    /**
     * Set position.
     *
     * @param int $position
     *
     * @return SortableInterface
     */
    public function setPosition($position);

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition();
}

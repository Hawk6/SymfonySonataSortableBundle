<?php

namespace Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for sorting mechanism.
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
trait Sortable
{
    /**
     * @ORM\Column(type="integer")
     */
    protected $position;

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return SortableInterface
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
}

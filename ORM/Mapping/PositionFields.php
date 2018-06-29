<?php

namespace Hawk6\Bundle\SonataSortableBundle\ORM\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class PositionFields implements Annotation
{
    /**
     * Fields by which max position will be counted like per Branch.
     * If empty max position is counted for all elements.
     *
     * @var array
     */
    public $searchFields = null;

    /**
     * List of fields and their sort order.
     *
     * @var array
     */
    public $adminOrderBy = null;
}

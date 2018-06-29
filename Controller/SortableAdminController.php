<?php

namespace Hawk6\Bundle\SonataSortableBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\SortableInterface;

/**
 * Class SortableAdminController
 *
 * @author Łukasz Jastrzębski <ljastrz@gmail.com>
 */
class SortableAdminController extends CRUDController
{
    /**
     * Move list object
     *
     * @param integer $id
     * @param integer $position
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function moveAction($id, $position)
    {
        $object = $this->admin->getObject($id);
        if (!$object instanceof SortableInterface) {
            throw new \Exception('Object: '.get_class($object).' has to implement SortableInterface');
        }

        $positionHandler = $this->get('hawk6.sortable.admin.list.position_handler');
        /* @var PositionHandler $positionHandler */
        $translator = $this->get('translator');

        try {
            $maxPosition = $positionHandler->getMaxPosition($object);
            $newPosition = $positionHandler->getNewPosition($object, $position, $maxPosition);

            if ($newPosition !== $object->getPosition()) {
                $this->admin->update(
                    $positionHandler->setNewPosition(
                        $object,
                        $newPosition,
                        $object->getPosition()
                    )
                );

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(
                        array(
                            'result' => 'ok',
                            'objectId' => $this->admin->getNormalizedIdentifier($object),
                        )
                    );
                }

                $this->get('session')->getFlashBag()->set('sonata_flash_success', $translator->trans('Position updated'));
            } else {
                $this->get('session')->getFlashBag()->set('sonata_flash_info', $translator->trans('Item is already at requested position'));
            }
        } catch (\Exception $exception) {
            $this->get('session')->getFlashBag()->set('sonata_flash_error', $translator->trans('Error occurred'));
        }

        return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
    }

    /**
     * Remove object from list.
     * Cover blank space in positions.
     *
     * @param integer $id
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function removeAction($id)
    {
        $object = $this->admin->getObject($id);
        if (!($object instanceof SortableInterface)) {
            throw new \Exception('Object: '.get_class($object).' has to implement SortableInterface');
        }

        $positionHandler = $this->get('hawk6.sortable.admin.list.position_handler');
        $positionHandler->cleanBeforeDelete($object);
        $this->admin->delete($object);

        if ($this->isXmlHttpRequest()) {
            return $this->renderJson(
                array(
                    'result' => 'ok',
                    'objectId' => $this->admin->getNormalizedIdentifier($object),
                )
            );
        }
        $translator = $this->get('translator');
        $this->get('session')->getFlashBag()->set('sonata_flash_success', $translator->trans('Position removed'));

        return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
    }

    /**
     * Disables object.
     *
     * @param integer $id
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function disableAction($id)
    {
        return $this->changeEnabledOption($id, false);
    }

    /**
     * Enable object.
     *
     * @param integer $id
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function enableAction($id)
    {
        return $this->changeEnabledOption($id, true);
    }

    /**
     * Disables objects.
     *
     * @param integer $id
     * @param boolean $enabled
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    private function changeEnabledOption($id, $enabled)
    {
        $object = $this->admin->getObject($id);
        if (!($object instanceof SortableInterface)) {
            throw new \Exception('Object: '.get_class($object).' has to implement SortableInterface');
        }

        $positionHandler = $this->get('hawk6.sortable.admin.list.position_handler');
        $this->admin->update(
            $positionHandler->setEnabled($object, $enabled)
        );

        if ($this->isXmlHttpRequest()) {
            return $this->renderJson(
                array(
                    'result' => 'ok',
                    'objectId' => $this->admin->getNormalizedIdentifier($object),
                )
            );
        }

        if ($enabled) {
            $msg = 'Position enabled';
        } else {
            $msg = 'Position disabled';
        }

        $translator = $this->get('translator');
        $this->get('session')->getFlashBag()->set('sonata_flash_success', $translator->trans($msg));

        return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
    }
}

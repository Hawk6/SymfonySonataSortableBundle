# SymfonySonataSortableBundle
This Symfony bundle provides sortable and management options to sonata admin lists

Bundle in easy way adds sorting, position and common actions to admin list items in Sonata Admin list.


## Installation 

### Step 1: Download the Bundle
composer require hawk6/sonata-sortable-bundle

### Step 2: Enable the Bundle in project that has Sonata Admin Bundle


    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...

                new Hawk6\Bundle\SonataSortableBundle\SonataSortableBundle(),
            );

            // ...
        }

        // ...
    }

### Step 3: Usage

#### In Entity Class:

add 3 uses:

    use Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\Sortable as SortableTrait;
    use Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\SortableInterface;
    use Hawk6\Bundle\SonataSortableBundle\ORM\Mapping\PositionFields;
    
add Annotation, interface and trait use to entity class definition like:

    /**
     * Entity of SomeEntity
     *
     * @ORM\Entity(repositoryClass="Front\Repository\Some\SomeEntityRepository")
     * @ORM\Table()
     *
     * @PositionFields(searchFields={"language"}, adminOrderBy={"language"="ASC", "position"="ASC"})
     */
    class SomeEntity extends Base implements SortableInterface
    
    use SortableTrait;
    
In annotations you point searchFields of which field or field pairs will define a group to count max position value. Second param is to define sort orders.
Important is to add Trait use in your entity class that will add new field and methods to them.


#### In admin service yaml file: 

You have to change controller to custome from bundle and set a service call to set position handler to Admin service.

    #
    # SomeEntityAdmin
    #
    sonata.admin.someentity:
        class: Cms\Admin\SomeEntityAdmin
        tags:
            - { name: sonata.admin, manager_type: orm, group: "Some group", label: "Some Admin" }
        arguments:
            - ~
            - Front\Entity\SomeEntity
            - SonataSortableBundle:SortableAdmin
        calls:
            - [ setPositionHandler,   ["@hawk6.sortable.admin.list.position_handler"]]
            
#### Final step
To add theses uses:


    use Hawk6\Bundle\SonataSortableBundle\Admin\Behaviors\SortableTrait;
    use Hawk6\Bundle\SonataSortableBundle\PositionHandler\PositionHandlerAwareInterface;
    use Hawk6\Bundle\SonataSortableBundle\PositionHandler\PositionHandlerAwareTrait;
    use Hawk6\Bundle\SonataSortableBundle\Entity\Behaviors\SortableInterface;

add implements "PositionHandlerAwareInterface" to Admin Class like:

    class SomeEntityAdmin extends AbstractAdmin implements PositionHandlerAwareInterface

use SortableTrait:

    use SortableTrait {
        configureRoutes as configureRoutesTrait;
        configureListFields as configureListFieldsTrait;
    }

and have to use Trait methods in configureListFields and configureRoutes (if you've one in your class):

    /**
     * Fields in list view.
     *
     * @param ListMapper $listMapper List mapper.
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->add( ....
        
        ...
        
        $listMapper->add( ....
        $this->configureListFieldsTrait($listMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove(....
        
        ...
        
        
        $this->configureRoutesTrait($collection);
    }






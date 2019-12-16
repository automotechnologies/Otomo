<?php

namespace Cocorico\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormBuilderInterface;

class ListingFormBuilderEvent extends Event
{
    private $formBuilder;

    /**
     * @param FormBuilderInterface $formBuilder
     */
    public function __construct(FormBuilderInterface $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }
}

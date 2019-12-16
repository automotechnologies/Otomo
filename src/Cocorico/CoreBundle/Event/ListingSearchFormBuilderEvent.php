<?php

namespace Cocorico\CoreBundle\Event;

use Cocorico\CoreBundle\Model\ListingSearchRequest;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormBuilderInterface;

class ListingSearchFormBuilderEvent extends Event
{
    private $formBuilder;
    private $listingSearchRequest;

    /**
     * @param FormBuilderInterface $formBuilder
     * @param ListingSearchRequest $listingSearchRequest
     */
    public function __construct(FormBuilderInterface $formBuilder, ListingSearchRequest $listingSearchRequest)
    {
        $this->formBuilder = $formBuilder;
        $this->listingSearchRequest = $listingSearchRequest;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }


    /**
     * @return ListingSearchRequest
     */
    public function getListingSearchRequest()
    {
        return $this->listingSearchRequest;
    }
}

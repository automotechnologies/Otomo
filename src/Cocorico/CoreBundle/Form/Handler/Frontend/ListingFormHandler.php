<?php

namespace Cocorico\CoreBundle\Form\Handler\Frontend;

use Cocorico\CoreBundle\Entity\Booking;
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Model\Manager\ListingManager;
use Cocorico\UserBundle\Entity\User;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Handle Listing Form
 */
class ListingFormHandler
{
    protected $request;
    protected $listingManager;
    /** @var User|null */
    private $user = null;

    /**
     * @param TokenStorage         $securityTokenStorage
     * @param AuthorizationChecker $securityAuthChecker
     * @param RequestStack         $requestStack
     * @param ListingManager       $listingManager
     */
    public function __construct(
        TokenStorage $securityTokenStorage,
        AuthorizationChecker $securityAuthChecker,
        RequestStack $requestStack,
        ListingManager $listingManager
    ) {
        if ($securityAuthChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->user = $securityTokenStorage->getToken()->getUser();
        }
        $this->request = $requestStack->getCurrentRequest();
        $this->listingManager = $listingManager;

    }


    /**
     * @return Listing
     *
     */
    public function init()
    {
        //todo: move to ListingManager->initListing() see BookingManager->initBooking
        $listing = new Listing();
        $listing->setUser($this->user);
        $listing = $this->addImages($listing);
        $listing = $this->addCategories($listing);

        return $listing;
    }

    /**
     * Process form
     *
     * @param Form $form
     *
     * @return Booking|string
     * @throws OptimisticLockException
     */
    public function process($form)
    {
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $this->request->isMethod('POST') && $form->isValid()) {
            return $this->onSuccess($form);
        }

        return false;
    }

    /**
     * @param Form $form
     * @return boolean
     * @throws OptimisticLockException
     */
    private function onSuccess(Form $form)
    {
        /** @var Listing $listing */
        $listing = $form->getData();
        $this->listingManager->save($listing);

        return true;
    }


    /**
     * @param  Listing $listing
     * @return Listing
     */
    private function addImages(Listing $listing)
    {
        //Files to upload
        $imagesUploaded = $this->request->request->get("listing");
        $imagesUploaded = $imagesUploaded["image"]["uploaded"];

        if ($imagesUploaded) {
            $imagesUploadedArray = explode(",", trim($imagesUploaded, ","));
            $listing = $this->listingManager->addImages(
                $listing,
                $imagesUploadedArray
            );
        }

        return $listing;
    }

    /**
     * Add selected categories and corresponding fields values from post parameters while listing deposit
     *
     * @param  Listing $listing
     * @return Listing
     */
    public function addCategories(Listing $listing)
    {
        $categories = $this->request->request->get("listing_categories");

        $listingCategories = isset($categories["listingListingCategories"]) ? $categories["listingListingCategories"] : array();
        $listingCategoriesValues = isset($categories["categoriesFieldsSearchableValuesOrderedByGroup"]) ? $categories["categoriesFieldsSearchableValuesOrderedByGroup"] : array();

        if ($categories) {
            $listing = $this->listingManager->addCategories(
                $listing,
                $listingCategories,
                $listingCategoriesValues
            );
        }

        return $listing;
    }

}
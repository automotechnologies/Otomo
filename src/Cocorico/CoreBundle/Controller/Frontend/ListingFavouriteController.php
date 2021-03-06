<?php

namespace Cocorico\CoreBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListingFavouriteController extends ListingSearchController
{

    /**
     * Favourites Listings result.
     *
     * @Route("/listing/favourite", name="cocorico_listing_favourite")
     * @Method("GET")
     *
     * @param  Request $request
     * @return Response
     */
    public function indexFavouriteAction(Request $request)
    {
        $markers = [];
        $listings = new \ArrayIterator();
        $nbListings = 0;

        $listingSearchRequest = $this->getListingSearchRequest();
        $form = $this->createSearchResultForm($listingSearchRequest);

        $form->handleRequest($request);

        // handle the form for pagination
        if ($form->isSubmitted() && $form->isValid()) {
            $listingSearchRequest = $form->getData();
        }

        $favourites = explode(',', $request->cookies->get('favourite'));
        if (count($favourites) > 0) {
            $listingSearchRequest->setPage($request->query->get('page', 1));
            $results = $this->get("cocorico.listing_search.manager")->getListingsByIds(
                $listingSearchRequest,
                $favourites,
                $listingSearchRequest->getPage(),
                $request->getLocale()
            );
            $nbListings = $results->count();
            $listings = $results->getIterator();
            $markers = $this->getMarkers($request, $results, $listings);
        }

        return $this->render('@CocoricoCore/Frontend/ListingResult/result.html.twig', [
            'form' => $form->createView(),
            'listings' => $listings,
            'nb_listings' => $nbListings,
            'markers' => $markers['markers'],
            'listing_search_request' => $listingSearchRequest,
            'favourites' => $favourites,
            'pagination' => [
                'page' => $listingSearchRequest->getPage(),
                'pages_count' => ceil($nbListings / $listingSearchRequest->getMaxPerPage()),
                'route' => $request->get('_route'),
                'route_params' => $request->query->all()
            ]
        ]);

    }

}

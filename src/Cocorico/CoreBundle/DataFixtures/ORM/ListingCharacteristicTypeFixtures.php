<?php

namespace Cocorico\CoreBundle\DataFixtures\ORM;

use Cocorico\CoreBundle\Entity\ListingCharacteristicType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ListingCharacteristicTypeFixtures extends Fixture
{

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $listingCharacteristicType = new ListingCharacteristicType();
        $listingCharacteristicType->setName("Yes/No");
        $manager->persist($listingCharacteristicType);
        $manager->flush();
        $this->addReference('characteristic_type_yes_no', $listingCharacteristicType);

        $listingCharacteristicType = new ListingCharacteristicType();
        $listingCharacteristicType->setName("Quantity");
        $manager->persist($listingCharacteristicType);
        $manager->flush();
        $this->addReference('characteristic_type_quantity', $listingCharacteristicType);

        $listingCharacteristicType = new ListingCharacteristicType();
        $listingCharacteristicType->setName("Custom_1");
        $manager->persist($listingCharacteristicType);
        $manager->flush();
        $this->addReference('characteristic_type_custom_1', $listingCharacteristicType);
    }

}

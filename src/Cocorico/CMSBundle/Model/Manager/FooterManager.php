<?php

namespace Cocorico\CMSBundle\Model\Manager;

use Cocorico\CMSBundle\Entity\Footer;
use Cocorico\CMSBundle\Repository\FooterRepository;
use Cocorico\CoreBundle\Utils\UriHasher;
use Doctrine\ORM\EntityManager;

class FooterManager
{
    protected $em;
    const SECRET = 'CG5DT3FW6R93FZ';

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    /**
     * @param  Footer $footer
     * @return Footer
     */
    public function save(Footer $footer)
    {
        $footer->mergeNewTranslations();
        $this->em->persist($footer);
        $this->em->flush();

        /** @var ContentTranslation $translation */
        foreach ($footer->getTranslations() as $translation) {
            if ($translation->getUrl()) {
                $translation->setUrlHash($this->hashUrl($translation->getUrl()));
            }
            $this->em->persist($translation);
        }

        $this->em->persist($footer);
        $this->em->flush();

        return $footer;
    }

    /**
     * @param $url
     * @return string
     */
    private function hashUrl($url)
    {
        $uriHasher = new UriHasher(self::SECRET);

        //Remove these fields from search query string to display seo content independently of these fields
        $qsFieldsToRemove = [
            '["location"]["address"]',
            '["location"]["addressType"]',
            '["location"]["viewport"]',
            '["location"]["area"]',
            '["location"]["department"]',
            '["location"]["city"]',
            '["location"]["zip"]',
            '["location"]["route"]',
            '["location"]["streetNumber"]',
            '["date_range"]',
            '["time_range"]',
            '["price_range"]',
            '["characteristics"]',
            '["sort_by"]'
        ];

        return $uriHasher->hash($url, true, $qsFieldsToRemove);
    }

    /**
     *
     * @return FooterRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('CocoricoCMSBundle:Footer');
    }
}

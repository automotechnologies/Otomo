<?php

namespace Cocorico\GeoBundle\Repository;

use Cocorico\GeoBundle\Entity\Area;
use Cocorico\GeoBundle\Entity\Department;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * DepartmentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DepartmentRepository extends EntityRepository
{
    /**
     * @param string $name
     * @param Area   $area
     * @return Department|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByNameAndArea($name, $area)
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->addSelect("dt, dg")
            ->leftJoin('d.translations', 'dt')
            ->leftJoin('d.geocoding', 'dg')
            ->where('dt.name = :name')
            ->andWhere('d.area = :area')
            ->setParameter('name', $name)
            ->setParameter('area', $area);
        try {
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return array|null
     */
    public function findAllDepartments()
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->addSelect("c, dg, dt")
            ->leftJoin('d.country', 'c')
            ->leftJoin('d.translations', 'dt')
            ->leftJoin('d.geocoding', 'dg')
            ->orderBy('dt.name');
        try {
            $query = $queryBuilder->getQuery();

            return $query->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (NoResultException $e) {
            return null;
        }
    }
}

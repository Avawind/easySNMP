<?php

namespace USMB\SNMPBundle\Repository;

/**
 * DeviceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DeviceRepository extends \Doctrine\ORM\EntityRepository
{


    /**
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNbHost() {
         $query = $this->createQueryBuilder('l');
         $query->select('COUNT(l)');

        return $query->getQuery()->getSingleScalarResult();
    }


}
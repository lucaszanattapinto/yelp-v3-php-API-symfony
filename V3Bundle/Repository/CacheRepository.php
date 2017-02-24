<?php

namespace Yelp\V3Bundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Yelp\V3Bundle\Entity\Cache;

/**
 * CacheRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CacheRepository extends EntityRepository
{
    /**
     * @param array $searchCriteria
     * @param array $businesses
     * @param ObjectManager $em
     */
    public function save($searchCriteria, $businesses, $em)
    {
        $cache = new Cache();
        $cache->setSearchCriteria($searchCriteria);
        $cache->setResponse($businesses);
        $em->persist($cache);
        $em->flush();
    }
}

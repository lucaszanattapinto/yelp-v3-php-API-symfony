<?php

namespace Yelp\V3Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cache
 *
 * @ORM\Table(name="YelpCache")
 * @ORM\Entity(repositoryClass="Yelp\V3Bundle\Repository\CacheRepository")
 */
class Cache
{
    /**
     * @var string
     *
     * @ORM\Column(name="search_criteria", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $searchCriteria;

    /**
     * @var string
     *
     * @ORM\Column(name="response", type="json_array")
     */
    private $response;


    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * @param string $searchCriteria
     */
    public function setSearchCriteria($searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
    }


}

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
     * @ORM\Column(name="search_keyword", type="string", length=255)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $searchKeyword;

    /**
     * @var string
     *
     * @ORM\Column(name="response", type="json_array")
     */
    private $response;

    /**
     * @return string
     */
    public function getSearchKeyword()
    {
        return $this->searchKeyword;
    }

    /**
     * @param string $searchKeyword
     */
    public function setSearchKeyword($searchKeyword)
    {
        $this->searchKeyword = $searchKeyword;
    }

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


}

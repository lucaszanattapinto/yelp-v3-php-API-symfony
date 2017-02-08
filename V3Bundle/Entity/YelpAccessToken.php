<?php

namespace Yelp\V3Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * YelpAccessToken
 *
 * @ORM\Table(name="YelpAccessToken")
 * @ORM\Entity
 */
class YelpAccessToken
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created = 'CURRENT_TIMESTAMP';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired", type="datetime", nullable=false)
     */
    private $expired = '0000-00-00 00:00:00';

    /**
     * @var string
     *
     * @ORM\Column(name="access_token", type="string", length=255)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $accessToken;



    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return YelpAccessToken
     */
    public function setCreated($created)
    {
        $this->created = new \DateTime();

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set expired
     *
     * @param \DateTime $expired
     *
     * @return YelpAccessToken
     */
    public function setExpired($expired)
    {
        $this->expired = $expired;

        return $this;
    }

    /**
     * Get expired
     *
     * @return \DateTime
     */
    public function getExpired()
    {
        return $this->expired;
    }

    /**
     * Get accessToken
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
    
    /**
     * Get accessToken
     *
     * @return string
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
    
}

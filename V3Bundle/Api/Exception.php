<?php namespace Yelp\V3Bundle\Api;

use \Exception as BaseException;

class Exception extends BaseException
{
    /**
     * Response body
     *
     * @var string
     */
    protected $responseBody;

    /**
     * Set exception response body from Http request
     *
     * @param string $body
     *
     * @return  BaseException
     */
    public function setResponseBody($body = null)
    {
        $this->responseBody = $body;

        return $this;
    }

    /**
     * Get exception response body
     *
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}

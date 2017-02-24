<?php

namespace Yelp\V3Bundle\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Doctrine\ORM\EntityManager;
use Yelp\V3Bundle\Entity\YelpAccessToken;

class Api
{
    /**
     * API host url
     *
     * @var string
     */
    protected $apiHost;

    /**
     * Consumer key
     *
     * @var string
     */
    protected $consumerKey;

    /**
     * Consumer secret
     *
     * @var string
     */
    protected $consumerSecret;

    /**
     * Access token
     *
     * @var string
     */
    protected static $access_token;

    /**
     * Default search term
     *
     * @var string
     */
    protected $defaultTerm = 'business';

    /**
     * Default location
     *
     * @var string
     */
    protected $defaultLocation = 'USA';

    /**
     * Default search limit
     *
     * @var integer
     */
    protected $searchLimit = 10;

    /**
     * Search path
     *
     * @var string
     */
    protected $searchPath = '/v3/businesses/search';

    /**
     * Business path
     *
     * @var string
     */
    protected $businessPath = '/v3/businesses/%s';

    /**
     * Phone search path
     *
     * @var string
     */
    protected $phoneSearchPath = '/v3/businesses/search/phone';
    
    /**
     * Reviews path
     * 
     * @var string
     */
    protected $reviewsPath = '/v3/businesses/%s/reviews';

    /**
     * Transactions path
     * 
     * @var string
     */
    protected $transactionsPath = '/v3/transactions/%s/search';
    
    /**
     * Autocomplete path
     * 
     * @var string
     */    
    protected $autocompletePath = '/v3/autocomplete';
        
    /**
     * Oauth2 token path
     * 
     * @var string
     */
    protected $oauth2TokenPath = '/oauth2/token';       
    
    /**
     * [$httpClient description]
     *
     * @var Client
     */
    protected $httpClient;

    /**
     * Doctrine entity manager
     * 
     * @var EntityManager
     */
    protected $em;
    
    /**
     * Create new client
     *
     * @param array $configuration
     */
    public function __construct($em, $configuration)
    {
        $this->em = $em;
        
        $this->parseConfiguration($configuration);
        $this->createHttpClient();
        $this->setAccessToken();
    }

    /**
     * Set access token from db (if exists not expired one) or from API
     * (in this case also add new token to db)
     * 
     * @return \Yelp\V3Bundle\Api\Api
     */
    protected function setAccessToken()
    {
        // try to get access token from db
        $repository = $this->em->getRepository('YelpV3Bundle:YelpAccessToken');

        // createQueryBuilder automatically selects FROM 
        $query = $repository->createQueryBuilder('yat')
            ->where('yat.expired > :curTime')
            ->setParameter('curTime', date('Y-m-d H:i:s',time()))
            ->getQuery();

        $res = $query->setMaxResults(1)->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        
        if(isset($res['accessToken']))
        {
            // token exists, let's init it
            self::$access_token = $res['accessToken'];
        }
        elseif($this->getConsumerKey() && $this->getConsumerSecret())
        {
            // if there is no token, set new access token (retreive it from Auth url)
            $this->setNewAccessTokenFromAuth();
        }
                                        
        return $this;
    }
    
    protected function setNewAccessTokenFromAuth()
    {
        // try to get access token from API
        $request = $this->httpClient->createRequest(
            'POST',
            'https://' . $this->apiHost . $this->oauth2TokenPath,
            [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body'   => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->consumerKey,
                    'client_secret' => $this->consumerSecret,
                ]
            ]
        );

        $response = $this->httpClient->send($request)->json();

        if(isset($response['access_token']))
        {
            // init by new token
            self::$access_token = $response['access_token'];

            // clear table with old token
            $q = $this->em->createQuery('DELETE FROM YelpV3Bundle:YelpAccessToken');
            $q->execute();
                        
            $expiredDate = new \DateTime();
            $expiredDate->setTimestamp(time() + $response['expires_in']);
            
            // add new token to db instead of old one            
            $newAccTokenObj = new YelpAccessToken();
            $newAccTokenObj->setExpired($expiredDate);
            $newAccTokenObj->setCreated(new \DateTime());            
            $newAccTokenObj->setAccessToken($response['access_token']);

            // tells Doctrine you want to (eventually) save 
            $this->em->persist($newAccTokenObj);

            // actually executes the queries (i.e. the INSERT query)
            $this->em->flush();            
        }
                
        return $this;
    }

    /**
     * @return string
     */
    public static function getAccessToken()
    {
        return self::$access_token;
    }

    /**
     * Get autocomplete suggestions
     * 
     * @param array $attributes
     * 
     * @return array
     */
    public function getAutocompleteSuggestions($attributes = [])
    {
        $path = $this->autocompletePath . "?" . $this->prepareQueryParams($attributes);
        
        return $this->request($path);
    }
    
    /**
     * Get reviews for business by ID string
     * 
     * @param str $businessId
     * 
     * @return array
     */
    public function getReviews($businessId)
    {
        $path = sprintf($this->reviewsPath, urlencode($businessId));

        return $this->request($path);
    }    
    
    /**
     * Get transactions - food delivery in the US
     * 
     * @param array $attributes
     * 
     * @return array
     */
    public function getTransactions($attributes = [])
    {        
        // default transaction type
        $transactionType = 'delivery';
        
        if(isset($attributes['transaction_type']))
        {
            $transactionType = $attributes['transaction_type'];
            unset($attributes['transaction_type']);
        }
        
        $path = $this->prepareQueryParams($attributes);
                
        $path = sprintf($this->transactionsPath, urlencode($transactionType)) . "?" . $path;

        return $this->request($path);
    }               
    
    /**
     * Build query string params using defaults for search() functionality
     *
     * @param  array $attributes
     *
     * @return string
     */
    public function buildQueryParamsForSearch($attributes = [])
    {
        $defaults = array(
            'term' => $this->defaultTerm,
            'location' => $this->defaultLocation,
            'limit' => $this->searchLimit,
        );
        
        $attributes = array_merge($defaults, $attributes);

        return $this->prepareQueryParams($attributes);
    }

    /**
     * Build unsigned url
     *
     * @param  string   $host
     * @param  string   $path
     *
     * @return string   Unsigned url
     */
    protected function buildUnsignedUrl($host, $path)
    {
        return "https://" . $host . $path;
    }

    /**
     * Builds and sets a preferred http client.
     *
     * @return Client
     */
    protected function createHttpClient()
    {
        $client = new HttpClient();

        return $this->setHttpClient($client);
    }

    /**
     * Query the Business API by business id
     *
     * @param    string   $businessId      The ID of the business to query
     *
     * @return   stdClass                   The JSON response from the request
     */
    public function getBusiness($businessId)
    {
        $businessPath = sprintf($this->businessPath, urlencode($businessId));

        return $this->request($businessPath);
    }
    
    /**
     * Maps legacy configuration keys to updated keys.
     *
     * @param  array   $configuration
     *
     * @return array
     */
    protected function mapConfiguration(array $configuration)
    {
        array_walk($configuration, function ($value, $key) use (&$configuration) {
            $newKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $configuration[$newKey] = $value;
        });

        return $configuration;
    }

    /**
     * Parse configuration using defaults
     *
     * @param  array $configuration
     *
     * @return client
     */
    protected function parseConfiguration($configuration = [])
    {
        $defaults = array(
            'consumerKey' => null,
            'consumerSecret' => null,
            'apiHost' => 'api.yelp.com'
        );

        $configuration = array_merge($defaults, $this->mapConfiguration($configuration));

        array_walk($configuration, [$this, 'setConfig']);

        return $this;
    }

    /**
     * Updates query params array to apply yelp specific formatting rules.
     *
     * @param  array   $params
     *
     * @return string
     */
    protected function prepareQueryParams($params = [])
    {
        array_walk($params, function ($value, $key) use (&$params) {
            if (is_bool($value)) {
                $params[$key] = $value ? 'true' : 'false';
            }
        });

        return http_build_query($params);
    }

    /**
     * Makes a request to the Yelp API and returns the response
     *
     * @param    string $path The path of the APi after the domain
     * @return stdClass The JSON response from the request
     * @throws \Exception
     */
    protected function request($path)
    {
        $url = $this->buildUnsignedUrl($this->apiHost, $path);

        try 
        {
            $request = $this->httpClient->createRequest(
                'get', 
                $url,
                [                    
                    'headers' => ['Authorization' => "Bearer ".self::$access_token],
                ]
            );            
        }
        catch (ClientException $e) 
        {
            $exception = new Exception($e->getMessage());

            throw $exception->setResponseBody($e->getResponse()->getBody());
        }

        $response = $this->httpClient->send($request);

        return $response->json();
    }

    /**
     * Query the Search API by a search term and location
     *
     * @param    array    $attributes   Query attributes
     *
     * @return   array               The JSON response from the request
     */
    public function search($attributes = [])
    {
        $query_string = $this->buildQueryParamsForSearch($attributes);
        $searchPath = $this->searchPath . "?" . $query_string;

        return $this->request($searchPath);
    }

    /**
     * Search for businesses by phone number
     *
     * @param    str    phone
     *
     * @return   array               The JSON response from the request
     */
    public function searchByPhone($phone)
    {
        $path = $this->phoneSearchPath . "?" . $this->prepareQueryParams(['phone' => $phone]);

        return $this->request($path);
    }

    /**
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * Attempts to set a given value.
     *
     * @param mixed   $value
     * @param string  $key
     *
     * @return Client
     */
    protected function setConfig($value, $key)
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Set default location
     *
     * @param string $location
     *
     * @return Client
     */
    public function setDefaultLocation($location)
    {
        $this->defaultLocation = $location;
        return $this;
    }

    /**
     * Set default term
     *
     * @param string $term
     *
     * @return Client
     */
    public function setDefaultTerm($term)
    {
        $this->defaultTerm = $term;
        return $this;
    }

    /**
     * Updates the yelp client's http client to the given http client. Client.
     *
     * @param HttpClient  $client
     *
     * @return  Client
     */
    public function setHttpClient(HttpClient $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    public function setHttpClientVerify($isSecure)
    {
        $this->httpClient->setDefaultOption('verify', $isSecure);

        return $this;
    }

    /**
     * Set search limit
     *
     * @param integer $limit
     *
     * @return Client
     */
    public function setSearchLimit($limit)
    {
        if (is_int($limit)) {
            $this->searchLimit = $limit;
        }
        return $this;
    }

    /**
     * Retrives the value of a given property from the client.
     *
     * @param  string  $property
     *
     * @return mixed|null
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return null;
    }
}

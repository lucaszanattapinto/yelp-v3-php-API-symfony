<?php
/**
 * Created by PhpStorm.
 * User: lucas.zanatta
 * Date: 09/02/2017
 * Time: 14:55
 */

namespace Yelp\V3Bundle\Helper;


use ArcaSolutions\ListingBundle\Entity\Listing;
use ArcaSolutions\ListingBundle\ListingBundle;
use Symfony\Component\DependencyInjection\Container;
use ArcaSolutions\CoreBundle\Entity\Location1;
use ArcaSolutions\CoreBundle\Entity\Location2;
use ArcaSolutions\CoreBundle\Entity\Location3;
use ArcaSolutions\CoreBundle\Entity\Location4;
use ArcaSolutions\CoreBundle\Entity\Location5;

class ApiHelper
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $item
     * @return array
     * @throws \Throwable
     */
    public function retrieveSearchParameters(array $item)
    {
        $keyword = ['term' => $item['title']];
        if ($item['geoLocation']['lat'] && $item['geoLocation']['lon']) {
            $location = [
                'latitude' => $item['geoLocation']['lat'],
                'longitude' => $item['geoLocation']['lon']
            ];
        } elseif ($item['locationId']) {
            $locationsObj = $this->container->get('helper.location')->convertElasticStringToObjects($item['locationId']);
            $locationsName = [];
            foreach ($locationsObj as $location) {
                /**
                 * @var Location1|Location2|Location3|Location4|Location5 $location
                 */
                $locationsName[] = $location->getName();
            }
            $location = ['location' => implode(', ', $locationsName)];
        } else {
            $location = ['location' => 'USA'];
        }

        return array_merge($keyword, $location);
    }

    public function retrieveSearchParametersGivenObject(Listing $item)
    {
        $keyword = ['term' => $item->getTitle()];
        if ($item->getLatitude() && $item->getLongitude()) {
            $location = [
                'latitude' => $item->getLatitude(),
                'longitude' => $item->getLongitude()
            ];
        } elseif ($locations = $this->container->get('location.service')->getLocations($item)) {
            foreach ($locations as $level => $location) {
                /**
                 * @var Location1|Location2|Location3|Location4|Location5 $location
                 */
                $locationsName[] = $location->getName();
            }
            $location = ['location' => implode(', ', $locationsName)];
        } else {
            $location = ['location' => 'USA'];
        }

        return array_merge($keyword, $location);
    }

}

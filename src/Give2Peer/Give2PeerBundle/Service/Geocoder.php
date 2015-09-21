<?php

namespace Give2Peer\Give2PeerBundle\Service;

use Geocoder\Geocoder as BaseGeocoder;
use Geocoder\HttpAdapter\BuzzHttpAdapter;
use Geocoder\Provider\ChainProvider;
use Geocoder\Provider\FreeGeoIpProvider;
use Geocoder\Provider\GoogleMapsProvider;
use Geocoder\Provider\HostIpProvider;
use Geocoder\Provider\OpenStreetMapProvider;
use Geocoder\Result\Geocoded;
use Give2Peer\Give2PeerBundle\Provider\LatitudeLongitudeProvider;

class Geocoder
{
    /** @var BaseGeocoder */
    protected $geocoder;

    function __construct($locale = 'fr_FR', $region = 'France')
    {
        $adapter  = new BuzzHttpAdapter();
        $geocoder = new BaseGeocoder();
        $chain    = new ChainProvider(array(
            new LatitudeLongitudeProvider($adapter),
            new FreeGeoIpProvider($adapter),
            new HostIpProvider($adapter),
            new OpenStreetMapProvider($adapter, $locale),
            new GoogleMapsProvider($adapter, $locale, $region, true),
        ));
        $geocoder->registerProvider($chain);

        $this->geocoder = $geocoder;
    }

    public function getCoordinates($location)
    {
        /** @var Geocoded $geocode */
        $geocode = $this->geocoder->geocode($location);
        return $geocode->getCoordinates();
    }
}
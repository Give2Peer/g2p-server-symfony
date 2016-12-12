<?php

namespace Give2Peer\Give2PeerBundle\Provider;

use Geocoder\Exception\InvalidCredentialsException;
use Geocoder\Exception\NoResultException;
use Geocoder\Exception\UnsupportedException;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\ProviderInterface;

class LatitudeLongitudeProvider extends AbstractProvider
{
    protected $maxResults;

    /**
     * Returns an associative array with data treated by the provider.
     *
     * @param string $latlon A latitude/longitude pair, eg: "41.2/-2.5"
     * @throws NoResultException If the latlon could not be resolved
     * @return array
     */
    public function getGeocodedData($latlon)
    {
        if (0 === $this->maxResults) return [];

        $m = [];
        $regex = '!^\s*(-?[0-9]*[.,]?[0-9]*)\s*[/,|]\s*(-?[0-9]*[.,]?[0-9]*)\s*$!';
        if (! preg_match($regex, $latlon, $m)) {
            throw new NoResultException();
        }
        if ('' == $m[0] || '' == $m[1]) {
            throw new NoResultException();
        }
        $result = array_merge($this->getDefaults(), array(
            'latitude'     => floatval($m[1]),
            'longitude'    => floatval($m[2]),
        ));

        return [$result];
    }

    /**
     * Returns an associative array with data treated by the provider.
     *
     * @param array $coordinates Coordinates (latitude, longitude).
     *
     * @throws NoResultException           If the coordinates could not be resolved
     * @throws InvalidCredentialsException If the credentials are invalid
     * @throws UnsupportedException        If reverse geocoding is not supported
     *
     * @return array
     */
    public function getReversedData(array $coordinates)
    {
        return $this->getGeocodedData(
            sprintf("%F/%F", $coordinates[0], $coordinates[1])
        );
    }

    /**
     * Returns the provider's name.
     *
     * @return string
     */
    public function getName()
    {
        return "LatitudeLongitudeProvider";
    }

    /**
     * Sets the maximum number of returned results.
     *
     * @param integer $maxResults
     * @return LatitudeLongitudeProvider
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;

        return $this;
    }
}
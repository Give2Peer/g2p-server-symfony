<?php

namespace Give2Peer\Give2PeerBundle\Provider;

use Geocoder\Exception\InvalidCredentialsException;
use Geocoder\Exception\NoResultException;
use Geocoder\Exception\UnsupportedException;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\ProviderInterface;

class LatitudeLongitudeProvider extends AbstractProvider
{
    /**
     * Returns an associative array with data treated by the provider.
     *
     * @param string $address An address (IP or street).
     *
     * @throws NoResultException           If the address could not be resolved
     * @throws InvalidCredentialsException If the credentials are invalid
     * @throws UnsupportedException        If not supported
     *
     * @return array
     */
    public function getGeocodedData($address)
    {
        $m = [];
        $regex = '!^\s*(-?[0-9]*\.?[0-9]*)\s*[/,|]\s*(-?[0-9]*\.?[0-9]*)\s*$!';
        if (! preg_match($regex, $address, $m)) {
            throw new NoResultException();
        }
        $result = array_merge($this->getDefaults(), array(
            'latitude'     => $m[1],
            'longitude'    => $m[2],
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
        return $this->getGeocodedData(sprintf("%F/%F", $coordinates[0], $coordinates[1]));
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
     *
     * @return ProviderInterface
     */
    public function setMaxResults($maxResults)
    {
        // Does nothing, as if we find a result there can be only one.
        // This breaks the functionality of setting $maxResults to 0. Who cares?
        return $this;
    }
}
<?php

/**
 * Fakes latitude and longitude, to provide to the Faker\Generator.
 */
class GeolocationFaker extends \Faker\Provider\Base
{
    public function latitude()
    {
        return rand(-90000, 90000) / 1000;
    }
    public function longitude()
    {
        return rand(-180000, 180000) / 1000;
    }
}
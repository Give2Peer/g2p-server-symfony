<?php

/**
 * Fakes latitude and longitude, to provide to the Faker\Generator.
 * 
 * This is possibly VERY BAD CODE. (or very good code, but VERY BAD COMMENTS ?)
 * Why all the zeroes ? -- ... -- I don't remember ! Numerical stability ?
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
    public function lat()
    {
        return $this->latitude();
    }
    public function lng()
    {
        return $this->longitude();
    }
}
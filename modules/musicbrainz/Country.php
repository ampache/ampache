<?php

namespace MusicBrainz;

class Country
{
    /**
     * @todo Populate rest of the countries
     */
    private static $countries = array(
        'GB' => 'Great Britain',
    );

    /**
     * Get the country name for a MusicBrainz country code
     *
     * @static
     * @param $countryCode
     * @throws \OutOfBoundsException
     * @return bool
     */
    public static function getName($countryCode)
    {
        if (!isset(self::$countries[$countryCode])) {
            throw new \OutOfBoundsException(sprintf("Could not find corresponding country name for the country code %s", $countryCode));
        }

        return self::$countries[$countryCode];
    }
}

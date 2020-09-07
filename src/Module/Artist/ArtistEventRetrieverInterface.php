<?php

namespace Ampache\Module\Artist;

use Ampache\Model\Artist;

interface ArtistEventRetrieverInterface
{
    /**
     * Returns a list of upcoming events
     *
     * @param Artist $artist
     * @return array List of events
     */
    public function getUpcomingEvents(Artist $artist): array;

    /**
     * Returns a list of past events
     *
     * @param Artist $artist
     * @return array List of events
     */
    public function getPastEvents(Artist $artist): array;
}
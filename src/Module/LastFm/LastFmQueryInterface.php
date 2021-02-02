<?php

namespace Ampache\Module\LastFm;

use SimpleXMLElement;

interface LastFmQueryInterface
{
    /**
     * Runs a last.fm query and returns the parsed results
     */
    public function getLastFmResults(string $method, string $query): SimpleXMLElement;

    public function queryLastFm(string $url): SimpleXMLElement;
}

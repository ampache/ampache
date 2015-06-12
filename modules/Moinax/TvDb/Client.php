<?php

namespace Moinax\TvDb;

use Moinax\TvDb\CurlException;

/**
 * Base TVDB library class, provides universal functions and variables
 *
 * @package TvDb
 * @author Jérôme Poskin <moinax@gmail.com>
 **/
class Client
{
    const POST = 'post';
    const GET = 'get';

    const MIRROR_TYPE_XML = 1;
    const MIRROR_TYPE_BANNER = 2;
    const MIRROR_TYPE_ZIP = 4;

    const DEFAULT_LANGUAGE = 'en';

    const FORMAT_XML = 'xml';
    const FORMAT_ZIP = 'zip';

    /**
     * Base url for TheTVDB
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * API key for thetvdb.com
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Array of available mirrors
     *
     * @var array
     */
    protected $mirrors = array();

    /**
     * Array of available languages
     *
     * @var array
     */
    protected $languages = array();

    /**
     * @param string $baseUrl Domain name of the api without trailing slash
     * @param string $apiKey Api key provided by http://thetvdb.com
     */
    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * Get a language information
     *
     * @param string $abbreviation
     * @return array
     * @throws \Exception
     */
    public function getLanguage($abbreviation)
    {
        if (empty($this->languages)) {
            $this->getLanguages();
        }
        if (!isset($this->languages[$abbreviation])) {
            throw new \Exception('This language is not available');
        }

        return $this->languages[$abbreviation];
    }

    /**
     * Get the server time for further updates
     *
     * @return string
     */
    public function getServerTime()
    {
        return (string)$this->fetchXml('Updates.php?type=none')->Time;
    }

    /**
     * Searches for tv serie based on series name
     *
     * @param string $seriesName
     * @param string $language
     * @internal param string $seriesName the show name to search for
     * @return array
     */
    public function getSeries($seriesName, $language = self::DEFAULT_LANGUAGE)
    {
        $data = $this->fetchXml('GetSeries.php?seriesname=' . urlencode($seriesName) . '&language=' . $language);
        $series = array();
        foreach ($data->Series as $serie) {
            $series[] = new Serie($serie);
        }
        return $series;
    }

    /**
     * Find a tv serie by the id from thetvdb.com
     *
     * @var int $serieId
     * @var string $language
     *
     * @return Serie A serie object or false if not found
     **/
    public function getSerie($serieId, $language = self::DEFAULT_LANGUAGE)
    {
        $data = $this->fetchXml('series/' . $serieId . '/' . $language . '.xml');

        return new Serie($data->Series);
    }

    /**
     * Find a tv serie by a remote id
     *
     * @param array $remoteId
     * @param string $language
     *
     * @example <pre><code>$obj->getSerieByRemoteId(array('imdbid' => '<imdbid>'));</code></pre>
     * @example <pre><code>$obj->getSerieByRemoteId(array('zap2it' => '<zap2it>'));</code></pre>
     *
     * @return Serie
     */
    public function getSerieByRemoteId(array $remoteId, $language = self::DEFAULT_LANGUAGE)
    {
        $data = $this->fetchXml('GetSeriesByRemoteID.php?' . http_build_query($remoteId) . '&language=' . $language);

        return new Serie($data->Series);
    }

    /**
     * Find all banners related to a serie
     *
     * @param int $serieId
     * @return string
     */
    public function getBanners($serieId)
    {
        $data = $this->fetchXml('series/' . $serieId . '/banners.xml');
        $banners = array();
        foreach ($data->Banner as $banner) {
            $banners[] = new Banner($banner);
        }

        return $banners;
    }

    /**
     * Find all actors related to a serie
     *
     * @param int $serieId
     * @return array
     */
    public function getActors($serieId)
    {
        $data = $this->fetchXml('series/'. $serieId . '/actors.xml');
        $actors = array();
        foreach ($data->Actor as $actor) {
            $actors [] = new Actor($actor);
        }

        return $actors;
    }

    /**
     * Get all episodes for a serie
     *
     * @param int $serieId
     * @param string $language
     * @param string $format
     * @return array
     * @throws \ErrorException
     */
    public function getSerieEpisodes($serieId, $language = self::DEFAULT_LANGUAGE, $format = self::FORMAT_XML)
    {
        switch ($format) {
            case self::FORMAT_XML:
                $data = $this->fetchXml('series/' . $serieId . '/all/' . $language . '.' . $format);
                break;
            case self::FORMAT_ZIP:
            default:
                throw new \ErrorException('Unsupported format');
                break;
        }
        $serie = new Serie($data->Series);
        $episodes = array();
        foreach ($data->Episode as $episode) {
            $episodes[(int)$episode->id] = new Episode($episode);
        }
        return array('serie' => $serie, 'episodes' => $episodes);
    }

    /**
     * Get a specific episode by season and episode number
     *
     * @var int $serieId required the id of the serie
     * @var int $season required the season number
     * @var int $episode required the episode number
     * @var string $language language abbreviation
     *
     * @return Episode
     **/
    public function getEpisode($serieId, $season, $episode, $language = self::DEFAULT_LANGUAGE)
    {
        $data = $this->fetchXml('series/' . $serieId . '/default/' . $season . '/' . $episode . '/' . $language . '.xml');

        return new Episode($data->Episode);
    }

    /**
     * Get a specific episode by his id
     *
     * @var int $episodeId required the id of the episode
     * @var string $language
     * @return Episode
     **/
    public function getEpisodeById($episodeId, $language = self::DEFAULT_LANGUAGE)
    {
        $data = $this->fetchXml('episodes/' . $episodeId . '/' . $language . '.xml');

        return new Episode($data->Episode);
    }

    /**
     * Get updates list based on previous time you got data
     *
     * @param int $previousTime
     * @return array
     */
    public function getUpdates($previousTime)
    {
        $data = $this->fetchXml('Updates.php?type=all&time=' . $previousTime);

        $series = array();
        foreach ($data->Series as $serieId) {
            $series[] = (int)$serieId;
        }
        $episodes = array();
        foreach ($data->Episode as $episodeId) {
            $episodes[] = (int)$episodeId;
        }
        return array('series' => $series, 'episodes' => $episodes);
    }


    /**
     * Fetches data via curl and returns result
     *
     * @access protected
     * @param string $function The function used to fetch data in xml
     * @param array $params
     * @param string $method
     * @return string The data
     */
    protected function fetchXml($function, $params = array(), $method = self::GET)
    {
        if (strpos($function, '.php') > 0) { // no need of api key for php calls
            $url = $this->getMirror(self::MIRROR_TYPE_XML) . '/api/' . $function;
        } else {
            $url = $this->getMirror(self::MIRROR_TYPE_XML) . '/api/' . $this->apiKey . '/' . $function;
        }

        $data = $this->fetch($url, $params, $method);

        $simpleXml = $this->getXml($data);

        return $simpleXml;
    }

    /**
     * Fetch data with curl
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @throws CurlException
     * @return bool|string
     */
    protected function fetch($url, array $params = array(), $method = self::GET)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($method == self::POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $data = substr($response, $headerSize);
        curl_close($ch);

        if ($httpCode != 200) {
            throw new CurlException(sprintf('Cannot fetch %s', $url), $httpCode);
        }

        return $data;

    }

    /**
     * Convert xml string to SimpleXMLElement
     *
     * @param string $data
     * @return \SimpleXMLElement
     * @throws \ErrorException|\Exception
     */
    protected function getXml($data)
    {
        if (extension_loaded('libxml')) {
            libxml_use_internal_errors(true);
        }

        $simpleXml = simplexml_load_string($data);
        if (!$simpleXml) {
            if (extension_loaded('libxml')) {
                $xmlErrors = libxml_get_errors();
                $errors = array();
                foreach ($xmlErrors as $error) {
                    $errors[] = sprintf('Error in file %s on line %d with message : %s', $error->file, $error->line, $error->message);
                }
                if (count($errors) > 0) {

                    throw new XmlException(implode("\n", $errors));
                }
            }
            throw new XmlException('Xml file cound not be loaded');
        }

        return $simpleXml;
    }

    /**
     * Get a list of mirrors available to fetchXml the data from the api
     * @return void
     */
    protected function getMirrors()
    {
        $data = $this->fetch($this->baseUrl . '/api/' . $this->apiKey . '/mirrors.xml');
        $mirrors = $this->getXml($data);

        foreach ($mirrors->Mirror as $mirror) {
            $typeMask = (int)$mirror->typemask;
            $mirrorPath = (string)$mirror->mirrorpath;

            if ($typeMask & self::MIRROR_TYPE_XML) {
                $this->mirrors[self::MIRROR_TYPE_XML][] = $mirrorPath;
            }
            if ($typeMask & self::MIRROR_TYPE_BANNER) {
                $this->mirrors[self::MIRROR_TYPE_BANNER][] = $mirrorPath;
            }
            if ($typeMask & self::MIRROR_TYPE_ZIP) {
                $this->mirrors[self::MIRROR_TYPE_ZIP][] = $mirrorPath;
            }
        }
    }

    /**
     * Get a random mirror from the list of available mirrors
     *
     * @param int $typeMask
     * @return string
     * @access protected
     */
    public function getMirror($typeMask = self::MIRROR_TYPE_XML)
    {
        if (empty($this->mirrors)) {
            $this->getMirrors();
        }
        return $this->mirrors[$typeMask][array_rand($this->mirrors[$typeMask], 1)];

    }

    /**
     * Get a list of languages available for the content of the api
     * @return \SimpleXMLElement
     */
    protected function getLanguages()
    {
        $languages = $this->fetchXml('languages.xml');

        foreach ($languages->Language as $language) {
            $this->languages[(string)$language->abbreviation] = array(
                'name' => (string)$language->name,
                'abbreviation' => (string)$language->abbreviation,
                'id' => (int)$language->id,
            );
        }
    }

    /**
     * Removes indexes from an array if they are zero length after trimming
     *
     * @param array $array The array to remove empty indexes from
     * @return array An array with all empty indexes removed
     **/
    public static function removeEmptyIndexes($array)
    {

        $length = count($array);

        for ($i = $length - 1; $i >= 0; $i--) {
            if (trim($array[$i]) == '') {
                unset($array[$i]);
            }
        }

        sort($array);
        return $array;
    }
}
<?php

namespace MusicBrainz;

use MusicBrainz\Clients\MbClient;

/**
 * Connect to the MusicBrainz web service
 *
 * http://musicbrainz.org/doc/Development
 *
 * @link http://github.com/mikealmond/musicbrainz
 */
class MusicBrainz
{
    private static $validIncludes = array(
        'artist'=> array(
            "recordings",
            "releases",
            "release-groups",
            "works",
            "various-artists",
            "discids",
            "media",
            "aliases",
            "tags",
            "user-tags",
            "ratings",
            "user-ratings", # misc
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "annotation"
        ),
        'annotation'=> array(

        ),
        'label'=> array(
            "releases",
            "discids",
            "media",
            "aliases",
            "tags",
            "user-tags",
            "ratings",
            "user-ratings", # misc
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "annotation"
        ),
        'recording'=> array(
            "artists",
            "releases", # Subqueries
            "discids",
            "media",
            "artist-credits",
            "tags",
            "user-tags",
            "ratings",
            "user-ratings", # misc
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "annotation",
            "aliases"
        ),
        'release'=> array(
            "artists",
            "labels",
            "recordings",
            "release-groups",
            "media",
            "artist-credits",
            "discids",
            "puids",
            "echoprints",
            "isrcs",
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "recording-level-rels",
            "work-level-rels",
            "annotation",
            "aliases"
        ),
        'release-group'=> array(
            "artists",
            "releases",
            "discids",
            "media",
            "artist-credits",
            "tags",
            "user-tags",
            "ratings",
            "user-ratings", # misc
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "annotation",
            "aliases"
        ),
        'work'=> array(
            "artists", # Subqueries
            "aliases",
            "tags",
            "user-tags",
            "ratings",
            "user-ratings", # misc
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "annotation"
        ),
        'discid'=> array(
            "artists",
            "labels",
            "recordings",
            "release-groups",
            "media",
            "artist-credits",
            "discids",
            "puids",
            "echoprints",
            "isrcs",
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels",
            "recording-level-rels",
            "work-level-rels"
        ),
        'echoprint'=> array(
            "artists",
            "releases"
        ),
        'puid'=> array(
            "artists",
            "releases",
            "puids",
            "echoprints",
            "isrcs"
        ),
        'isrc'=> array(
            "artists",
            "releases",
            "puids",
            "echoprints",
            "isrcs"
        ),
        'iswc'=> array(
            "artists"
        ),
        'collection'=> array(
            'releases'
        )
    );

    private static $validBrowseIncludes = array(
        'release'=> array(
            "artist-credits",
            "labels",
            "recordings",
            "release-groups",
            "media",
            "discids",
            "artist-rels",
            "label-rels",
            "recording-rels",
            "release-rels",
            "release-group-rels",
            "url-rels",
            "work-rels"
        ),
        'recording'=> array(
            "artist-credits",
            "tags",
            "ratings",
            "user-tags",
            "user-ratings"
        ),
        'label'=> array(
            "aliases",
            "tags",
            "ratings",
            "user-tags",
            "user-ratings"
        ),
        'artist'=> array(
            "aliases",
            "tags",
            "ratings",
            "user-tags",
            "user-ratings"
        ),
        'release-group'=> array(
            "artist-credits",
            "tags",
            "ratings",
            "user-tags",
            "user-ratings"
        )
    );
    private static $validReleaseTypes = array(
        "nat",
        "album",
        "single",
        "ep",
        "compilation",
        "soundtrack",
        "spokenword",
        "interview",
        "audiobook",
        "live",
        "remix",
        "other"
    );

    private static $validReleaseStatuses = array(
        "official",
        "promotion",
        "bootleg",
        "pseudo-release"
    );

    private $userAgent = 'MusicBrainz PHP Api/0.1.0';
    private $userAgentClient = 'MusicBrainz PHP Api-0.1.0';

    /**
     * The username a MusicBrainz user. Used for authentication.
     *
     * @var string
     */
    private $user = null;

    /**
     * The password of a MusicBrainz user. Used for authentication.
     *
     * @var string
     */
    private $password = null;

    /**
     * The client used to make requests
     *
     * @var \MusicBrainz\Clients\MbClient
     */
    private $client;

    /**
     * Initializes the class. You can pass the user’s username and password
     * However, you can modify or add all values later.
     *
     * @param \MusicBrainz\Clients\MbClient $client   The client used to make requests
     * @param string                       $user
     * @param string                       $password
     */
    public function __construct(MbClient $client, $user = null, $password = null)
    {
        $this->client = $client;

        if (null != $user) {
            $this->setUser($user);
        }

        if (null != $password) {
            $this->setPassword($password);
        }

    }

    /**
     * Do a MusicBrainz lookup
     *
     * http://musicbrainz.org/doc/XML_Web_Service
     *
     * @param $entity
     * @param $mbid Music Brainz ID
     * @param  array  $inc
     * @return object | bool
     */
    public function lookup($entity, $mbid, array $includes = array())
    {

        if (!$this->isValidEntity($entity)) {
            throw new Exception('Invalid entity');
        }

        $this->validateInclude($includes, self::$validIncludes[$entity]);

        $authRequired = $this->isAuthRequired($entity, $includes);

        $params = array(
            'inc' => implode('+', $includes),
            'fmt' => 'json'
        );

        $response = $this->client->call($entity . '/' . $mbid, $params, $this->get_call_options(), $authRequired);

        return $response;
    }

    protected function browse(Filters\FilterInterface $filter, $entity, $mbid, array $includes, $limit = 25, $offset = null, $releaseType = array(), $releaseStatus = array())
    {
        if (!$this->isValidMBID($mbid)) {
            throw new Exception('Invalid Music Brainz ID');
        }

        if ($limit > 100) {
            throw new Exception('Limit can only be between 1 and 100');
        }

        $this->validateInclude($includes, self::$validBrowseIncludes[$filter->getEntity()]);

        $authRequired = $this->isAuthRequired($filter->getEntity(), $includes);

        $params  = $this->getBrowseFilterParams($filter->getEntity(), $includes, $releaseType, $releaseStatus);
        $params += array(
            $entity  => $mbid,
            'inc'    => implode('+', $includes),
            'limit'  => $limit,
            'offset' => $offset,
            'fmt'    => 'json'
        );

        $response = $this->client->call($filter->getEntity() . '/', $params, $this->get_call_options(), $authRequired);

        return $response;
    }

    public function browseArtist($entity, $mbid, array $includes = array(), $limit = 25, $offset = null)
    {
        if (!in_array($entity, array('recording', 'release', 'release-group'))) {
            throw new Exception('Invalid browse entity for artist');
        }

        return $this->browse(new Filters\ArtistFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseLabel($entity, $mbid, array $includes, $limit = 25, $offset = null)
    {
        if (!in_array($entity, array('release'))) {
            throw new Exception('Invalid browse entity for label');
        }

        return $this->browse(new Filters\LabelFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseRecording($entity, $mbid, array $includes = array(), $limit = 25, $offset = null)
    {
        if (!in_array($entity, array('artist', 'release'))) {
            throw new Exception('Invalid browse entity for recording');
        }

        return $this->browse(new Filters\RecordingFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseRelease($entity, $mbid, array $includes = array(), $limit = 25, $offset = null, $releaseType = array(), $releaseStatus = array())
    {
        if (!in_array($entity, array('artist', 'label', 'recording', 'release-group'))) {
            throw new Exception('Invalid browse entity for release');
        }

        return $this->browse(new Filters\ReleaseFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseReleaseGroup($entity, $mbid, $limit = 25, $offset = null, array $includes, $releaseType = array())
    {
        if (!in_array($entity, array('arist', 'release'))) {
            throw new Exception('Invalid browse entity for release group');
        }

        return $this->browse(new Filters\ReleaseGroupFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    /**
     * Performs a query based on the parameters supplied in the Filter object.
     * Returns an array of possible matches with scores, as returned by the
     * musicBrainz web service.
     *
     * Note that these types of queries only return some information, and not all the
     * information available about a particular item is available using this type of query.
     * You will need to get the MusicBrainz id (mbid) and perform a lookup with browse
     * to return complete information about a release. This method returns an array of
     * objects that are possible matches.
     *
     * @param  \MusicBrainz\Filters\FilterInterface $trackFilter
     * @return array
     */
    public function search(Filters\FilterInterface $filter, $limit = 25, $offset = null)
    {
        if (count($filter->createParameters()) < 1) {
            throw new Exception('The artist filter object needs at least 1 argument to create a query.');
        }

        if ($limit > 100) {
            throw new Exception('Limit can only be between 1 and 100');
        }

        $params = $filter->createParameters(array('limit' => $limit, 'offset' => $offset, 'fmt' => 'json'));

        $response = $this->client->call($filter->getEntity() . '/', $params, $this->get_call_options());

        return $filter->parseResponse($response);

    }
    
    public function get_call_options()
    {
        $options = array();
        
        $options['method'] = 'GET';
        $options['user-agent'] = $this->getUserAgent();
        $options['user'] = $this->getUser();
        $options['password'] = $this->getPassword();
        
        return $options;
    }

    /**
     * Check that the status or type values are valid. Then, check that
     * the filters can be used with the given includes.
     *
     * @param  string $entity
     * @param  array  $includes
     * @param  array  $releaseType
     * @param  array  $releaseStatus
     * @return array
     */
    public function getBrowseFilterParams($entity, $includes, array $releaseType = array(), array $releaseStatus = array())
    {
        //$this->validateFilter(array($entity), self::$validIncludes);
        $this->validateFilter($releaseStatus, self::$validReleaseStatuses);
        $this->validateFilter($releaseType, self::$validReleaseTypes);

        if (!empty($releaseStatus)
            && !in_array('releases', $includes)) {
            throw new Exception("Can't have a status with no release include");
        }

        if (!empty($releaseType)
            && !in_array('release-groups', $includes)
            && !in_array('releases', $includes)
            && $entity != 'release-group') {
            throw new Exception("Can't have a release type with no release-group include");
        }

        $params = array();

        if (!empty($releaseType)) {
            $params['type'] = implode('|', $releaseType);
        }

        if (!empty($releaseStatus)) {
            $params['status'] = implode('|', $releaseStatus);
        }

        return $params;
    }

    public function isValidMBID($mbid)
    {
        return preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $mbid);
    }

    public function validateInclude($includes, $validIncludes)
    {
        foreach ($includes as $include) {
            if (!in_array($include, $validIncludes)) {
                throw new \OutOfBoundsException(sprintf('%s is not a valid include', $include));
            }
        }

        return true;
    }

    public function validateFilter($values, $valid)
    {
        foreach ($values as $value) {
            if (!in_array($value, $valid)) {
                throw new Exception(sprintf('%s is not a valid filter', $value));
            }
        }

        return true;
    }
    /**
     * Some calls require authentication
     * @return bool
     */
    protected function isAuthRequired($entity, $includes)
    {
        if (in_array('user-tags', $includes) || in_array('user-ratings', $includes)) {
            return true;
        }

        if (substr($entity, 0, strlen('collection')) === 'collection') {
            return true;
        }

        return false;
    }

    /**
     * Check the list of allowed entities
     *
     * @param $entity
     * @return bool
     */
    private function isValidEntity($entity)
    {
        return array_key_exists($entity, self::$validIncludes);
    }

    /**
     * Set the user agent for POST requests (and GET requests for user tags)
     *
     * @param $application The name of the application using this library
     * @param $version The version of the application using this library
     * @param $contactInfo E-mail or website of the application
     * @throws Exception
     */
    public function setUserAgent($application, $version, $contactInfo)
    {
        if (strpos($version, '-') !== false) {
            throw new Exception('User agent: version should not contain a "-" character.');
        }

        $this->userAgent       = $application . '/' . $version . ' (' . $contactInfo . ')';
        $this->userAgentClient = $application . '-' . $version;

    }

    /**
     * Returns the user agent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Sets the MusicBrainz user
     *
     * @param string $email
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Returns the MusicBrainz user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the user’s password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the user’s password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

}

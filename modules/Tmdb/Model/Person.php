<?php
/**
 * This file is part of the Tmdb PHP API created by Michael Roterman.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Tmdb
 * @author Michael Roterman <michael@wtfz.net>
 * @copyright (c) 2013, Michael Roterman
 * @version 0.0.1
 */
namespace Tmdb\Model;

use Tmdb\Model\Collection\CreditsCollection;
use Tmdb\Model\Common\ExternalIds;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Collection\Images;
use Tmdb\Model\Collection\People\PersonInterface;
use Tmdb\Model\Image\ProfileImage;

/**
 * Class Person
 * @package Tmdb\Model
 */
class Person extends AbstractModel implements PersonInterface
{
    /**
     * @var bool
     */
    private $adult;

    /**
     * @var array
     */
    private $alsoKnownAs = array();

    /**
     * @var string
     */
    private $biography;
    /**
     * @var \DateTime
     */
    private $birthday;

    /**
     * @var \DateTime|boolean
     */
    private $deathday;

    /**
     * @var string
     */
    private $homepage;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $placeOfBirth;

    /**
     * @var string
     */
    private $profilePath;

    /**
     * @var ProfileImage
     */
    private $profileImage;

    /**
     * @var Collection\CreditsCollection
     * @deprecated
     */
    protected $credits;

    /**
     * @var CreditsCollection\MovieCredits
     */
    protected $movieCredits;

    /**
     * @var CreditsCollection\TvCredits
     */
    protected $tvCredits;

    /**
     * @var CreditsCollection\CombinedCredits
     */
    protected $combinedCredits;

    /**
     * @var Collection\Images
     */
    protected $images;

    /**
     * @var Common\GenericCollection
     */
    protected $changes;

    /**
     * External Ids
     *
     * @var ExternalIds
     */
    protected $externalIds;

    public static $properties = array(
        'adult',
        'also_known_as',
        'biography',
        'birthday',
        'deathday',
        'homepage',
        'id',
        'name',
        'place_of_birth',
        'profile_path',
    );

    /**
     * Constructor
     *
     * Set all default collections
     */
    public function __construct()
    {
        $this->credits         = new CreditsCollection();
        $this->movieCredits    = new CreditsCollection\MovieCredits();
        $this->tvCredits       = new CreditsCollection\TvCredits();
        $this->combinedCredits = new CreditsCollection\CombinedCredits();
        $this->images          = new Images();
        $this->changes         = new GenericCollection();
        $this->externalIds     = new ExternalIds();
    }

    /**
     * @param  mixed $adult
     * @return $this
     */
    public function setAdult($adult)
    {
        $this->adult = $adult;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAdult()
    {
        return $this->adult;
    }

    /**
     * @param  mixed $alsoKnownAs
     * @return $this
     */
    public function setAlsoKnownAs($alsoKnownAs)
    {
        $this->alsoKnownAs = $alsoKnownAs;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAlsoKnownAs()
    {
        return $this->alsoKnownAs;
    }

    /**
     * @param  mixed $biography
     * @return $this
     */
    public function setBiography($biography)
    {
        $this->biography = $biography;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBiography()
    {
        return $this->biography;
    }

    /**
     * @param  mixed $birthday
     * @return $this
     */
    public function setBirthday($birthday)
    {
        if (!$birthday instanceof \DateTime) {
            $birthday = new \DateTime($birthday);
        }

        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param  mixed $changes
     * @return $this
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param  mixed $credits
     * @return $this
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * @param  mixed $deathday
     * @return $this
     */
    public function setDeathday($deathday)
    {
        if (!$deathday instanceof \DateTime && !empty($deathday)) {
            $deathday = new \DateTime($deathday);
        }

        if (empty($deathday)) {
            $deathday = false;
        }

        $this->deathday = $deathday;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeathday()
    {
        return $this->deathday;
    }

    /**
     * @param  mixed $homepage
     * @return $this
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param  mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  Images $images
     * @return $this
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return Images
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param  mixed $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  mixed $placeOfBirth
     * @return $this
     */
    public function setPlaceOfBirth($placeOfBirth)
    {
        $this->placeOfBirth = $placeOfBirth;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlaceOfBirth()
    {
        return $this->placeOfBirth;
    }

    /**
     * @param  mixed $profilePath
     * @return $this
     */
    public function setProfilePath($profilePath)
    {
        $this->profilePath = $profilePath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProfilePath()
    {
        return $this->profilePath;
    }

    /**
     * @param  ProfileImage $profileImage
     * @return $this
     */
    public function setProfileImage(ProfileImage $profileImage)
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    /**
     * @return ProfileImage
     */
    public function getProfileImage()
    {
        return $this->profileImage;
    }

    /**
     * @param  \Tmdb\Model\Collection\CreditsCollection\CombinedCredits $combinedCredits
     * @return $this
     */
    public function setCombinedCredits($combinedCredits)
    {
        $this->combinedCredits = $combinedCredits;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\CreditsCollection\CombinedCredits
     */
    public function getCombinedCredits()
    {
        return $this->combinedCredits;
    }

    /**
     * @param  \Tmdb\Model\Collection\CreditsCollection\MovieCredits $movieCredits
     * @return $this
     */
    public function setMovieCredits($movieCredits)
    {
        $this->movieCredits = $movieCredits;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\CreditsCollection\MovieCredits
     */
    public function getMovieCredits()
    {
        return $this->movieCredits;
    }

    /**
     * @param  \Tmdb\Model\Collection\CreditsCollection\TvCredits $tvCredits
     * @return $this
     */
    public function setTvCredits($tvCredits)
    {
        $this->tvCredits = $tvCredits;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\CreditsCollection\TvCredits
     */
    public function getTvCredits()
    {
        return $this->tvCredits;
    }

    /**
     * @param  \Tmdb\Model\Common\ExternalIds $externalIds
     * @return $this
     */
    public function setExternalIds($externalIds)
    {
        $this->externalIds = $externalIds;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Common\ExternalIds
     */
    public function getExternalIds()
    {
        return $this->externalIds;
    }
}

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

use Tmdb\Model\Image\LogoImage;

/**
 * Class Company
 * @package Tmdb\Model
 */
class Company extends AbstractModel
{
    private $description;
    private $headquarters;
    private $homepage;
    private $id;
    private $logo;
    private $logoPath;
    private $name;
    private $parentCompany;

    public static $properties = array(
        'description',
        'headquarters',
        'homepage',
        'id',
        'logo_path',
        'name',
        'parent_company'
    );

    /**
     * @param  mixed $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param  mixed $headquarters
     * @return $this
     */
    public function setHeadquarters($headquarters)
    {
        $this->headquarters = $headquarters;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeadquarters()
    {
        return $this->headquarters;
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
     * @param  LogoImage $logo
     * @return $this
     */
    public function setLogoImage(LogoImage $logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return LogoImage
     */
    public function getLogoImage()
    {
        return $this->logo;
    }

    /**
     * @param  mixed $logoPath
     * @return $this
     */
    public function setLogoPath($logoPath)
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogoPath()
    {
        return $this->logoPath;
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
     * @param  mixed $parentCompany
     * @return $this
     */
    public function setParentCompany($parentCompany)
    {
        $this->parentCompany = $parentCompany;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentCompany()
    {
        return $this->parentCompany;
    }
}

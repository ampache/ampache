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
namespace Tmdb\Factory;

use Tmdb\Model\Company;

/**
 * Class CompanyFactory
 * @package Tmdb\Factory
 */
class CompanyFactory extends AbstractFactory
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->imageFactory = new ImageFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data = array())
    {
        $company = new Company();

        if (array_key_exists('logo_path', $data)) {
            $company->setLogoImage($this->getImageFactory()->createFromPath($data['logo_path'], 'logo_path'));
        }

        return $this->hydrate($company, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        return array();
    }

    /**
     * @param  \Tmdb\Factory\ImageFactory $imageFactory
     * @return $this
     */
    public function setImageFactory($imageFactory)
    {
        $this->imageFactory = $imageFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\ImageFactory
     */
    public function getImageFactory()
    {
        return $this->imageFactory;
    }
}

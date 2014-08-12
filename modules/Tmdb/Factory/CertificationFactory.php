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

use Tmdb\Model\Certification;
use Tmdb\Model\Common\GenericCollection;

/**
 * Class CertificationFactory
 * @package Tmdb\Factory
 */
class CertificationFactory extends AbstractFactory
{
    /**
     * @param array $data
     *
     * @return Certification
     */
    public function create(array $data = array())
    {
        return $this->hydrate(new Certification\CountryCertification(), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        if (array_key_exists('certifications', $data)) {
            $data = $data['certifications'];
        }

        $collection = new GenericCollection();

        foreach ($data as $country => $certifications) {
            $certification = new Certification();
            $certification->setCountry($country);

            foreach ($certifications as $countryCertification) {
                $object = $this->create($countryCertification);

                $certification->getCertifications()->add(null, $object);
            }

            $collection->add(null, $certification);
        }

        return $collection;
    }
}

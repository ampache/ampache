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
namespace Tmdb\Model\Common\QueryParameter\Type;

use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Common\QueryParameter\QueryParameterInterface;

/**
 * Class CollectionToCommaSeperatedString
 * @package Tmdb\Model\Common\QueryParameter\Type
 */
abstract class CollectionToCommaSeperatedString extends GenericCollection implements QueryParameterInterface
{
    /**
     * @param array $collection
     */
    public function __construct(array $collection = array())
    {
        $i = 0;

        foreach ($collection as $item) {
            $this->add($i, $item);

            $i++;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return implode(',', $this->data);
    }
}

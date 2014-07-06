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
namespace Tmdb\Model\Collection;

use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Keyword;

/**
 * Class Keywords
 * @package Tmdb\Model\Collection
 */
class Keywords extends GenericCollection
{
    /**
     * Returns all keywords
     *
     * @return array
     */
    public function getKeywords()
    {
        return $this->data;
    }

    /**
     * Retrieve a keyword from the collection
     *
     * @param $id
     * @return Keyword
     */
    public function getKeyword($id)
    {
        return $this->filterId($id);
    }

    /**
     * Add a keyword to the collection
     *
     * @param Keyword $keyword
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->data[] = $keyword;
    }
}

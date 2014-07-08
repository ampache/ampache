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
namespace Tmdb\Model\Common\QueryParameter;

use Tmdb\Model\Common\QueryParameter\Type\CollectionToCommaSeperatedString;

/**
 * Class AppendToResponse
 * @package Tmdb\Model\Common\QueryParameter
 */
class AppendToResponse extends CollectionToCommaSeperatedString
{
    /**
     * @return string
     */
    public function getKey()
    {
        return 'append_to_response';
    }
}

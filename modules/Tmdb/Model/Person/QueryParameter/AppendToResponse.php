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
namespace Tmdb\Model\Person\QueryParameter;

use Tmdb\Model\Common\QueryParameter\AppendToResponse as BaseAppendToResponse;

/**
 * Class AppendToResponse
 * @package Tmdb\Model\Person\QueryParameter
 */
final class AppendToResponse extends BaseAppendToResponse
{
    const MOVIE_CREDITS     = 'movie_credits';
    const TV_CREDITS        = 'tv_credits';
    const COMBINED_CREDITS  = 'combined_credits';
    const IMAGES            = 'images';
    const CHANGES           = 'changes';
    const EXTERNAL_IDS      = 'external_ids';
}

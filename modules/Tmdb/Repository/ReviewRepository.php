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
namespace Tmdb\Repository;

use Tmdb\Factory\ReviewFactory;
use Tmdb\Model\Review;

/**
 * Class ReviewRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#reviews
 */
class ReviewRepository extends AbstractRepository
{
    /**
     * Get the full details of a review by ID.
     *
     * @param $id
     * @param  array  $parameters
     * @param  array  $headers
     * @return Review
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {
        return $this->getFactory()->create(
            $this->getApi()->getReview($id, $parameters, $headers)
        );
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\Reviews
     */
    public function getApi()
    {
        return $this->getClient()->getReviewsApi();
    }

    /**
     * @return ReviewFactory
     */
    public function getFactory()
    {
        return new ReviewFactory();
    }
}

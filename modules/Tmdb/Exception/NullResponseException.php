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
namespace Tmdb\Exception;
use Tmdb\HttpClient\Request;

/**
 * Class RuntimeException
 * @package Tmdb\Exception
 */
class NullResponseException extends RuntimeException
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request         $request
     * @param \Exception|null $previous
     */
    public function __construct(Request $request, \Exception $previous = null)
    {
        $this->request = $request;

        if (null !== $previous && is_a($previous, 'GuzzleHttp\Exception\RequestException')) {
            $message = sprintf(
                'The request to path "%s" with query parameters "%s" failed to return a valid response. The previous exception reported "%s" at "%s:%d".',
                $request->getPath(),
                json_encode($request->getParameters()->all()),
                $previous->getMessage(),
                $previous->getFile(),
                $previous->getLine()
            );
        } else {
            $message = sprintf(
                'The request to path "%s" with query parameters "%s" failed to return a valid response.',
                $request->getPath(),
                json_encode($request->getParameters()->all())
            );
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param  Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}

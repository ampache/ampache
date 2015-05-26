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
namespace Tmdb\Event;

use Symfony\Component\EventDispatcher\Event;
use Tmdb\HttpClient\Request;
use Tmdb\HttpClient\Response;
use Tmdb\Model\AbstractModel;

class HydrationEvent extends Event
{
    /**
     * @var AbstractModel
     */
    private $subject;

    /**
     * @var array
     */
    private $data;

    /**
     * @var Request|null
     */
    private $lastRequest;

    /**
     * @var Response|null
     */
    private $lastResponse;

    /**
     * Constructor
     *
     * @param AbstractModel $subject
     * @param array         $data
     */
    public function __construct(AbstractModel $subject, array $data = [])
    {
        $this->subject = $subject;
        $this->data    = $data;
    }

    /**
     * @return AbstractModel
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param  AbstractModel $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param  array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasData()
    {
        return !empty($this->data);
    }

    /**
     * @return Request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @param  Request|null $lastRequest
     * @return $this
     */
    public function setLastRequest($lastRequest)
    {
        $this->lastRequest = $lastRequest;

        return $this;
    }

    /**
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param  Response|null $lastResponse
     * @return $this
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;

        return $this;
    }
}

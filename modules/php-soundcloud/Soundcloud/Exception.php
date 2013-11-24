<?php
/**
 * Soundcloud missing client id exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Missing_Client_Id_Exception extends Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'All requests must include a consumer key. Referred to as client_id in OAuth2.';

}

/**
 * Soundcloud invalid HTTP response code exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Invalid_Http_Response_Code_Exception extends Exception {

    /**
     * HTTP response body.
     *
     * @access protected
     *
     * @var string
     */
    protected $httpBody;

    /**
     * HTTP response code.
     *
     * @access protected
     *
     * @var integer
     */
    protected $httpCode;

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The requested URL responded with HTTP code %d.';

    /**
     * Constructor.
     *
     * @param string $message
     * @param string $code
     * @param string $httpBody
     * @param integer $httpCode
     *
     * @return void
     */
    function __construct($message = null, $code = 0, $httpBody = null, $httpCode = 0) {
        $this->httpBody = $httpBody;
        $this->httpCode = $httpCode;
        $message = sprintf($this->message, $httpCode);

        parent::__construct($message, $code);
    }

    /**
     * Get HTTP response body.
     *
     * @return mixed
     */
    function getHttpBody() {
        return $this->httpBody;
    }

    /**
     * Get HTTP response code.
     *
     * @return mixed
     */
    function getHttpCode() {
        return $this->httpCode;
    }

}

/**
 * Soundcloud unsupported response format exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Unsupported_Response_Format_Exception extends Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The given response format is unsupported.';

}

/**
 * Soundcloud unsupported audio format exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class Services_Soundcloud_Unsupported_Audio_Format_Exception extends Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The given audio format is unsupported.';

}

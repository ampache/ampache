<?php
namespace Dropbox;

/**
 * Use with {@link OAuth1Upgrader} to convert old OAuth 1 access tokens
 * to OAuth 2 access tokens.  This SDK doesn't support using OAuth 1
 * access tokens for regular API calls.
 */
class OAuth1AccessToken
{
    /**
     * The OAuth 1 access token key.
     *
     * @return string
     */
    function getKey() { return $this->key; }

    /** @var string */
    private $key;

    /**
     * The OAuth 1 access token secret.
     *
     * Make sure that this is kept a secret.  Someone with your app secret can impesonate your
     * application.  People sometimes ask for help on the Dropbox API forums and
     * copy/paste code that includes their app secret.  Do not do that.
     *
     * @return string
     */
    function getSecret() { return $this->secret; }

    /** @var secret */
    private $secret;

    /**
     * Constructor.
     *
     * @param string $key
     *     {@link getKey()}
     * @param string $secret
     *     {@link getSecret()}
     */
    function __construct($key, $secret)
    {
        AppInfo::checkKeyArg($key);
        AppInfo::checkSecretArg($secret);

        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Use this to check that a function argument is of type <code>AppInfo</code>
     *
     * @internal
     */
    static function checkArg($argName, $argValue)
    {
        if (!($argValue instanceof self)) Checker::throwError($argName, $argValue, __CLASS__);
    }
}

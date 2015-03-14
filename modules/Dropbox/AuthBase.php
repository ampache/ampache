<?php
namespace Dropbox;

/**
 * Base class for API authorization-related classes.
 */
class AuthBase
{
    /**
     * Whatever AppInfo was passed into the constructor.
     *
     * @return AppInfo
     */
    function getAppInfo() { return $this->appInfo; }

    /** @var AppInfo */
    protected $appInfo;

    /**
     * An identifier for the API client, typically of the form "Name/Version".
     * This is used to set the HTTP <code>User-Agent</code> header when making API requests.
     * Example: <code>"PhotoEditServer/1.3"</code>
     *
     * If you're the author a higher-level library on top of the basic SDK, and the
     * "Photo Edit" app's server code is using your library to access Dropbox, you should append
     * your library's name and version to form the full identifier.  For example,
     * if your library is called "File Picker", you might set this field to:
     * <code>"PhotoEditServer/1.3 FilePicker/0.1-beta"</code>
     *
     * The exact format of the <code>User-Agent</code> header is described in
     * <a href="http://tools.ietf.org/html/rfc2616#section-3.8">section 3.8 of the HTTP specification</a>.
     *
     * Note that underlying HTTP client may append other things to the <code>User-Agent</code>, such as
     * the name of the library being used to actually make the HTTP request (such as cURL).
     *
     * @return string
     */
    function getClientIdentifier() { return $this->clientIdentifier; }

    /** @var string */
    protected $clientIdentifier;

    /**
     * The locale of the user of your application.  Some API calls return localized
     * data and error messages; this "user locale" setting determines which locale
     * the server should use to localize those strings.
     *
     * @return null|string
     */
    function getUserLocale() { return $this->userLocale; }

    /** @var string */
    protected $userLocale;

    /**
     * Constructor.
     *
     * @param AppInfo $appInfo
     *     See {@link getAppInfo()}
     * @param string $clientIdentifier
     *     See {@link getClientIdentifier()}
     * @param null|string $userLocale
     *     See {@link getUserLocale()}
     */
    function __construct($appInfo, $clientIdentifier, $userLocale = null)
    {
        AppInfo::checkArg("appInfo", $appInfo);
        Client::checkClientIdentifierArg("clientIdentifier", $clientIdentifier);
        Checker::argStringNonEmptyOrNull("userLocale", $userLocale);

        $this->appInfo = $appInfo;
        $this->clientIdentifier = $clientIdentifier;
        $this->userLocale = $userLocale;
    }
}

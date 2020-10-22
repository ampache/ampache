---
title: "Errors"
metaTitle: "Errors"
metaDescription: "API documentation"
---

Ampache's API errors are loosely based around the HTTP status codes. All errors are returned in the form of an XML/JSON Document however the string error message provided is translated into the language of the Ampache server in question. All services should only use the code value.

For Ampache5 error codes are changing and expanding on the information available to the user/client/application that caused the error.

An Ampache5 error has the following parts:

* errorCode: numeric code
* errorAction: method that caused the error
* errorType: further information such as the type of data missing or access level required
* errorMessage: translated error message

## Rules Regarding errors

* XML and JSON errors are always in an 'error' object.
* Errors will always provide a code
* The data names user in the error must use names that don't conflict with other data objects
* Allow the ability to drill down even further using the action and type of error
  * errorAction will return the method used that caused the error
  * Use errorType 'system' for things users can't change / server config
  * Use errorType 'account' for user issues (password, perms, auth, etc)
* errorMessage must be a translated string to allow devs to show things for the user in their language.

## Error Codes

All error codes are accompanied by a string value for the error and derived from the [HTTP/1.1 Status Codes](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html)

To separate Ampache from the http codes it's been decided to prefix our codes with 47 to allow clear differentiation

* **4700** Access Control not Enabled
  * The API is disabled. Enable 'access_control' in your config
* **4701** Received Invalid Handshake
  * This is a temporary error, this means no valid session was passed or the handshake failed
* **4703** Access Denied
  * The requested method is not available
  * You can check the error message for details about which feature is disabled
* **4704** Not Found
  * The API could not find the requested object
* **4705** Missing
  * This is a fatal error, the service requested a method that the API does not implement
* **4706** Depreciated
  * This is a fatal error, the method requested is no longer available
* **4710** Bad Request
  * Used when you have specified a valid method but something about the input is incorrect, invalid or missing
  * You can check the error message for details, but do not re-attempt the exact same request
* **4742** Failed Access Check
  * Access denied to the requested object or function for this user

## Example Error messages

Error 4700: Access Control not Enabled

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4700.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
    <error errorCode="4700">
        <errorAction><![CDATA[handshake]]></errorAction>
        <errorType><![CDATA[system]]></errorType>
        <errorMessage><![CDATA[Access Denied]]></errorMessage>
    </error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4700.json)

```JSON
{
    "error": {
        "errorCode": "4700",
        "errorAction": "handshake",
        "errorType": "system",
        "errorMessage": "Access Denied"
    }
}
```

Error 4701: Received Invalid Handshake

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4701.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
	<error errorCode="4701">
		<errorAction><![CDATA[playlist_create]]></errorAction>
		<errorType><![CDATA[account]]></errorType>
		<errorMessage><![CDATA[Session Expired]]></errorMessage>
	</error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4701.json)

```JSON
{
    "error": {
        "errorCode": "4701",
        "errorAction": "playlist_create",
        "errorType": "account",
        "errorMessage": "Session Expired"
    }
}
```

Error 4703: Missing Feature

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4703.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
    <error errorCode="4703">
        <errorAction><![CDATA[podcasts]]></errorAction>
        <errorType><![CDATA[system]]></errorType>
        <errorMessage><![CDATA[Enable: podcast]]></errorMessage>
    </error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4703.json)

```JSON
{
    "error": {
        "errorCode": "4703",
        "errorAction": "podcasts",
        "errorType": "system",
        "errorMessage": "Enable: podcast"
    }
}
```

Error 4704: Not Found

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4704.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
	<error errorCode="4704">
		<errorAction><![CDATA[scrobble]]></errorAction>
		<errorType><![CDATA[song]]></errorType>
		<errorMessage><![CDATA[Not Found]]></errorMessage>
	</error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4704.json)

```JSON
{
    "error": {
        "errorCode": "4704",
        "errorAction": "scrobble",
        "errorType": "song",
        "errorMessage": "Not Found"
    }
}
```

Error 4705: Missing Method

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4705.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
	<error errorCode="4705">
		<errorAction><![CDATA[plafgfylist_create]]></errorAction>
		<errorType><![CDATA[system]]></errorType>
		<errorMessage><![CDATA[Invalid Request]]></errorMessage>
	</error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4705.json)

```JSON
{
    "error": {
        "errorCode": "4705",
        "errorAction": "plafgfylist_create",
        "errorType": "system",
        "errorMessage": "Invalid Request"
    }
}
```

Error 4706: Depreciated Method

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4706.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
	<error errorCode="4706">
		<errorAction><![CDATA[tag_songs]]></errorAction>
		<errorType><![CDATA[removed]]></errorType>
		<errorMessage><![CDATA[Depreciated]]></errorMessage>
	</error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4706.json)

```JSON
{
    "error": {
        "errorCode": "4706",
        "errorAction": "tag_songs",
        "errorType": "removed",
        "errorMessage": "Depreciated"
    }
}
```

Error 4710: Bad Request

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4710.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
	<error errorCode="4710">
		<errorAction><![CDATA[playlist_create]]></errorAction>
		<errorType><![CDATA[system]]></errorType>
		<errorMessage><![CDATA[Bad Request: name]]></errorMessage>
	</error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4710.json)

```JSON
{
    "error": {
        "errorCode": "4710",
        "errorAction": "playlist_create",
        "errorType": "system",
        "errorMessage": "Bad Request: name"
    }
}
```

Error 4742: Failed Access Check

[Example XML](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/error-4742.xml)

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
	<error errorCode="4710">
		<errorAction><![CDATA[playlist_delete]]></errorAction>
		<errorType><![CDATA[account]]></errorType>
		<errorMessage><![CDATA[Require: 100]]></errorMessage>
	</error>
</root>
```

[Example JSON](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/error-4742.json)

```JSON
{
    "error": {
        "errorCode": "4742",
        "errorAction": "playlist_delete",
        "errorType": "account",
        "errorMessage": "Require: 100"
    }
}
```

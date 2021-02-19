---
title: "Access Control Lists 4.3"
metaTitle: "Access Control Lists 4.3"
metaDescription: "API documentation 4.3"
---

Ampache supports internal Access Control Lists, these are IP/DNS based restrictions on different actions and interactions with Ampache. By Default Access Controls lists are turned off in Ampache. In order to turn them on you must modify the _/config/ampache.cfg.php_ and set access_control to true

```INI
; Use Access List
; Toggle this on if you want ampache to pay attention to the access list
; and only allow streaming/downloading/xml-rpc from known hosts xml-rpc
; will not work without this on.
; NOTE: Default Behavior is DENY FROM ALL
; DEFAULT: false
;access_control = "false"
```

The default configuration of Ampache's ACLs when enabled is Deny From All. There are a few different types, and levels

## Start IP & End IP

This is a range of IP addresses represented by a pair of dotted quad's. This does not have to be within a subnet boundary. Currently only IPV4 is supported.

**Any IP Address:**

`0.0.0.0 - 255.255.255.255`

**Any 10.x IP Address:**

`10.0.0.0 - 10.255.255.255`

## ACL Types

* **Interface** - Access to the web interface
  * Restricts Login based on IP
  * Defaults to DENY FROM ALL
* **Streaming** - Controls streaming/downloading access
  * Restricts access to /play/index.php based on IP + USER
  * Defaults to DENY FROM ALL
* **Local Network** - Local network ACL
  * Used by the downsample remote configuration option
  * Tells Ampache which IP addresses should be considered local to the server and which ones are remote
  * Default not applicable
* **RPC** - Used to control remote access to your Ampache installation
  * Remote access to the [Ampache API](API.md)
  * Remote Sync using XML-RPC.
  * Restricts based on IP + USER + KEY, KEY may not be blank
  * Defaults to DENY FROM ALL

## ACL Users

Ampache allows you to define different ACLs to different users. This can be useful for defining connecting an API calls to a username, or to limiting a specific user's streaming access regardless of their IP Address. The default is 'system' which will apply to all users of Ampache.

## Access Levels

This setting is not fully implemented, more on this later

## Setting up an ACL

ACl's can only be created by Full Administrators. You can find them under the Admin Menu under the submenu Access Control

# Ampache

![Logo](http://ampache.org/img/logo/ampache-logo_x64.png)

[www.ampache.org](http://ampache.org/) |
[ampache.github.io](http://ampache.github.io)

**News:**

* Keep an eye on the incoming changes to develop branch at [Ampache-Next-Changes](https://github.com/ampache/ampache/wiki/Ampache-Next-Changes)
* The Ampache kodi plugin has merged into the official Kodi addons repo!
 * Make sure you enable **hide_search** in your config to enable playlists in this initial version.
* Develop changes
  * **DATABASE CHANGES** You can now force a default collation and charset on your database. (Read the changelog!)
  * Develop branch now has a **mandatory** requirement of php-intl for date formatting.
  * The initial development version of api 5.0.0 has been released! Please go over the changes on the [ampache.org](http://ampache.org/api/) website!
* Get your album/artist art right from Spotify's web API. Go to the [dashboard](https://developer.spotify.com/dashboard/)
  * Then put your client ID and secret in your ampache config
  * And don't forget to set your art order! (art_order = "db,tags,folder,spotify,musicbrainz")

## Basics

Ampache is a web based audio/video streaming application and file
manager allowing you to access your music & videos from anywhere,
using almost any internet enabled device.

Ampache's usefulness is heavily dependent on being able to extract
correct metadata from embedded tags in your files and/or the file name.
Ampache is not a media organiser; it is meant to be a tool which
presents an already organised collection in a useful way. It assumes
that you know best how to manage your files and are capable of
choosing a suitable method for doing so.

## Recommended Version

The recommended and most stable version is [git HEAD](https://github.com/ampache/ampache/archive/master.tar.gz).
[![Build Status](https://api.travis-ci.org/ampache/ampache.png?branch=master)](https://travis-ci.org/ampache/ampache)

You get the latest version with recent changes and fixes but maybe in an unstable state from our [develop branch](https://github.com/ampache/ampache/archive/develop.tar.gz).
[![Build Status](https://api.travis-ci.org/ampache/ampache.png?branch=develop)](https://travis-ci.org/ampache/ampache)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ampache/ampache/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/ampache/ampache/?branch=develop)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/f995711a30364908968bf0efb3e7e257)](https://app.codacy.com/gh/ampache/ampache)
[![Code Climate](https://codeclimate.com/github/ampache/ampache/badges/gpa.svg)](https://codeclimate.com/github/ampache/ampache)

## Installation

Please see [the wiki](https://github.com/ampache/ampache/wiki/Installation) and don't forget to check out the [basic config](https://github.com/ampache/ampache/wiki/Basic) guide after that.

## Requirements

* A web server. All of the following have been used, though Ampache receives the most testing with Apache:
  * Apache
  * lighttpd
  * nginx
  * IIS

* PHP 7.1 or greater. (Currently tested on php7.4-fpm)

* PHP modules:
  * PDO
  * PDO_MYSQL
  * hash
  * session
  * json
  * intl (Ampache develop)
  * simplexml (optional)
  * curl (optional)

* For FreeBSD The following php modules must be loaded:
  * php-xml
  * php-dom
  * php-intl (Ampache develop)

* MySQL 5.x / MySQL 8.x / MariaDB 10.x

## Upgrading

If you are upgrading from an older version of Ampache we recommend
moving the old directory out of the way, extracting the new copy in
its place and then copying the old /config/ampache.cfg.php, /rest/.htaccess,
/channel/.htaccess, and /play/.htaccess files if any.
All database updates will be handled by Ampache.

## License

Ampache is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License v3 (AGPL-3.0-or-later)
as published by the Free Software Foundation.

Ampache includes some [external modules](https://github.com/ampache/ampache/blob/develop/composer.lock) that carry their own licensing.

## Translations

Ampache is currently translated (at least partially) into the
following languages. If you are interested in updating an existing
translation, simply visit us on [Transifex](https://www.transifex.com/ampache/ampache).
If you prefer it old school or want to work offline, take a look at [/locale/base/TRANSLATIONS](https://github.com/ampache/ampache/blob/develop/locale/base/TRANSLATIONS.md)
for more instructions.

Translation progress so far:

[![Transifex](https://www.transifex.com/_/charts/redirects/ampache/ampache/image_png/messagespot/)](https://www.transifex.com/projects/p/ampache/)

## Credits

Thanks to all those who have helped make Ampache awesome: [Credits](docs/ACKNOWLEDGEMENTS.md)

## Contact Us

Hate it? Love it? Let us know! Dozens of people send ideas for amazing new features, report bugs and further develop Ampache actively. Be a part of Ampache with it's more than 10 years long history and get in touch with an awesome and friendly community!

* For Live discussions, visit us on our IRC Channel at chat.freenode.net #ampache or alternative via a [web based chat client](https://webchat.freenode.net)
* For harder cases or general discussion about Ampache take a look at our [Google Groups Forum](https://groups.google.com/forum/#!forum/ampache)
* Found a bug or Ampache isn't working as expected? Please refer to the [Issues Template](https://github.com/ampache/ampache/wiki/Issues) and head over to our [Issue Tracker](https://github.com/ampache/ampache/issues)
* [r/Ampache](https://www.reddit.com/r/ampache/)
* [Our Telegram Group](https://t.me/ampache)
* [Official Twitter](https://twitter.com/ampache)
* [Official Mastodon](https://fosstodon.org/@ampache)

## Further Information and basic Help

* Everything related to the Ampache Project can be found on our [Public Repository](https://github.com/ampache)
* Want to know, how to get Apache to work or learn more about the functions? See our [Documentation](https://github.com/ampache/ampache/wiki)

We hope to see you soon and that you have fun with this Project!

[Team Ampache](docs/ACKNOWLEDGEMENTS.md)

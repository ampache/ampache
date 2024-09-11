# Ampache

![Logo](https://ampache.org/img/logo/ampache-logo_x64.png)

[www.ampache.org](https://ampache.org/)

[Ampache Docker](https://hub.docker.com/repository/docker/ampache/ampache)

## News

Ampache6 is [here!](https://github.com/ampache/ampache/releases/)

Ampache7 development has begun on the patch7 branch!

This branch will become release7 and replace the develop and master branches.

Information and changes for this major release are being recorded in the wiki [here.](https://github.com/ampache/ampache/wiki/ampache7-for-admins)

Ampache7 will continue to use **API6** and will not make any breaking changes to that [API](https://ampache.org/api/).

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

* Check out [Ampache 6 for Admins](https://github.com/ampache/ampache/wiki/ampache6-details)
* As well as [Ampache 6 for Users](https://github.com/ampache/ampache/wiki/ampache6-for-users)

## Recommended Version

The recommended and most stable version is the current stable [release6 branch](https://github.com/ampache/ampache/archive/release6.tar.gz).

You get the latest version with recent changes and fixes but maybe in an unstable state from our [develop branch](https://github.com/ampache/ampache/archive/develop.tar.gz).
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ampache/ampache/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/ampache/ampache/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/ampache/ampache/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/ampache/ampache/?branch=develop)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/f995711a30364908968bf0efb3e7e257)](https://app.codacy.com/gh/ampache/ampache)
[![Code Climate](https://codeclimate.com/github/ampache/ampache/badges/gpa.svg)](https://codeclimate.com/github/ampache/ampache)

If you want to run the last stable version [release5](https://github.com/ampache/ampache/archive/release5.tar.gz) is still available

## Installation

Please see [the wiki](https://github.com/ampache/ampache/wiki/Installation) and don't forget to check out the [basic config](https://github.com/ampache/ampache/wiki/Basic) guide after that.

## Requirements

* A web server. All of the following have been used, though Ampache receives the most testing with Apache:
  * Apache
  * lighttpd
  * nginx
  * IIS

* The correct PHP version for your Ampache release
  * PHP 7.1-7.4 (Ampache 4.x.x)
  * PHP 7.4 (Ampache 5.0.x -> Ampache 6.x.x)
  * PHP 8.0 (Ampache 5.1.x -> Ampache 6.x.x)
  * PHP 8.1 (Ampache 5.5.0 -> Ampache 6.x.x)
  * PHP 8.2 (Ampache 6.0.0 and higher)
  * PHP 8.3 (Ampache 6.2.0 and higher)

**NOTE** That php7.4 will not be supported for Ampache6 but can still be built.

* PHP modules:
  * PDO
  * PDO_MYSQL
  * hash
  * session
  * json (included in php8+)
  * intl
  * simplexml
  * curl
  * zip (Required in Ampache 7.0.0 and higher)

* For FreeBSD The following php modules must be loaded:
  * php-xml
  * php-dom
  * php-intl
  * php-zip
 
* Node.js v15+, npm v7+ (Required in Ampache 7.0.0 and higher)

* MySQL 5.x / MySQL 8.x / MariaDB 10.x

## Upgrading

If you are upgrading from an older version of Ampache we recommend
moving the old directory out of the way, extracting the new copy in
its place and then copying the old /config/ampache.cfg.php,
/rest/.htaccess, and /play/.htaccess files if any.
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
If you prefer it old school or want to work offline, take a look at [locale/base/TRANSLATIONS](https://github.com/ampache/ampache/blob/develop/locale/base/TRANSLATIONS.md)
for more instructions.

Translation progress so far:

[![Transifex](https://www.transifex.com/_/charts/redirects/ampache/ampache/image_png/messagespot/)](https://www.transifex.com/projects/p/ampache/)

## Admin tools and helper scripts

There are a lot of automation and helper tools used to help keep the Ampache release processes running smoothly.

The [Ampache Administrator](https://github.com/lachlan-00/ampache-administrator) repo is used to build, document and test all Ampache releases.

The [ampache-python3](https://github.com/ampache/python3-ampache) repo is used heavily in the admin repo and provides a few example scripts to do some simple tasks using the latest API versions.

The [Ampache Helper Scripts](https://github.com/icefields/Ampache-Helper-Scripts) uses lua and is being used to help in Android development and testing of [Power Ampache 2](https://github.com/icefields/Power-Ampache-2)

## Credits

Ampache would like to request anyone wanting to donate to the project spend that money on the people who really deserve it.

If you use these projects please consider donating in any way possible. (Including your time if you can help out!)

@mitchray developer of [ample](https://github.com/mitchray/ample).
<a target="_blank" href="[https://www.paypal.com/paypalme/musiquelibre](https://buymeacoffee.com/mitchray)">
<img width="32" height="32" class="octicon rounded-2 d-block" alt="buy_me_a_coffee" src="https://github.githubassets.com/assets/buy_me_a_coffee-63ed78263f6e.svg">https://buymeacoffee.com/mitchray</a>

@icefields developer of [Power Ampache](https://power.ampache.dev/)
<a target="_blank" href="https://www.patreon.com/Icefields">
<img height="50" hspace="20" alt="Become a patreon" src="https://github.com/user-attachments/assets/3318ab05-3c7e-42dd-8784-f12129c0915d"></a>
<a target="_blank" href="https://live.blockcypher.com/btc/address/bc1qm9dvdrukgrqpg5f7466u4cy7tfvwcsc8pqshl4">
<img height="30" hspace="20" alt="Donate Bitcoin" src="https://power.ampache.dev/images/banner_bitcoin.png"></a>
<a target="_blank" href="https://paypal.me/powerampache">
<img height="40" hspace="20" alt="Donate - Paypal" src="https://power.ampache.dev/images/banner_paypal.png"></a>

If you're a fan of [play.dogmazic.net](https://play.dogmazic.net/)? Donate to the libre music association
<a target="_blank" href="https://www.paypal.com/paypalme/musiquelibre">
<img height="40" hspace="20" alt="Donate - Paypal" src="https://clipart-library.com/image_gallery2/PayPal-Donate-Button-PNG-Images.png"></a>

Thanks to all those who have helped make Ampache [awesome](docs/ACKNOWLEDGEMENTS.md).

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

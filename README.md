 ![Logo](http://ampache.org/img/logo/ampache-logo_x64.png) Ampache
=======
[www.ampache.org](http://ampache.org/) |
[ampache.github.io](http://ampache.github.io)

Basics
------

Ampache is a web based audio/video streaming application and file
manager allowing you to access your music & videos from anywhere,
using almost any internet enabled device.

Ampache's usefulness is heavily dependent on being able to extract
correct metadata from embedded tags in your files and/or the file name.
Ampache is not a media organiser; it is meant to be a tool which
presents an already organised collection in a useful way. It assumes
that you know best how to manage your files and are capable of
choosing a suitable method for doing so.

Recommended Version
-------------------

The recommended and most stable version is [git HEAD](https://github.com/ampache/ampache/archive/master.tar.gz).
[![Build Status](https://api.travis-ci.org/ampache/ampache.png?branch=master)](https://travis-ci.org/ampache/ampache)

You get the latest version with recent changes and fixes but maybe in an unstable state from our [develop branch](https://github.com/ampache/ampache/archive/develop.tar.gz).
[![Build Status](https://api.travis-ci.org/ampache/ampache.png?branch=develop)](https://travis-ci.org/ampache/ampache)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ampache/ampache/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/ampache/ampache/?branch=develop)
[![Codacy Badge](https://api.codacy.com/project/badge/b28cdb9e9ee2431c7cb9c23d5438cb80)](https://www.codacy.com/app/afterster_2222/ampache)
[![Code Climate](https://codeclimate.com/github/ampache/ampache/badges/gpa.svg)](https://codeclimate.com/github/ampache/ampache)

Requirements
------------

* A web server. All of the following have been used, though Apache
receives the most testing:
    * Apache
    * lighttpd
    * nginx
    * IIS

* PHP 5.4 or greater.

* PHP modules:
    * PDO
    * PDO_MYSQL
    * hash
    * session
    * json
    * simplexml (This is not strictly necessary, but may result in a better experience.)
    * curl (This is not strictly necessary, but may result in a better experience.)

* MySQL 5.x

Installation
------------

Please see [the wiki](https://github.com/ampache/ampache/wiki/Installation)

Upgrading
---------

If you are upgrading from an older version of Ampache we recommend
moving the old directory out of the way, extracting the new copy in
its place and then copying the old /config/ampache.cfg.php, /rest/.htaccess,
and /play/.htaccess files if any. All database updates will be handled by Ampache.

License
-------

Ampache is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License v3 (AGPLv3)
as published by the Free Software Foundation.

Ampache includes some external modules that carry their own licensing.

* [getID3](http://getid3.sourceforge.net): GPL v2
* [Horde_Browser](http://www.horde.org): LGPL v2.1
* [Gettext](https://github.com/oscarotero/Gettext): MIT
* [MusicBrainz](https://github.com/mikealmond/MusicBrainz): MIT
* PHP MPD interface: GPL v2
* [PHPMailer](https://github.com/PHPMailer/PHPMailer): LGPL v2.1
* [jQuery](https://jquery.org/): MIT
* [Requests](http://requests.ryanmccue.info): ISC Licensed
* [xbmc-php-rpc](https://github.com/krixon/xbmc-php-rpc): GPL v3
* [Dropbox SDK](https://github.com/dropbox/dropbox-sdk-php): MIT
* [jPlayer](http://jplayer.org): MIT
* [prettyPhoto](http://www.no-margin-for-errors.com/projects/prettyphoto-jquery-lightbox-clone): GPL v2
* [Tag-it!] (http://aehlke.github.io/tag-it): MIT
* [PHP Echo Nest API] (https://github.com/bshaffer/php-echonest-api): MIT
* [Noty] (http://ned.im/noty/): MIT
* [jScroll] (https://github.com/pklauzinski/jscroll): MIT
* [jquery.qrcode] (http://jeromeetienne.github.io/jquery-qrcode): MIT
* [PHP OpenID] (https://github.com/openid/php-openid): Apache License
* [Ratchet] (http://socketo.me): MIT
* [ReactPHP] (https://github.com/reactphp/react): MIT
* [Guzzle] (https://github.com/guzzle/guzzle): MIT
* [Symfony Components] (https://github.com/symfony): MIT
* [Evenement] (https://github.com/igorw/evenement): MIT
* [RhinoSlider] (http://www.rhinoslider.com/): MIT
* [MediaTable] (https://github.com/edenspiekermann/MediaTable): MIT
* [Responsive Elements] (https://github.com/kumailht/responsive-elements): MIT
* [Bootstrap] (http://getbootstrap.com): MIT
* [jQuery Knob] (https://github.com/aterrien/jQuery-Knob): MIT
* [jQuery File Upload] (https://github.com/blueimp/jQuery-File-Upload): MIT
* [jsTree] (https://www.jstree.com/): MIT
* [php-tmdb-api] (https://github.com/php-tmdb/api) : MIT
* [TvDb] (https://github.com/Moinax/TvDb) : MIT
* [jQuery DateTimePicker] (https://github.com/xdan/datetimepicker) : MIT
* [pChart] (http://www.pchart.net) : GPL v3
* [ZipStream-PHP] (https://github.com/maennchen/ZipStream-PHP) : [ZipStream-PHP license] (modules/zipstream/COPYING)
* [SabreDAV] (https://github.com/fruux/sabre-dav) : New BSD


Translations
------------

Ampache is currently translated (at least partially) into the
following languages. If you are interested in updating an existing
translation, simply visit us on [Transifex](https://www.transifex.com/ampache/ampache).
If you prefer it old school or want to work offline, take a look at [/locale/base/TRANSLATIONS](https://github.com/ampache/ampache/blob/develop/locale/base/TRANSLATIONS.md)
for more instructions.

Translation progress so far:

[![Transifex](https://www.transifex.com/projects/p/ampache/resource/messagespot/chart/image_png)](https://www.transifex.com/projects/p/ampache/)

Credits
-------

Thanks to all those who have helped make Ampache awesome: [Credits](docs/ACKNOWLEDGEMENTS)


Contact Us
----------

Hate it? Love it? Let us know! Dozens of people send ideas for amazing new features, report bugs and further develop Ampache actively. Be a part of Ampache with it's more than 10 years long history and get in touch with an awesome and friendly community!

* For Live discussions, visit us on our IRC Channel at chat.freenode.net #ampache or alternative via a [web based chat client](https://webchat.freenode.net)
* For harder cases or general discussion about Ampache take a look at our [Google Groups Forum](https://groups.google.com/forum/#!forum/ampache)
* Found a bug or Ampache isn't working as expected? Head over to our [Issue Tracker](https://github.com/ampache/ampache/issues)

Further Information and basic Help
----------------------------------

* Everything related to the Ampache Project can be found on our [Public Repository](https://github.com/ampache)
* Want to know, how to get Apache to work or learn more about the functions? See our [Documentation](https://github.com/ampache/ampache/wiki)

We hope to see you soon and that you have fun with this Project!

[Team Ampache](docs/ACKNOWLEDGEMENTS)

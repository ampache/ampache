# Ampache - React Client/Interface

![Logo](http://ampache.org/img/logo/ampache-logo_x64.png) Ampache

A fork of https://github.com/ampache/ampache/tree/develop

## Point Of This
This client is mean to replace the Reborn interface. Using modern technologies and easier to maintain code.

## Installation


## Requirements

To build this project you will need [Yarn](https://yarnpkg.com/lang/en/docs/install/). In addition to the requirements of Ampache itself.

###Build
Clone the repo: `git clone https://github.com/AshotN/ampache/tree/ReactClient`

Use yarn to download all needed packages: `yarn`

Run Parcel to build: `yarn run build`

Run a PHP server locally on port 8080: `/bin/php -S localhost:8080 -t {PATH TO BASE FOLDER}`


###Existing Ampach drop-in
If you wish to run the client on an existing copy of Ampache. There will be one file needing to be replaced since Ampache does not natively support JSON, if you rely on the current API, before that since my file is rather outdated compared to [origin/develop](https://github.com/ampache/ampache/tree/develop), you may cause some features to break in external clients that rely on those features. I suggest backing up your files.

First put the `newclient` folder into your base directory. 

Second replace `lib/class/api.class.php` with this [file](https://github.com/AshotN/ampache/blob/ReactClient/lib/class/api.class.php)

Also add `json_data.class.php` into `/lib/class/`. [File](https://github.com/AshotN/ampache/blob/ReactClient/lib/class/json_data.class.php)

Lastly add `json.server.php` into `/server/`. [File](https://github.com/AshotN/ampache/blob/ReactClient/server/json.server.php)

###FAQ(not really)
**Why must I run locally on 8080?** 

Because I hard-coded localhost:8080 for the API calls, when I fix that you can run it wherever you want

**Can I run this on an external server?** 

If you enable CORS and change all the API calls in the source code.

**Will these instructions work?** 

Hopefully 
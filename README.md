# Ampache - React Client/Interface

![Logo](http://ampache.org/img/logo/ampache-logo_x64.png) Ampache

A fork of https://github.com/ampache/ampache/tree/develop

## Point Of This
This client is mean to replace the Reborn interface. Using modern technologies and easier to maintain code.


## Requirements

To build this project you will need [Yarn](https://yarnpkg.com/lang/en/docs/install/). In addition to the requirements of Ampache itself.

### Build
Clone the repo: `git clone https://github.com/AshotN/ampache/tree/ReactClient`

Use yarn to download all needed packages: `yarn`

Run Parcel to build: `yarn run build`

Run a PHP server locally on port 8080: `/bin/php -S localhost:8080 -t {PATH TO BASE FOLDER}`


### Existing Ampach drop-in
If you wish to run the client on an existing copy of Ampache. 

Put the `newclient` folder into your base directory. 

####Since version 4.1.0 JSON support is now built in Ampache!

## FAQ(not really)


**Can I run this on an external server?** 

If you enable CORS and change the .env file and build.

**Can you prioritize X feature!** 

Open an issue about it please!

**Will these instructions work?** 

Hopefully 

# Ampache Release Guide

## Release Naming

* Each supported php version is tagged by binary name, e.g. `ampache-8.x.x_all_php8.5.zip`
* Ampache8 requires PHP 8.5+, so it is the only build; the structure variants add a suffix, e.g. `ampache-8.x.x_all_php8.5_squashed.zip` and `..._client.zip`
* The code-only zips (no `vendor/`, no `public/lib/components/`) drop the php version, e.g. `ampache-8.x.x_public.zip`

## Official Release Process

It's easy to use a program like github desktop to compare between branches.
**Use Linux**

**note** this process has been automated. I now use the `build_release8.sh` script contained at the [ampache-administrator](https://github.com/lachlan-00/ampache-administrator/blob/master/build_release8.sh) repo.

* Export database from a fresh install (phpMyAdmin exports well.)
  * Tables: Structure; **select all tables**
  * Tables: Data; **only select** `access_list`, `license`, `preference`, `search`, `update_info` and `user_preference`
  * Object creation options:
    * Tick "Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement"
    * Tick "IF NOT EXISTS (less efficient as indexes will be generated during table creation)"
  * Save and then remove user 1 settings from `preference` and `user_preference` inserts.
* Open Github Desktop and pull latest develop / master
* Change to master branch
* Go to Branch Menu -> Update from develop
* Fix merge issues
  * src/Config/Init/InitializationHandlerConfig.php (Search for "// AMPACHE_VERSION" to find the lines that need updating)
  * src/Module/Api/Api.php (Set $version and $version_numeric)
  * docs/CHANGELOG.md (Update for release)
  * add new ampache.sql
* Commit merge for new version (e.g. 5.x.x) but **do not push!**
* Browse changes to check for things you've missed in the changelog
* Get missing map files

* Reset the vendor folder completely and pull it all down

```shell
rm -rf ./composer.lock vendor/* public/lib/components/*
```

* Install composer packages for all supported php releases (ONE at a time obviously)

```shell
php8.5 /usr/local/bin/composer install
```

* Install npm packages & build javascript files

```shell
npm install
npm run build
```

* Remove broken symbolic links

```shell
find . -xtype l -exec rm {} \;
```

* Set a version number to skip typing over and over
```shell
read -p "Enter Ampache Version: " a_version
```

* Create the zip packages from the entire ampache directory tree, excluding git and development specific files

  The exclude list is long and is maintained in one place only: the `ZIPEXCLUDE` variables in
  `build_release8.sh`. Read it there rather than copying a list out of here — a stale copy is how a
  development file reaches every install. Six zips are produced per release, one per structure
  (`public`, `squashed`, `client`) in both a code-only and an `_all_php8.5` flavour:

```text
ampache-${a_version}_public.zip             ampache-${a_version}_all_php8.5.zip
ampache-${a_version}_squashed.zip           ampache-${a_version}_all_php8.5_squashed.zip
ampache-${a_version}_client.zip             ampache-${a_version}_all_php8.5_client.zip
```

* Then unpack the exact zip and create a server to test basic functionality

```shell
rm -rf /var/www/html && ln -s /var/www/ampache/public /var/www/html
rm -rf /var/www/ampache && unzip -o ../ampache-${a_version}_all_php8.5.zip -d /var/www/ampache/
```

  `docker-release-test8.sh` automates this for all three structures at once, and is the only step that
  tests a release zip rather than a checkout.

* FIXME This might be where unit testing would be helpful.
* Draft Release page online and save as draft using your changelog
* When drafting a new release, set the tag to the version **5.x.x** and target to the **release5** branch.
* The "ampache-*.*.*_all.zip" drop-in package is then uploaded.
* After setting version and title, save as draft
* Push your waiting update to master
* Publish the new release
  * get the md5hash for the release page

```shell
md5sum ../ampache-${a_version}*.zip
```

## Post release

* Update develop from master **don't push**
* Set the next version in init.php back to 'develop' and update the changelog
* Commit and push "Begin Ampache develop"

## Additional requirements

* Update ampache-docker README.md with the current version.
* Update config file in docker (ampache.cfg.php.dist)
* Update and make a release for python3-ampache following the version with a build (5.x.x-1)
  * run build_docs.py to update the example files
* Create a new release on GitHub to automatically push to [PyPI](https://pypi.org/project/ampache/).

## Update ampache-docker images on docker hub

Update the official Ampache docker images [<https://hub.docker.com/r/ampache/ampache>]

**note** this process has been automated. I now use the `build_docker.sh` script contained at the [ampache-administrator](https://github.com/lachlan-00/ampache-administrator/blob/master/build_docker.sh) repo.

* To bump ampache-docker images rebuild for arm and amd64 using buildx [<https://github.com/docker/buildx>]
* After enabling experimental mode I installed the tools and buildx container.
* Make sure the docker API is [accessible](https://success.docker.com/article/how-do-i-enable-the-remote-api-for-dockerd)

This part should only be needed once.
It creates a local builder that can build the other CPU architectures using [buildx](https://github.com/docker/buildx).

```bash
aptitude install qemu qemu-user-static qemu-user binfmt-support
docker buildx create --name mybuilder mybuilder
docker buildx use mybuilder
docker buildx inspect --bootstrap
```

Log in to your docker account

```bash
docker login -u USER -p PASSWORD
```

Build latest (master) images and push to docker hub.

```bash
git clone -b master https://github.com/ampache/ampache-docker.git ampache-docker/
cd ampache-docker
docker buildx build --no-cache --platform linux/amd64,linux/arm64,linux/arm/v7 --build-arg VERSION=5.x.x -t ampache/ampache:5 -t ampache/ampache:5.x.x -t ampache/ampache:latest --push .
```

Build develop images and push to docker hub.

```bash
git clone -b develop https://github.com/ampache/ampache-docker.git ampache-docker-develop/
cd ampache-docker-develop
docker buildx build --no-cache --platform linux/amd64,linux/arm64,linux/arm/v7 -t ampache/ampache:develop -t ampache/ampache:preview --push .
```

Build nosql images and push to docker hub.

```bash
git clone -b nosql https://github.com/ampache/ampache-docker.git ampache-docker-nosql/
cd ampache-docker-nosql
docker buildx build --no-cache --platform linux/amd64,linux/arm64,linux/arm/v7 --build-arg VERSION=5.x.x -t ampache/ampache:nosql5 -t ampache/ampache:nosql5.x.x -t ampache/ampache:nosql --push .
```

## Additional info

Make sure rolling tags are updated to the latest commit. This is good for pre-release/develop tags

```shell
git tag -f 5.0.0 824380522
```

Then pushing it to github

```shell
git push --force origin 5.0.0
```

## Updating for squashed releases

For the squashed repo you need to manually copy/paste then do some find/replace to get the server working.

After fixing up the paths you can commit then follow the regular release process

**note** this process has been automated. I now use the `build_ampache-squashed.sh` script contained at the [ampache-administrator](https://github.com/lachlan-00/ampache-administrator/blob/master/build_ampache-squashed.sh) repo.

* Clone the repo `git clone -b squashed https://github.com/ampache/ampache.git ampache_squashed/`
* Clone master `git clone -b master https://github.com/ampache/ampache.git ampache_master/`
* Copy everything except the `/public`, `/docker` and `/vendor` folders into ampache_squashed
* Copy everything from /public into the root on the ampache_squashed folder
* find and replace for the following folders

/admin, /daap, /play, /rest, /server, /upnp, /webdav
* find `$dic = require __DIR__ . '/../../src/Config/Init.php';`
* replace `$dic = require __DIR__ . '/../src/Config/Init.php';`

/lib/javascript
* find `$dic = require __DIR__ . '/../../../src/Config/Init.php';`
* replace `$dic = require __DIR__ . '/../../src/Config/Init.php';`

/src, /templates
* find `/public/`
* replace `/`

/
* find `__DIR__ . '/../`
* replace `__DIR__ . '/`

composer.json, locale/base/gather-messages.sh
* find `public/lib`
* replace `lib`

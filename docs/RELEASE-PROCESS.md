# Ampache Release Guide

## Official Release Process

It's easy to use a program like github desktop to compare between branches.
**Use Linux**

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
  * lib/init.php (Set release version)
  * lib/class/api.class.php (Set release version)
  * docs/CHANGELOG.md (Update for release)
  * add new ampache.sql
* Commit merge for new version (e.g. 5.x.x) but **do not push!**
* Browse changes to check for things you've missed in the changelog
* Add pchart to composer
  * composer require --update-no-dev szymach/c-pchart "3.*"
* ~~Run composer install~~ (adding pchart updates everything)
* Check `public/lib/components/prettyphoto/images` exists
* Get missing map files

```shell
wget -P ./public/lib/components/jQuery-contextMenu/dist/ https://raw.githubusercontent.com/swisnl/jQuery-contextMenu/a7a1b9f3b9cd789d6eb733ee5e7cbc6c91b3f0f8/dist/jquery.contextMenu.min.js.map
wget -P ./public/lib/components/jQuery-contextMenu/dist/ https://raw.githubusercontent.com/swisnl/jQuery-contextMenu/a7a1b9f3b9cd789d6eb733ee5e7cbc6c91b3f0f8/dist/jquery.contextMenu.min.css.map
```

* Remove broken symbolic links

```shell
find . -xtype l -exec rm {} \;
```

* Set a version number to skip typing over and over
```shell
read -p "Enter Ampache Version: " a_version
```

* Create a zip package named "ampache-5.x.x_all.zip and add the entire ampache directory tree. (excluding git/development specific files)

```shell
rm ../ampache-${a_version}_all.zip & zip -r -q -u -9 --exclude=./.git/* --exclude=./.github/* --exclude=./.tx/* --exclude=./.idea/* --exclude=.gitignore --exclude=.gitattributes --exclude=.scrutinizer.yml --exclude=CNAME --exclude=.codeclimate.yml --exclude=.php* --exclude=.tgitconfig --exclude=.travis.yml ../ampache-${a_version}_all.zip ./
```

* Then unpack the exact zip and create a server to test basic functionality

```shell
rm -rf /var/www/html && unzip -o ../ampache-${a_version}_all.zip -d /var/www/html/
```

* FIXME This might be where unit testing would be helpful.
* Draft Release page online and save as draft using your changelog
* When drafting a new release, set the tag to the version **5.x.x** and target to the **release5** branch.
* The "ampache-*.*.*_all.zip" drop-in package is then uploaded.
* After setting version and title, save as draft
* Push your waiting update to master
* Publish the new release
  * get the md5hash for the release page

```shell
md5sum ../ampache-${a_version}_all.zip
```

## Post release

* Update develop from master **don't push**
* Set the next version in init.php back to 'develop' and update the changelog
* Commit and push "Begin Ampache develop"

## Additional requirements

* Update ampache-docker README.md with the current version. ~~(This will kick off a build with the new version)~~
* Update config file in docker (ampache.cfg.php.dist)
* Update and make a release for python3-ampache following the version with a build (5.x.x-1)
  * run build_docs.py to update the example files
* Create a new release on GitHub to automatically push to [PyPI](https://pypi.org/project/ampache/).

## Update ampache-docker images on docker hub

Update the official Ampache docker images [<https://hub.docker.com/r/ampache/ampache>]

* To bump ampache-docker images rebuild for arm and amd64 using buildx [<https://github.com/docker/buildx>]
* After enabling experimental mode I installed the tools and buildx container.
* Make sure the docker API is [accessible](https://success.docker.com/article/how-do-i-enable-the-remote-api-for-dockerd)

This part should only be needed once.
It creates a local builder that can build the other CPU architectures.

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

To update master and nosql; add the latest zip file to the docker images
```Dockerfile
    &&  wget -q -O /tmp/master.zip https://github.com/ampache/ampache/releases/download/5.x.x/ampache-5.x.x_all.zip \
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

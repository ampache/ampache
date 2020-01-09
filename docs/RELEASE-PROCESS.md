# Ampache Release Guide

## Official Release Process

It's easy to use a program like github desktop to compare between branches.
**Use Linux**

* Open Github Desktop and pull latest develop / master
* Change to master branch
* Go to Branch Menu -> Update from develop
* Fix merge issues
  * lib/init.php (Set release version)
  * docs/CHANGELOG.md (Update for release)
* Commit merge but do not push!
* Undo commits and tag for new version (e.g. 4.1.0)
* Browse changes to check for things you've missed in the changelog
* Run composer install
* Remove broken symbolic links

```shell
  find . -xtype l -exec rm {} \;
```

* Create a zip package named "ampache-*.*.*_all.zip and add the entire ampache directory tree. (excluding git/development specific files)

```shell
  cd ampache
  zip -r -q -u -9 --exclude=./.git/* --exclude=./.github/* --exclude=./.tx/* --exclude=.gitignore --exclude=.gitattributes --exclude=.scrutinizer.yml  --exclude=.tgitconfig --exclude=.travis.yml ../ampache-4.1.0_all.zip ./
```

* Then unpack the exact zip and create a server to test basic functionality
  * FIXME This might be where unit testing would be helpful.
* Draft Release page online and save as draft using your changelog
* When drafting a new release, set target to master branch.
* The "ampache-*.*.*_all.zip" drop-in package is then uploaded.
* After setting version and title, save as draft
* Commit your waiting update to master
* Publish the new release
  * get the md5hash for the release page

```shell
md5sum ../ampache-4.1.0_all.zip 
```

## Additional requirements

* Update ampache-docker README.md with the current version. (This will kick off a build with the new version)
* Update config file in docker (ampache.cfg.php.dist) if it's changed as well
* Update and make a release for python3-ampache if api has changed
  * Use a test file (test.py?) for some basic API function.
  * FIXME what should it test?

## Update ampache-docker images on docker hub

Update the official Ampache docker images [<https://hub.docker.com/r/ampache/ampache>]

* To bump ampache-docker images simply update the readme file with the new version number. Docker hub will detect a change and rebuild the image. (This was broken for a while for unknown reasons)

## Update github docker packages

Packages are also published directly on github [<https://github.com/ampache/ampache-docker/packages>]

* Authenticate

``` shell
docker login docker.pkg.github.com --username lachlan-00
```

* Step 2: Build the master image

``` shell
git clone -b master https://github.com/ampache/ampache-docker.git ampache-docker-master/
cd ampache-docker-master/
docker tag ampache/ampache docker.pkg.github.com/ampache/ampache-docker/ampache:latest
docker build -t docker.pkg.github.com/ampache/ampache-docker/ampache:latest .
```

* Update the package

``` shell
docker push docker.pkg.github.com/ampache/ampache-docker/ampache:latest
```

* Do the same process for develop every month or so

``` shell
git clone -b develop https://github.com/ampache/ampache-docker.git ampache-docker-develop/
cd ampache-docker-develop/
docker tag ampache/ampache-develop docker.pkg.github.com/ampache/ampache-docker/ampache:develop
docker build -t docker.pkg.github.com/ampache/ampache-docker/ampache:develop .
docker push docker.pkg.github.com/ampache/ampache-docker/ampache:develop
```

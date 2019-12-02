# Ampache Release Guide

## Official Release Process

It's easy to use a program like github desktop to compare between branches.
**Use Linux**

* Open Github Desktop and pull latest develop / master
* Change to master branch
* Go to Branch Menu -> Update from develop
* Fix Merge issues
  * lib/init.php (Set release version)
  * docs/CHANGELOG.md (Update for release)
* Commit merge but do not push!
* Undo commits and tag for new version (e.g. 4.0.4)
* Browse changes to check for things you've missed in the changelog
* Run composer install
* Remove broken symbolic links

```shell
  find . -xtype l -exec rm {} \;
```

* Create a zip package named "ampache-*.*.*_all.zip and add the entire ampache directory tree. (excluding git/development specific files)

```shell
  cd ampache
  zip -r -q -u -9 --exclude=./.git/* --exclude=./.github/* --exclude=./.tx/* --exclude=.gitignore --exclude=.gitattributes --exclude=.scrutinizer.yml  --exclude=.tgitconfig --exclude=.travis.yml ../ampache-4.0.4_all.zip ./
```

* Then unpack the exact zip and create a server to test basic functionality
  * FIXME This might be where unit testing would be helpful.
* Draft Release page online and save as draft using your changelog
* When drafting a new release, set target to master branch.
* The "ampache-*.*.*_all.zip" drop-in package is then uploaded.
* After setting version and title, save as draft
* Commit your waiting update to master
* Publish the new release


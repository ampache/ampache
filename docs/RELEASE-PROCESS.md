# Ampache Release Guide

## Wagnerd's process

1. Merge/squash develop branch with master. (Local in git desktop don't upload)
  * composer install
2. Create a zip package named "ampache-*.*.*_all.zip and add the entire ampache directory tree.
3. From the zip file, I then removed all files that are used for installation/development and are not necessary for ampache to function.
  * Folders /.git/ /.github/ /.tx/
  * Files /.gitignore /.scrutinizer.yml /.tgitconfig /.travis.yml
4. I then unpacked the zip into a separate path, created a server pointing to that path and tested basic functionality. This might be where unit testing would be helpful.
5. When drafting a new release, set target to master branch. When publishing, github will add a reference to the two compressed packages (zip and tar.gz) which will need to be installed.
6. The "ampache-*.*.*_all.zip" drop-in package is then uploaded.
7. After setting version and title, it should be ready to publish.


# Ampache and docker

Make sure you check out the pre-built docker images first!

https://github.com/ampache/ampache-docker

This is used to help set up a dev environment for people working on the code more than a user.

## Ampache docker testing

Want a live docker environment on your system?

There are a few docker files I've been using to build and test that have helped out a lot.

Anyone with a bit of docker knowledge should be able to get up and running but here's the gist.

* Copy the docker-compose.yml file to the root of your Ampache directory
* Edit the file if you want to run a different dockerfile or change the volume directories
* Run `docker-compose up` to start the container
* Don't forget your composer install

After you're up and running you should be able to install as normal. (MySQL is not included in the docker files)

## Setup file permissions

If you use Linux, the permissions can be set up by running the setup script from the docker directory

```
cd ./docker
sh ./docker-setup.sh
```

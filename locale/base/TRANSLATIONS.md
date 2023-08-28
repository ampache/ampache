# TRANSLATIONS - Ampache Translation Guide

The official way to send in translations is via [Transifex](https://www.transifex.com/ampache/ampache/dashboard/).

* The official source language of Ampache is US English.
* Strings should only be translated where they differ from the source language.
* If a translation is not available, Ampache will fall back to US English.

## Introduction

Ampache uses gettext to handle the translation between different languages.
If you are interested in translating Ampache into a new language or updating
an existing translation please join us on Transifex.

Benifits to using the Transifex platform include:

* Everything is managed in a central location.
* Translations are updated in a single commit without conflicts.
* The current translation state is available to the team to understand the status of each language.

## Questions

If you have further questions, please feel free to open an issue here or start a new thread on our GoogleGroups forum and ask for @Psy-Virus - The Translation Guy.

Thanks and happy localizing!

## gather-messages.sh

To update the repository, Ampache uses a gettext script called `gather-messages.sh`

To use; cd to the base dir

```
cd locale/base
```

Then gather the new messages

```
./gather-messages.sh -g
```

This will generate the pot file for upload to the repo. This is needed to allow Transifex users time to translate things.

## Transifex Client

To configure and use translations you need to have access tothe project and an API token to use for the client.

https://developers.transifex.com/docs/cli

### Basic Use

You can install the latest Transifex CLI by executing:

```
curl -o- https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash
```

Now migrate your old config if you had it installed previously

```
tx migrate
```

With the cli tool you can pull the changes to the messages with

```
tx pull -f
```

Here is an example of my migrated config file (~/.transifexrc) which allows me to pull the translations

```
[https://www.transifex.com]
api_hostname  = https://api.transifex.com
hostname      = https://www.transifex.com
username      = api
password      = 1/2345675623876238476103450278634925761291
rest_hostname = https://rest.api.transifex.com
token         = 1/2345675623876238476103450278634925761291
```

Finally, to build all the mo files which Ampache uses you need to install gettext

Now you can forcibly rebuild all the mo files with this command

```
bash gather-messages.sh -fa
```


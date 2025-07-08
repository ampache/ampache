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

```shell
cd locale/base
```

Then gather the new messages

```shell
./gather-messages.sh -g
```

This will generate the pot file for upload to the repo. This is needed to allow Transifex users time to translate things.

## Find duplicate strings

Because updating untranslated-strings.txt is a manual process there is room for mistakes

check for dupes using grep.

```shell
grep -E '^msgid "[^"]+"$' messages.pot | sort | uniq -d -w 100 | awk '{print "Duplicate entry:", $0}'
```

## Transifex Client

To configure and use translations you need to have access tothe project and an API token to use for the client.

https://developers.transifex.com/docs/cli

### Basic Use

You can install the latest Transifex CLI by executing:

```shell
curl -o- https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash
```

Now migrate your old config if you had it installed previously

```shell
tx migrate
```

With the cli tool you can pull the changes to the messages with

```shell
tx pull -f
```

Here is an example of my migrated config file (~/.transifexrc) which allows me to pull the translations

```text
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

```shell
cd base/locale
bash gather-messages.sh -fa
```

### Gathering new translations

Generate a new messages.pot using the generate command.

This file is used by Transifex to generate the messages for each language file.

```shell
cd base/locale
bash gather-messages.sh -g
```

You can regenerate database strings by adding `u`

```shell
cd base/locale
bash gather-messages.sh -gu
```

Check for duplicate message id's in the pot

```shell
sort messages.pot | uniq -d | grep msgid
```

msgid "" is the only dupe you want to see. (This is used for multiline translations)

I used a regex to delete the dupes, alter to match your issues. Dupes will generally come from these generated files.

```txt
\n+#:\s*(?:Database preference subcategory table id [0-9]+|\.\/untranslated-strings\.txt:[0-9]+)\nmsgid "(?:backend|browse|catalog|custom|feature|home|httpq|lastfm|library|localplay|mpd|metadata|notification|player|podcast|privacy|query|share|Show current song in Web Player page title|sidebar|theme|transcoding|update|upload)"\nmsgstr "[^"]*"
```


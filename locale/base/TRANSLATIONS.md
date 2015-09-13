# TRANSLATIONS - Ampache Translation Guide

## Introduction
Ampache uses gettext to handle the translation between different languages.
If you are interested in translating Ampache into a new language or updating
an existing translation, simply follow the instructions provided below.

### A) Getting the Necessary Tools

Since 2015, you can contribute to Ampache's translation without any technical
knowledge or tools, using the online translation platform
[**Transifex**](https://www.transifex.com/ampache/ampache).

Otherwise if you don't want to use the online translation platform,
you should contact us, before you start translating Ampache into a new language,
on IRC (chat.freenode.net #ampache), just to make sure that nobody else is already working on a translation.
Once you are ready to start your translation you will need to get a few tools:

 - [Gettext](http://www.gnu.org/software/gettext/)
 - xgettext (Generates PO files)
 - msgmerge (Merges old and new PO files)
 - msgfmt (Generates the MO file from a PO file)

### B) Quick Reference
Below are all of the commands listed you may have to run when working on a translation.

#### Gather All info
	./gather-messages.sh --all

#### Create New po file
	LANG=YOURLANG ./gather-messages.sh --init

Example:
*LANG=ja_JP.UTF-8 ./gather-messages.sh --init*
locale/ja_JP/LC_MESSAGES/messages.po will create.

#### Merge with existing po file
	./gather-messages.sh --merge

#### Combine Old & New po files
	msgmerge old.po messages.po --output-file=new.po

#### Generate MO file for use by gettext
	./gather-messages.sh --format

## Creating a New Translation

### A) Translating
We do our best to keep an up to date POT file in /locale/base feel free to 
use this file rather than attempting to generate your own. If you would 
like to gather a new POT file simply run /locale/base/gather-messages.sh 
(Linux only)

Once you have an up to date POT file you will need to figure out the 
country code for the language you are translating into. There are many
[lists](http://www.gnu.org/software/gettext/manual/html_chapter/gettext_16.html)
on the web.

Create the following directory structure and put your po file in the 
LC_MESSAGES directory */locale/<COUNTRY CODE>/LC_MESSAGES/*

Start Translating!

### B) Creating a MO File
Once you have finished translating the PO file, you need to convert it into
a MO file, so Gettext is able to use it.
Simply run the command listed below.

	msgfmt <DIR>messages.po -o <DIR>/messages.mo

Unfortunately, currently Ampache doesn't automatically detect new languages
and thus you have to edit the code directly, so Ampache can pickup your
new language.
Find /lib/preferences.php and then find "case 'lang':" under
the "create_preference_input" function and add a line for your own 
language. For example to add en_US support add the following line

```php
echo "\t<option value=\"en_US\" $en_US_lang>" . T_("English") . "</option>\n";
```

Make sure that it comes after the `<select>` statement. This will be fixed
for future releases... Sorry :S

## Updating an Existing Translation

### A) Merging existing file
If you are updating an existing PO file you will need to merge your new
created file with the old, existing file. So you don't have to do everything over again. 
Simply run the following command. 

	msgmerge old.po messages.po --output-file=new.po

Once you have created the new PO file, translate it as you normally would.

Hint: [Poedit](https://poedit.net/) is a great, GUI based, cross platform tool to handle this.
It also generates the MO file while you save your work.

### B) Generating the MO file
As this is an existing translation, you do not have to modify Ampache's
code at all. Simply run:

	msgfmt <DIR>messages.po -o <DIR>messages.mo

And then check it within the web interface!

## Questions:
If you have any questions or if you are unable to get gettext to work, please
feel free to contact us.
Thanks!

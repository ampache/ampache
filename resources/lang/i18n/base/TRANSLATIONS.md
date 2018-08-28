# TRANSLATIONS - Ampache Translation Guide

## Introduction
Ampache uses gettext to handle the translation between different languages.
If you are interested in translating Ampache into a new language or updating
an existing translation, simply follow the instructions provided below.

Since 2015, you can contribute to Ampache's translation without any technical
knowledge or tools, using the online translation platform
[**Transifex**](https://www.transifex.com/ampache/ampache).
It is also the recommended Method to translate Ampache.

### If you can't use Transifex, the following is for you. You'll need at least some terminal skills. ;-)
### A) Getting the Necessary Tools

If you **can't** use the online translation platform,
you should contact us, **before** you start translating Ampache into a new language,
on IRC (chat.freenode.net #ampache), just to make sure that nobody else is already working on a translation.
Once you are ready to start your translation you will need to get a few tools:

 - [Gettext](http://www.gnu.org/software/gettext/)
 - xgettext (Generates PO files)
 - msgmerge (Merges old and new PO files)
 - msgfmt (Generates the MO file from a PO file)

### B) Quick Reference
Below are all of the commands listed you may have to run when working on a translation.

#### Gather All info
+ $ ./gather-messages.sh [-a|--all]
	+ This command gathers everything that is translatable from Ampache's source code and creates the messages.pot file, the main translation catalog in /Ampache-dir/locale/base/.
+ $ ./gather-messages.sh [-au|--allutds]
	+ If you have a fully set up Ampache, you can use this command to also gather translatable strings from the database.

#### Create New po file
+ LANG=YOURLANG ./gather-messages.sh --init

Example:
+ LANG=en_US.UTF-8 ./gather-messages.sh --init
	+ will create /Ampache-Dir/locale/en_US/LC_MESSAGES/messages.po

#### Merge with existing po file
+ ./gather-messages.sh --merge
	+ This command merges an existing /Ampache-Dir/locale/base/messages.pot with your language file, specified before.

#### Combining/merge Old & New po files
+ msgmerge old.po messages.po --output-file=new.po
	+ With this command, you can simply merge two .po files together. You also can directly overwrite the existing messages.po by the merged one, by simply use messages.po instead of new.po.

#### Generate MO file for use by gettext
+ ./gather-messages.sh --format
	+ After you finished your translations, you have to fomrat or compile your translation catalog. This will create a binary file, a messages.mo file in the directory, you worked.

## Creating a New Translation

### A) Translating
We do our best to keep an up to date POT file in /locale/base feel free to 
use this file rather than attempting to generate your own. If you would 
like to gather a new POT file simply run one of the above "Gather All info" commands. (Linux only)

Once you have an up to date POT file you will need to figure out the 
country code for the language you are translating into. There are many
[lists](https://www.gnu.org/software/gettext/manual/html_chapter/gettext_16.html#Language-Codes)
on the web.

Create the following directory structure and put your po file in the 
LC_MESSAGES directory.
+ /Ampache-Dir/locale/lang code_COUNTRY CODE/LC_MESSAGES/
	+ Example: /Ampache-Dir/locae/en_US/LC_MESSAGES/
	+ Please keep in mind, that the language and country codes are case sensitive.

Start Translating!

### B) Creating a MO File
Once you have finished translating the PO file, you need to convert it into
a MO file, so Gettext is able to use it.
Simply run the command listed below.

	msgfmt /somedir/messages.po -o /somedir/messages.mo

Unfortunately, currently Ampache doesn't automatically detect new languages
and thus you have to edit the code directly, so Ampache can pickup your
new language.
+ Find /lib/general.lib.php and then find "function get_languages()" @ around line 150.
+ Create a new case with your language. You can use the other cases as examples.
+ Please pay attention to the case order. It's ordered alphabetically by the lang codes. ;-)

Example for adding en_US language:

```php
case 'en_US'; $name = 'English (US)'; break; /* English */
```

## Updating an Existing Translation

### A) Merging an existing file
If you are updating an existing PO file you will need to merge your new
created file with the old, existing file. So you don't have to do everything over again. 
Simply run the following command. 

	msgmerge old.po messages.po --output-file=new.po

Once you have created the new PO file, translate it as you normally would.

Hint: [Poedit](https://poedit.net/) is a great, GUI based, cross platform tool to handle this.
It also generates the MO file while you save your work.

### B) Generating the MO file.
As this is an existing translation, you do not have to modify Ampache's
code at all. Simply run:

	msgfmt /somedir/messages.po -o /somedir/messages.mo

And then check it within the web interface

## Questions:
If you have further questions, please feel free to open an issue here or start a new thread on our GoogleGroups forum and ask for @Psy-Virus - The Translation Guy.

Thanks and happy localizing!

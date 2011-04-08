PHP-gettext 1.0 (https://launchpad.net/php-gettext)

Copyright 2003, 2006, 2009 -- Danilo "angry with PHP[1]" Segan
Licensed under GPLv2 (or any later version, see COPYING)

[1] PHP is actually cyrillic, and translates roughly to
    "works-doesn't-work" (UTF-8: Ради-Не-Ради)


Introduction

    How many times did you look for a good translation tool, and
    found out that gettext is best for the job? Many times.

    How many times did you try to use gettext in PHP, but failed
    miserably, because either your hosting provider didn't support
    it, or the server didn't have adequate locale? Many times.

    Well, this is a solution to your needs. It allows using gettext
    tools for managing translations, yet it doesn't require gettext
    library at all. It parses generated MO files directly, and thus
    might be a bit slower than the (maybe provided) gettext library.

    PHP-gettext is a simple reader for GNU gettext MO files. Those
    are binary containers for translations, produced by GNU msgfmt.

Why?

    I got used to having gettext work even without gettext
    library. It's there in my favourite language Python, so I was
    surprised that I couldn't find it in PHP. I even Googled for it,
    but to no avail.

    So, I said, what the heck, I'm going to write it for this
    disguisting language of PHP, because I'm often constrained to it.

Features

  o Support for simple translations
    Just define a simple alias for translate() function (suggested
    use of _() or gettext(); see provided example).

  o Support for ngettext calls (plural forms, see a note under bugs)
    You may also use plural forms. Translations in MO files need to
    provide this, and they must also provide "plural-forms" header.
    Please see 'info gettext' for more details.

  o Support for reading straight files, or strings (!!!)
    Since I can imagine many different backends for reading in the MO
    file data, I used imaginary abstract class StreamReader to do all
    the input (check streams.php). For your convenience, I've already
    provided two classes for reading files: FileReader and
    StringReader (CachedFileReader is a combination of the two: it
    loads entire file contents into a string, and then works on that).
    See example below for usage. You can for instance use StringReader
    when you read in data from a database, or you can create your own
    derivative of StreamReader for anything you like.


Bugs

    Report them on https://bugs.launchpad.net/php-gettext

Usage

    Put files streams.php and gettext.php somewhere you can load them
    from, and require 'em in where you want to use them.

    Then, create one 'stream reader' (a class that provides functions
    like read(), seekto(), currentpos() and length()) which will
    provide data for the 'gettext_reader', with eg.
      $streamer = new FileStream('data.mo');

    Then, use that as a parameter to gettext_reader constructor:
      $wohoo = new gettext_reader($streamer);

    If you want to disable pre-loading of entire message catalog in
    memory (if, for example, you have a multi-thousand message catalog
    which you'll use only occasionally), use "false" for second
    parameter to gettext_reader constructor:
      $wohoo = new gettext_reader($streamer, false);

    From now on, you have all the benefits of gettext data at your
    disposal, so may run:
      print $wohoo->translate("This is a test");
      print $wohoo->ngettext("%d bird", "%d birds", $birds);

    You might need to pass parameter "-k" to xgettext to make it
    extract all the strings. In above example, try with
      xgettext -ktranslate -kngettext:1,2 file.php
    what should create messages.po which contains two messages for
    translation.

    I suggest creating simple aliases for these functions (see
    example/pigs.php for how do I do it, which means it's probably a
    bad way).


Usage with gettext.inc (standard gettext interfaces emulation)

    Check example in examples/pig_dropin.php, basically you include
    gettext.inc and use all the standard gettext interfaces as
    documented on:

       http://www.php.net/gettext

    The only catch is that you can check return value of setlocale()
    to see if your locale is system supported or not.


Example

    See in examples/ subdirectory. There are a couple of files.
    pigs.php is an example, serbian.po is a translation to Serbian
    language, and serbian.mo is generated with
       msgfmt -o serbian.mo serbian.po
    There is also simple "update" script that can be used to generate
    POT file and to update the translation using msgmerge.

TODO:

  o Improve speed to be even more comparable to the native gettext
    implementation.

  o Try to use hash tables in MO files: with pre-loading, would it
    be useful at all?

Never-asked-questions:

  o Why did you mark this as version 1.0 when this is the first code
    release?

    Well, it's quite simple. I consider that the first released thing
    should be labeled "version 1" (first, right?). Zero is there to
    indicate that there's zero improvement and/or change compared to
    "version 1".

    I plan to use version numbers 1.0.* for small bugfixes, and to
    release 1.1 as "first stable release of version 1".

    This may trick someone that this is actually useful software, but
    as with any other free software, I take NO RESPONSIBILITY for
    creating such a masterpiece that will smoke crack, trash your
    hard disk, and make lasers in your CD device dance to the tune of
    Mozart's 40th Symphony (there is one like that, right?).

  o Can I...?

    Yes, you can. This is free software (as in freedom, free speech),
    and you might do whatever you wish with it, provided you do not
    limit freedom of others (GPL).

    I'm considering licensing this under LGPL, but I *do* want
    *every* PHP-gettext user to contribute and respect ideas of free
    software, so don't count on it happening anytime soon.

    I'm sorry that I'm taking away your freedom of taking others'
    freedom away, but I believe that's neglible as compared to what
    freedoms you could take away. ;-)

    Uhm, whatever.

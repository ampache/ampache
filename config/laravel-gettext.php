<?php

return [
    /**
     * Session identifier: Key under which the current locale will be stored.
     */
    'session-identifier' => 'laravel-gettext-locale',

    /**
     * Default locale: this will be the default for your application.
     * Is to be supposed that all strings are written in this language.
     */
    'locale' => 'en_US',

    /**
     * Supported locales: An array containing all allowed languages
     */
    'supported-locales' => [
        'en_US',
        'fr_FR',
    ],

    /**
     * Default charset encoding.
     */
    'encoding' => 'UTF-8',

    /**
     * -----------------------------------------------------------------------
     * All standard configuration ends here. The following values
     * are only for special cases.
     * -----------------------------------------------------------------------
     **/

    /**
     * Base translation directory path (don't use trailing slash)
     */
    'translations-path' => '../resources/lang',

    /**
     * Relative path to the app folder: is used on .po header files
     */
    'relative-path' => '../../../../../app',

    /**
     * Fallback locale: When default locale is not available
     */
    'fallback-locale' => 'en_US',

    /**
     * Default domain used for translations: It is the file name for .po and .mo files
     */
    'domain' => 'messages',

    /**
     * Project name: is used on .po header files
     */
    'project' => 'Ampache',

    /**
     * Translator contact data (used on .po headers too)
     */
    'translator' => 'Afterster <afterster@gmail.com>',

    /**
     * Paths where PoEdit will search recursively for strings to translate.
     * All paths are relative to app/ (don't use trailing slash).
     *
     * Remember to call artisan gettext:update after change this.
     */
    'source-paths' => [
        'Http/Controllers',
        '../resources/views',
        'Console/Commands',
    ],

    /**
     * Multi-domain directory paths. If you want the translations in
     * different files, just wrap your paths into a domain name.
     * Paths on top-level will be associated to the default domain file,
     * for example:
     */
    /*
    'source-paths' => [
		'frontend' => [
			'controllers',
			'views/frontend',
		],
		'backend' => [
			'views/backend',
		],
		'storage/views',
	],
    */

    /**
     * Sync laravel: A flag that determines if the laravel built-in locale must
     * be changed when you call LaravelGettext::setLocale.
     */
    'sync-laravel' => true,

    /**
     * Use custom locale that is not supported by the system
     */
    'custom-locale' => false,

    /**
     * The keywords list used by poedit to search the strings to be translated
     *
     * The "_", "__" and "gettext" are singular translation functions
     * The "_n" and "ngettext" are plural translation functions
     *
     * "__" and "_n" are helpers functions @see \Xinax\LaravelGettext\Support\helpers.php
     */
    'keywords-list' => ['_', '__', 'gettext', '_n:1,2', 'ngettext:1,2'],
];

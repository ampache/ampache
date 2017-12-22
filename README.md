 ![Logo](http://ampache.org/img/logo/ampache-logo_x64.png) Ampache/Laravel
=======
[www.ampache.org](http://ampache.org/) |
[ampache.github.io](http://ampache.github.io)

Requirements
------

In addition to Composer:

1. nodejs: Found at https://nodejs.org/en/ or possible in your distribution's repository.

Nodejs includes npm.


Installation
------------

1. Run `php composer.phar install`
2. Run `npm install`  to install required nodejs packages.
3. Run `npm run dev` to process the contents of webpack.mix.js.
4. Copy .env.example to .env
5. Run `php artisan key:generate`.
6. In the .env file replace the entries for
6. Run `php artisan migrate:install` to install the tables

Artisan can be used as a development web server: Running `php artisan serve` in document root, defaults to 127.0.0.1 and port 8000.
Also `php -S host:port` can be run  from the ./public folder.

To get a list of commands: `php artisan list`.


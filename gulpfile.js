var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('app.scss');
    
    mix.copy('resources/assets/images', 'public/images')
        .copy('components/bootstrap/fonts', 'public/fonts')
        .copy('resources/assets/fonts', 'public/fonts')
        .copy('resources/themes/reborn/assets', 'public/themes/reborn');

    mix.scripts([
            'components/jquery/jquery.min.js',
            'components/jquery-ui/jquery-ui.min.js',
            'components/prettyphoto/js/jquery.prettyPhoto.js',
            'components/tag-it/js/tag-it.js',
            'vendor/needim/noty/js/noty/packaged/jquery.noty.packaged.min.js',
            'components/jquery-cookie/jquery.cookie.js',
            'components/jscroll/jquery.jscroll.min.js',
            'components/jquery-qrcode/src/jquery.qrcode.js',
            'components/responsive-elements/responsive-elements.js',
            'components/datetimepicker/jquery.datetimepicker.js',
            'components/jQuery-Knob/js/jquery.knob.js',
            'components/jQuery-File-Upload/js/jquery.iframe-transport.js',
            'components/jQuery-File-Upload/js/jquery.fileupload.js',
            'components/jQuery-contextMenu/dist/jquery.contextMenu.js',
            'resources/assets/js/**/*.js'
        ], 'public/js/vendors.js', './')
        .scripts([
            'resources/assets/js/*.js'
        ], 'public/js/main.js', './')
        .styles([
            'resources/assets/css/**/*.css',
            'components/prettyphoto/css/prettyPhoto.css',
            'components/jstree/dist/themes/default/style.min.css',
            'components/tag-it/css/jquery.tagit.css',
            'components/datetimepicker/jquery.datetimepicker.css',
            'components/jQuery-contextMenu/dist/jquery.contextMenu.min.css',
        ], 'public/css/vendors.css', './')
        .styles([
            'resources/assets/css/*.css',
        ], 'public/css/main.css', './');

    mix.version(['css/vendors.css', 'css/app.css', 'css/main.css', 'js/vendors.js', 'js/main.js']);

    if (process.env.NODE_ENV !== 'production') {
        mix.browserSync({
            proxy: 'ampache.dev',
            files: [
                elixir.config.get('public.css.outputFolder') + '/**/*.css',
                elixir.config.get('public.versioning.buildFolder') + '/rev-manifest.json',
            ]
        });
    }
});

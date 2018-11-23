<!DOCTYPE html>
<html>
<head>
    <!-- Propulsed by Ampache | ampache.org -->
     <title><?php echo(__("Ampache error page"));?></title>
</head>
<body>
<h2>{{ $exception->getMessage() }}</h2>
<br>
<br>
<h2><pre>Ampache requires initialization. Run

     <mark>php artisan ampache:install</mark>

from a commandline</pre></h2>

</body>


<!DOCTYPE html>
<html lang="en-US">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ampache -- Debug Page</title>
        <link rel="icon" href="./public/client/favicon.ico">
        <link rel="stylesheet" href="./public/client/lib/components/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="./public/client/templates/install.css" type="text/css" media="screen">
    </head>
    <body>
        <div class="navbar navbar-dark navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container" style="height: 70px;">
                <a class="navbar-brand" href="#">
                    <img src="./public/client/images/ampache-dark.png" title="Ampache" alt="Ampache">
                    Ampache :: For the Love of Music                </a>
            </div>
        </div>
        <div id="guts" class="container" role="main">
            <div class="jumbotron" style="margin-top: 70px">
                <h1>Warning</h1>
                <p>The root Ampache folder has changed to <a href="./public" target="_blank">./public</a></p>
            </div>
            <div class="alert alert-danger">
                <p>You must update your DocumentRoot to the new path</p>
                <p><a href="https://github.com/ampache/ampache/wiki/Ampache-Next-Changes" target="_blank">Please check the Ampache wiki for more information</a></p>
            </div>
        </div>
    </body>
</html>

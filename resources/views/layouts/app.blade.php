<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <title>{{ config('app.name', 'Ampache') }}</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="{{ url('favicon.png') }}">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ url('css/dark.css') }}">
    <link rel="stylesheet" href="{{ url('css/jquery-ui.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/jquery-validate.js') }}"></script>
    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.js') }}"></script>
    <script src="{{ asset('js/dynamicpage.js') }}"></script>
    <script src="{{ asset('js/moment.js') }}"></script>
    <script src="{{ asset('js/validate.js') }}"></script>
 
     {!! $cat_types !!}
 </head>
<body class="w3-laravel-body w3-padding-48">
 <div id="dialog-confirm" title="Current Catalog Operation:">
   <p id="alert"><span class="alert" style="float:left; margin:12px 12px 20px 0;">
   </span></p>
 </div>
 
      @section('header')
             @include('partials.header')
      @show

	  @section('sidebar')
		Reserved for including the sidebar.
		@include('partials.sidebar.main')
	  @show
	  
	<div id="main-content" class="w3-container" style="margin-left:13%">
		@yield('content')
	</div>
 <div id="dialog-edit"></div>
<script>
function dialogEdit(id, name, path, dialogAction)
{
    switch (dialogAction)
    {
    case 'catalog-create':
        var url = "{{ url('/catalogs') }}";
        $("#dialog-edit").dialog( "option", "title", "Creating New Catalog" );
        $("#dialog-edit").load("{{ url('catalogs/create') }}" + ' #catalog_create');
        break;
    case 'catalog-edit':
        var url = "{{ url('/catalogs') }}" + '/' + id;
        var title = name + "("+ path + ")";
        $("#dialog-edit").dialog( "option", "title", title );
        $("#dialog-edit").load("{{ url('catalogs/edit') }}/" + id + ' #catalog-edit');
        break;
    case 'user-register':
        var url = "{{ route('register') }}";
        var title = "Registering New User:"
        $("#dialog-edit").dialog( "option", "title", title );
        $("#dialog-edit").load("{{ url('register') }}/" + ' #user-register');
        break;
    case 'user-login':
        var url = "{{ route('login') }}";
        var title = "User Logging In:"
        $("#dialog-edit").dialog( "option", "title", title );
        $("#dialog-edit").load("{{ url('login') }}/" + ' #user-login');
        break;
    default:
        
    }
    $( "#dialog-edit").data("url", url);
    $("#dialog-edit").data("action", dialogAction);
    $( "#dialog-edit" ).dialog( "open" );
    
}

$( "#dialog-edit").dialog({
    autoOpen: false,
    modal: true,
    resizable: false,
    height: "auto",
    width: "auto",
    buttons: [{
        text: "Save",
        "id": "btnOk",
        click: function () {
            var action = $( this ).data("action");
            var id = $( this ).data("id");
            var url = $( this ).data("url")
            //var t= validate(catalog_name, constraints);
            var t = validate({}, {catalog_name: {presence: {message: "^You must pick a username"}}});
            $.post( url, $( "#edit-form" ).serialize(), function( data, status ) {
               switch (action) {
               case 'catalog-edit':
                   $("#name").text($("#catalog_name").val());
                   break;
               case 'catalog-create':
                   $("#main-content").load("{{ url('/catalogs') }}/" + ' #catalog_show');
                   alert("Catalog was created");
                   break;
               case 'user-register':
                   break;
               case 'user-login':
                   break;
               default:
               }
            });
              $( this ).dialog( "close" );
        },

    }, {
        text: "Cancel",
        "id": "btnCancel",
        click: function () {
 //       $(this).text("");    
        $( this ).dialog( "close" );
       },
    }],
});

</script>
	
</body>
</html>

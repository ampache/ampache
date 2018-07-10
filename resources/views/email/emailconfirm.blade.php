@extends('layouts.app')

@section('content')

<div class=”w3-container”>

<div class=”row”>

<div class=”col-md-8 col-md-offset-2">

<div class=”w3-panel”>panel-default

<div class=”panel-heading”>Registration Confirmed</div>

<div class=”panel-body”>

Your Email is successfully verified. Click here to <a class="w3-text-orange" 
 onclick="dialogEdit('','', '', 'user-login')"><u style="cursor:pointer">login</u></a>

</div>

</div>

</div>

</div>

</div>

@endsection
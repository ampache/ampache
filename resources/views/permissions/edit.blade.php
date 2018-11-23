@extends('layouts.app')

@section('title', '| Edit Permission')

@section('content')
<div id="main-content" class="w3-container w3-section" style="margin-left:14%">
 <div class="w3-display-container w3-black" style="height:500px;">
 <div class="w3-display-middle">

<div>

    <h1><i class='fa fa-key'></i> Edit {{$permission->name}}</h1>
    <br>
    {{ Form::model($permission, array('route' => array('permissions.update', $permission->id), 'method' => 'PUT')) }}{{-- Form model binding to automatically populate our fields with permission data --}}

    <div class="form-group">
        {{ Form::label('name', 'Permission Name') }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>
    <br>
    {{ Form::submit('Edit', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div>
</div>
</div>
</div>
@endsection
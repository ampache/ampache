@extends('layouts.app')

@section('title', '| Edit Role')

@section('content')

<div class='col-lg-4 col-lg-offset-4'>
    <h1><i class='fa fa-key'></i> Edit Role: {{$role->name}}</h1>
    <hr>

    {{ Form::model($role, array('route' => array('roles.update', $role->id), 'method' => 'PUT')) }}

    <div class="form-group">
        {{ Form::label('name', 'Role Name') }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>

    <h5><b>Assign Permissions</b></h5>
          @foreach ($permissions as $permission)
            {{ Form::checkbox('permissions[]',  $permission->id, in_array($permission->name, $p), 
                ['id'=> $permission->id, 'onClick' => 'toggleCheckbox("' . $permission->id . '")' ]) }}
            {{ Form::label($permission->name, ucfirst($permission->name)) }}<br>

        @endforeach
 
    <br>
    {{ Form::submit('Save', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}    
</div>
<script>

function toggleCheckbox(id) {
   var x = document.getElementById(id);
	if (x.value == id) {
	    x.value = "";
	} else {
        x.value = id;
	}
}
</script>
@endsection
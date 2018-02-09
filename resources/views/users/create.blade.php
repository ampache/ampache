{{-- \resources\views\users\create.blade.php --}}
@extends('layouts.app')

@section('title', '| Add User')

@section('content')
<div id="main-content" class="w3-container w3-section" style="margin-left:14%">
 <div class="w3-display-container w3-black" style="height:500px;">
 <div class="w3-display-middle">

<div>

    <h1><i class='fa fa-user-plus'></i> Add User</h1>
    <hr>

    {{ Form::open(array('url' => 'users')) }}
<div class="w3-row w3-small">
  <div class="w3-col" style="width:50px"><i class="w3-xlarge fa fa-user"></i></div>
    <div class="w3-rest">
      <input class="w3-tiny" name="username" type="text" placeholder="User Name">
    </div>
</div>
 <div class="w3-row w3-small">
  <div class="w3-col" style="width:50px"><i class="w3-xlarge fa fa-user-plus"></i></div>
    <div class="w3-rest">
      <input class="w3-tiny" name="name" type="text" placeholder="Full Name">
    </div>
</div>

<div class="w3-row w3-small">
  <div class="w3-col" style="width:50px"><i class="w3-xlarge fa fa-envelope-o"></i></div>
    <div class="w3-rest">
      <input class="w3-tiny" name="email" type="text" placeholder="Email">
    </div>
</div>
 <div class="w3-row w3-small">
  <div class="w3-col" style="width:50px"><i class="w3-xlarge fa fa-home"></i></div>
    <div class="w3-rest">
      <input class="w3-tiny" name="website" type="text" placeholder="Web site">
    </div>
</div>
<div class="w3-container w3-section">
    <div class='form-group'>
        @foreach ($roles as $role)
            {{ Form::checkbox('roles[]',  $role->id ) }}
            {{ Form::label($role->name, ucfirst($role->name)) }}<br>

        @endforeach
    </div>
</div>
 <div class="w3-row w3-small">
  <div class="w3-col" style="width:50px"><i title="Create Password" class="w3-xlarge fa fa-lock"></i></div>
    <div class="w3-rest">
      <input class="w3-tiny" name="password" type="password" placeholder="Password">
    </div>
</div>
 <div class="w3-row w3-small">
  <div class="w3-col" style="width:50px"><i class="w3-xlarge fa fa-id-badge" title="Confirm Password"></i></div>
    <div class="w3-rest">
      <input class="w3-tiny" name="password_confirmation" type="password" placeholder="Confirm Password">
    </div>
</div>

    {{ Form::submit('Add', array('class' => 'btn btn-primary w3-section')) }}

    {{ Form::close() }}

</div>
</div>
</div>
</div>

@endsection
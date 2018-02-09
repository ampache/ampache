

{{-- \resources\views\users\index.blade.php --}}

@extends('layouts.app')

@section('title', '| Users')

@section('content')
 <div id="main" class="w3-container w3-section w3-black" style="margin-left:13%">
  <h4><i class="fa fa-users"></i> User Administration <a href="{{ route('roles.index') }}" class="w3-btn">Roles</a>
   <a href="{{ route('permissions.index') }}" class="w3-button">Permissions</a></h4>
 <table class="w3-table" style="width: 100%">

<thead >
<tr>
<th class="w3-tiny w3-left" style="width: 13.5%;">Full Name(username)</th>
<th class="w3-tiny w3-left" style="width: 13.5%;">Last Seen</th>
<th class="w3-tiny w3-left" style="width: 13.5%;">Registration Date</th>
<th class="w3-tiny w3-left" style="width: 13.5%;">Activity</th>
<th class="w3-tiny w3-left" style="width: 13.5%;">Following</th>
<th class="w3-tiny w3-left" style="width: 13.5%;">Action</th>
<th class="w3-tiny w3-left" style="width: 13.5%;">On Line</th>
</tr>
</thead>

   <tbody>
	@foreach ($users as $user)
    	<tr>
    
    	<td class="w3-tiny w3-left" style="width: 13.5%;">{{ $user->username }}</td>
    	<td class="w3-tiny w3-left" style="width: 13.5%;">{{ $user->updated_at->format('m/d/y H:i') }}</td>
    	<td class="w3-tiny w3-left" style="width: 13.5%;">{{ $user->created_at->format('m/d/y H:i') }}</td>
    	<td class="w3-tiny w3-left" style="width: 13.5%;">{{ "N/A" }}</td>
    	<td class="w3-tiny w3-left" style="width: 13.5%;">{{ "N/A" }}</td>
    	<td class="w3-tiny w3-left" style="width: 13.5%;">
    	<a href="{!! url('/users/create') !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Send private message"
    	    src="{{ asset('images/icon_mail.png') }}" alt="mail"></a> 
    	<a href="{!! url('/users/create') !!}" rel="nohtml">	<img style="width:15px" class="w3-small" title="Edit" 
    	    src="{{ asset('images/icon_edit.png') }}" alt="edit"></a>
  		<a href="{!! url('/users/create') !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Preferences"
  			src="{{ asset('images/icon_preferences.png') }}" alt="preferences"> 
    	<a href="{!! url('/users/create') !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Disable"
    		src="{{ asset('images/icon_disable.png') }}" alt="Disable"></a> 
    	<a href="{!! url('/users/create') !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Delete"
    	    src="{{ asset('images/icon_delete.png') }}" alt="Delete"> 
      	</td>
        <td>
        </td>
        </tr>
     @endforeach
   </tbody>
 </table>
    
    <a href="{{ route('users.create') }}" class="btn btn-success">Add User</a>
    
   </div>
    @endsection
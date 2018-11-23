
<?php use App\Models\User;

?>
{{-- \resources\views\users\index.blade.php --}}

@extends('layouts.app')

@section('title', '| Users')

@section('content')
 <div id="main" class="w3-display-container w3-black">
  <h4><i class="fa fa-users"></i> Users</h4>
 <table id="user_table" class="w3-table" style="width: 100%">

<thead >
<tr>
<th class="w3-small w3-left" style="width: 80px;">Avatar</th>
<th class="w3-small w3-left" style="width: 11.5%;">Username(name)</th>
<th class="w3-small w3-left" style="width: 11.5%;">Last Seen</th>
<th class="w3-small w3-left" style="width: 11.5%;">Registration Date</th>
<th class="w3-small w3-left" style="width: 11.5%;">Activity</th>
<th class="w3-small w3-left" style="width: 11.5%;">Following</th>
<th class="w3-small w3-left" style="width: 11.5%;">Action</th>
<th class="w3-small w3-left" style="width: 11.5%;">On Line</th>
</tr>
</thead>

   <tbody>
	@foreach ($User_ids as $user_id)
	@php
	    $user = User::find($user_id);
	@endphp
    	<tr id="user_{!! $user->id !!}">
    	<td class="w3-small w3-left" style="width: 80px;">
    	@if ($user->avatar !==  false)
    	  <img style="width: 32px; height: 32px;" src= "{{ $user->avatar }}">
    	@endif
    	</td>
    	<td class="w3-small w3-left" style="width: 11.5%;">
    	{{ $user->username }}({{ $user->fullname }})</td>
    	<td class="w3-small w3-left" style="width: 11.4%;">{!! cache('last-activity-' . $user->id) !!}</td>
   	    <td class="w3-small w3-left" style="width: 11.4%;">{!! $user->created_at !!}</td>
    	<td class="w3-small w3-left" style="width: 11.4%;">{{ "N/A" }}</td>
    	<td class="w3-small w3-left" style="width: 11.4%;">{{ "N/A" }}</td>
    	<td class="w3-small w3-left" style="width: 11.4%;">
    	<a href="{!! url('/users/create') !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Send private message"
    	    src="{{ asset('images/icon_mail.png') }}" alt="mail"></a> 
    	<a href="{!! url('/users/edit', $user->id) !!}" rel="nohtml">	<img style="width:15px" class="w3-small" title="Edit" 
    	    src="{{ asset('images/icon_edit.png') }}" alt="edit"></a>
  		<a href="{!! url('/user_preference', $user->id) . '/edit' !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Preferences"
  			src="{{ asset('images/icon_preferences.png') }}" alt="preferences"> 
        @if($user->id != $owner)
    	    <a href="{!! url('/users/disable') !!}" rel="nohtml"><img style="width:15px" class="w3-small" title="Disable"
    		    src="{{ asset('images/icon_disable.png') }}" alt="Disable"></a>
    	    <img onClick="deleteUser({!! $user->id !!},'{!! $user->username !!}')" style="width:15px;cursor: pointer;" class="w3-small" title="Delete"
    	        src="{{ asset('images/icon_delete.png') }}" alt="Delete"> 
       	@endif
       	</td>
        <td class="w3-small w3-left {{ $user->isOnline() ? 'w3-green' : 'w3-red' }}" style="width: 90px;">
           {!!  $user->isOnline() ? 'On Line' : 'Off Line' !!}
        </td>
        </tr>
     @endforeach
   </tbody>
 </table>
 <div id="dialog-confirm"> <div id="alert"></div>
 
    <script>

    $( "#dialog-confirm").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        height: "auto",
        width: 400,
       buttons: [{
            text: "OK",
            "id": "btnOk",
            click: function () {
                var url = $( this ).data("url");
                var id = $( this ).data("id");                
                var rowcount = $('#user_table >tbody >tr').length;
                $.get(url, function(data, status){
                    if (rowcount > 1) {
                        $("#user_" + id).remove();
                     } else {
                         $('#user_table >tbody >tr').eq(0).html("<h3>No Users</h3>");
                     }
                });
                         
                $( this ).dialog( "close" );
            },

        }, {
            text: "Cancel",
            "id": "btnCancel",
            click: function () {
                $( this ).dialog( "close" );
           },
        }],
    });

    
    function deleteUser(id, name)
    {
        var message = "The user will be permanently deleted. Are you sure?";
        $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button:first').show();
        $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button:last').show();
        $("#btnCancel").html('<span class="ui-button-text">'+ "Cancel" +'</span>')

        var url = "{!! url('/users/delete') !!}" + "/" + id;
        document.getElementById("alert").innerHTML = message;
        $( "#dialog-confirm" ).dialog( "option", "title", "Removing user: '" + name + "'" );
        $( "#dialog-confirm" ).data( "id", id );
        $( "#dialog-confirm" ).data( "url", url);
        $( "#dialog-confirm" ).dialog( "open" );
     }

</script>
</div>
@endsection
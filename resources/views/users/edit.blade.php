<!-- \resources\views\users\edit.blade.php -->

@extends('layouts.app')

@section('title', '| Edit User')

@section('content')

<div id="edit-content" class="w3-container w3-section">
 <div class="w3-display-container w3-black" style="height:600px;">
 <div class="w3-display-middle">

    <h1><i class='fa fa-user-plus'></i> Edit User <strong>{{$user->username}}</strong></h1>
    <hr>
    {{ Form::model($user, array('route' => array('users.update', $user->id), 'method' => 'PUT',
     'data-parsley-validate' => '', 'enctype'=>'multipart/form-data')) }}
    {{-- Form model binding to automatically populate our fields with user data --}}
 <table class="w3-table w3-small">
@role('Administrator')  
<tr>
   <td>
        {{ Form::label('username', 'User Name') }}
   </td>
   <td>
        {{ Form::text('username', null, array('class' => "w3-round")) }}
    </td>
    <td>
    </td>
</tr>
@endrole
<tr>
<td>
       {{ Form::label('email', 'Email') }}
</td>
<td>
       {{ Form::email('email', null, array('class' => "w3-round")) }}
 </td>
</tr>
<tr>
<td>
        {{ Form::label('password', 'Password') }}
</td>
<td>
        {{ Form::password('password', array('class' => "w3-round")) }}
</td>
</tr>
<tr>
<td>
        {{ Form::label('password', 'Confirm Password') }}
</td>
<td>
        {{ Form::password('password_confirmation', array('class' => "w3-round")) }}
</td>
</tr>
     @php
          $req_fields = config('user.registration_mandatory_fields');
     @endphp
        @if ($req_fields)
        @foreach ($req_fields as $field)
           @php
             $tokens = explode("_", $field);
             if ($tokens) {
                 $label = title_case(implode(' ', $tokens));
             }
             else {
                 $label = title_case($field);
             }
           @endphp
         <tr>
          <td>
               <label for="{!! $field !!}">{!! $label !!}</label>
          </td>
          <td>
           {{ Form::text($field, null, array('class' => "w3-round")) }}
               @if ($errors->has('{!! $field !!}'))
                   <span class="help-block">
                       <strong>{{ $errors->first($field) }}</strong
                   </span>
               @endif
           </td>
          </tr>
       @endforeach
       @endif 

           @php
          $req_fields = config('user.registration_display_fields');
       @endphp
       
       @if ($req_fields)
        @foreach ($req_fields as $field)
           @php
            if (strstr($field, '_')) {
                 $tokens = explode("_", $field);
                 $label = title_case(implode(' ', $tokens));
             }
             else {
                 $label = title_case($field);
             }
           @endphp
         <tr>
          <td>
               <label for="{!! $field !!}">{!! $label !!}</label>
          </td>
          <td>
               <input id="{!! $field !!}" type="text" class="w3-round" name="{!! $field !!}" value="{{ old($field) }}">
               @if ($errors->has('{!! $field !!}'))
                   <span class="help-block">
                       <strong>{{ $errors->first($field) }}</strong
                   </span>
               @endif
           </td>
          </tr>
         @endforeach
        @endif
    <tr>
    <td>
       <label for="subsonic_password">Subsonic Password</label>        
    </td>
    <td>
        {{ Form::text('subsonic_password', null, array('class' => "w3-round")) }}
    </td>
    
    </tr>
    <tr>
         <td>
            API Key
            @role('Administrator') 
            <i onclick="apiKey({{ $user->id }})" class="fa fa-random w3-margin-left" style="color:orange;cursor:pointer;" title="Generate new API Key"></i>
             <i class="fa fa-trash w3-margin-left" style="color:orange;cursor:pointer;" title="Remove API Key"></i>
            @endrole
         </td>
         <td id="api_key">
         {{ $user->apikey }}
          </td>
         
     </tr>
        @role('Administrator') 
     <tr>
       <td id="user_role">
           User Role
       </td>
       <td>
        @foreach ($roles as $role)
                 {{ Form::checkbox('roles[]',  $role->id, $user->roles ) }}
        
            {{-- Form::checkbox('roles[]',  $role->id, in_array($role->name, $r)) --}}
            {{ Form::label($role->name, ucfirst($role->name)) }}<br>

        @endforeach
       </td>
     </tr>
@endrole
@php
 $avSize = value(function () {
         $bytes = (integer)config('system.max_avatar_size');
             $i = floor(log($bytes) / log(1024));
             $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
             return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
        }) 
  @endphp
     <tr>
        <td onclick="clearUpload()">
            Avatar (<{{ $avSize }})
            <i class="fa fa-random w3-margin-left" style="color:orange;cursor:pointer;" title="Clear upload"></i>
        </td>
        <td>
          <input id="avatar" name="avatar" value="" data-parsley-filemimetypes="image/jpeg, image/png"  data-parsley-max-file-size="{{ (integer)config('system.max_upload_size') }}" type="file">
      </td>
       <td style="vertical-align:top; font-family: monospace;" rowspan="4" id="user_avatar">
         @if ($user->avatar !== false)
          <strong>Current Avatar:</strong><br>
          <img src= "{{ $user->avatar }}" style="width: 60px; height: 60px;">
          <div onclick="deleteAvatar()" class="w3-button w3-khaki w3-tiny w3-round">Delete</div>
         @endif
      </td>      
     </tr>
     <tr><td></td></tr>
     <tr>
       <td>
       {{ Form::submit('Update', array('class' => 'w3-button w3-orange w3-round w3-tiny')) }}
       </td>
       <td>
       {{ Form::button('Cancel', array('class' => 'w3-button w3-orange w3-round w3-tiny')) }}
       </td>
    </tr>
</table>
 
  
    {{ Form::close() }}
 
   </div>
 </div>
  
  </div>
<script>
function apiKey(id) {
	var x = "{!! url('/apikey/create') !!}" + '/' + id;
            $.get(x, function(data, status){
               $("#api_key").text(data);
	         });
}

window.Parsley.addValidator('maxFileSize', {
    validateString: function(_value, maxSize, parsleyInstance) {
      if (!window.FormData) {
        alert('You are making all developpers in the world cringe. Upgrade your browser!');
        return true;
      }
      var files = parsleyInstance.$element[0].files;
      return files.length != 1  || files[0].size <= maxSize;
    },
    requirementType: 'integer',
    messages: {
      en: 'This file should not be larger than {{ $avSize }}',
      fr: 'Ce fichier est plus grand que {{ $avSize }}.'
    }
  });

window.Parsley.addValidator('filemimetypes', {
    requirementType: 'string',
    validateString: function (value, requirement, parsleyInstance) {

        if (!window.FormData) {
            return true;
        }

        var file = parsleyInstance.$element[0].files;

        if (file.length == 0) {
            return true;
        }

        var allowedMimeTypes = requirement.replace(/\s/g, "").split(',');
        return allowedMimeTypes.indexOf(file[0].type) !== -1;

    },
    messages: {
        en: 'File mime type not allowed'
    }
});

function clearUpload() {
    $("#avatar").val("");
    $("#avatar").removeClass("parsley-error");
    $("#avatar").next().empty();
}

function deleteAvatar(id) {
    if (confirm("Delete Current Avatar?")) {
        var request = $.ajax({
            method: "GET",
            url: "{{ url('avatar/delete', [$user->id]) }}",
            data: { name: "{{ $user->username }}"}
          });
        request.done(function( msg ) {
            $("#user_avatar").hide();
        });
    }
}

</script>
  </div>

@endsection
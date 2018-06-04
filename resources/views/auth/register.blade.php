@extends('layouts.app')

@section('content')
<div id="user-register" class="w3-display-container w3-black">
 <div class="w3-container w3-cell w3-middle">
  <form id="edit-form" method="POST" action="{{ route('register') }}">
          {{ csrf_field() }}
     <table class="w3-table w3-small">
       <tr>
          <td>
             <label for="username">User Name</label>
          </td>
          <td>
             <input id="username" type="text" class="w3-round" name="username" value="{{ old('username') }}" required autofocus>      
             @if ($errors->has('username'))
                 <span class="help-block">
                  <strong>{{ $errors->first('username') }}</strong>
                 </span>
             @endif
           </td>                        
       </tr>
               <tr>
          <td>
             <label for="email" class="col-md-4 control-label">E-Mail Address</label>
          </td>
          <td>
              <input id="email" type="email" class="w3-round" name="email" value="{{ old('email') }}" required>
              @if ($errors->has('email'))
                 <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                 </span>
              @endif
           </td>
        </tr>
        
        @php
          $req_fields = config('user.registration_display_fields');
        @endphp
        @foreach ($req_fields as $field)
           @php
             $tokens = explode("_", $field);
             if ($tokens) {
                 $field = strtolower(implode('', $tokens));
                 $label = title_case(implode(' ', $tokens));
             }
             else {
                 $label = $field;
             }
           @endphp
         <tr>
          <td>
               <label for="{!! $field !!}">{!! $label !!}</label>
          </td>
          <td>
               <input id="{!! $field !!}" type="text" class="w3-round" name="{!! $field !!}" value="{{ old('{!! $field !!}') }}" required>
               @if ($errors->has('{!! $field !!}'))
                   <span class="help-block">
                       <strong>{{ $errors->first('{!! $field') }}</strong
                   </span>
               @endif
           </td>
          </tr>
       @endforeach
           <tr>
              <td>
                 <label for="password">Password</label>
              </td>
              <td>
                 <input id="password" type="password" class="w3-round" name="password" required>
                 @if ($errors->has('password'))
                    <span class="help-block">
                    <strong>{{ $errors->first('password') }}</strong>
                    </span>
                 @endif
              </td>
           </tr>
              <tr>
                 <td>
                    <label for="password-confirm">Confirm Password</label>
                 </td>
                 <td>
                    <input id="password-confirm" type="password" class="w3-round" name="password_confirmation" required>
                 </td>
              </tr>
              </table>
     </div>
</div>
@endsection

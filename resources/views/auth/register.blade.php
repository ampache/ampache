@extends('layouts.app')

@section('content')
<div class="w3-display-container w3-black w3-section" style="height:500px;">
<div class="w3-padding w3-display-middle">
@if (session('status'))
    <div class="w3-panel w3-red">
        {{ session('status') }}
    </div>
@endif
<div class="w3-display-container" style="height:100px;">
<div class="w3-display-middle" style="width:300px">
<H3>New User Registration</H3>
 <!-- <img width="88" height="88" src="{{ url('/images/ampache.png') }}" title="{{ Config::get('theme.title') }}"/> -->
</div>
</div>
  <form id="edit-form" method="POST" action="{{ route('register') }}">
          {{ csrf_field() }}

     <table class="w3-table w3-small">
       <tr>
          <td>
             <label for="username">User Name*</label>
          </td>
          <td>
             <input id="username" type="text" class="w3-round" name="username" value="{{ old('username') }}" required="" autofocus>      
            </td>                        
       </tr>
       <tr>
          <td>
             <label for="email" class="col-md-4 control-label">E-Mail Address*</label>
          </td>
          <td>
              <input id="email" type="email" class="w3-round" name="email" value="{{ old('email') }}" required="">
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
//                 $field = strtolower(implode('', $tokens));
                 $label = title_case(implode(' ', $tokens)) . '*';
             }
             else {
                 $label = title_case($field) . '*';
             }
           @endphp
         <tr>
          <td>
               <label for="{!! $field !!}">{!! $label !!}</label>
          </td>
          <td>
               <input id="{!! $field !!}" type="text" class="w3-round" name="{!! $field !!}" value="{{ old($field) }}" required="">
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
                 <label for="password">Password*</label>
              </td>
              <td>
                 <input id="password" type="password" class="w3-round" name="password" required="">
              </td>
           </tr>
              <tr>
                 <td>
                    <label for="password-confirm*">Confirm Password*</label>
                 </td>
                 <td>
                    <input id="password-confirm" type="password" class="w3-round" name="password_confirmation" required="">
                 </td>
              </tr>
    @if (config('user.captcha_public_reg') == true)
          <tr>
            <td> 
 				{!! captcha_image_html('RegisterCaptcha') !!}
 		    </td>
            <td>
                <input type="text"id="CaptchaCode" name="CaptchaCode">
           </td>
          </tr>
     @endif 
      </table>

          <br>
        <input class="btn btn-default" value="Register" type="submit">
  </form>

</div>
</div>
@endsection

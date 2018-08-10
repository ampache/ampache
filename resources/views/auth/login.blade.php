@extends('layouts.app')

@section('content')
<div class="w3-display-container w3-black w3-section" style="height:400px;">
<div class="w3-padding w3-display-middle">
@if (session('status'))
    <div class="w3-panel w3-red">
        {{ session('status') }}
    </div>
@endif
<div class="w3-display-container" style="height:50px;">
<div class="w3-display-middle" style="width:300px">
<H3>User Log In</H3>
 <!--        <img width="88" height="88" src="{{ url('/images/ampache.png') }}" title="{{ Config::get('theme.title') }}"/> -->
</div>
</div>

  <form id="login-form" method="POST" action="{{ route('login') }}">
         {{ csrf_field() }}
    <table class="w3-table w3-small">
        <tr>
            <td>
               <label for="username" >User Name</label>
            </td>
            <td>
               <input id="username" type="text" class="form-control" name="username" value="{{ old('username') }}" required="" autofocus>
               @if ($errors->has('username'))
                  <span>
                      <strong>{{ $errors->first('username') }}</strong>
                  </span>
               @endif
            </td>
         </tr>
         <tr>
             <td>
                <label for="password" class="col-md-4 control-label">Password</label>
             </td>
             <td>
                <input id="password" type="password" class="form-control" name="password" data-parsley-trigger required="">
                @if ($errors->has('password'))
                   <span>
                      <strong>{{ $errors->first('password') }}</strong>
                   </span>
                @endif
             </td>
          </tr>
          <tr>
             <td>
               <label>
                  <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember Me
               </label>
             </td>
          </tr>
          <tr>
            <td>
            </td>
              <td>
                 <a class="btn btn-link w3-text-orange" href="{{ route('password.request') }}">
                                    Forgot Your Password?
                 </a>
              </td>
          </tr>
          </table>
         <input class="btn btn-default" value="Log In" type="submit">
       </form>
</div>

</div>
@endsection

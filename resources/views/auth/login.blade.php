@extends('layouts.app')

@section('content')
<div id="user-login" class="w3-display-container w3-black w3-section">
 <div class="w3-container w3-cell w3-middle">
  <form id="edit-form" method="POST" action="{{ route('login') }}">
         {{ csrf_field() }}
    <table class="w3-table w3-small">
        <tr>
            <td>
               <label for="username" >User Name</label>
            </td>
            <td>
               <input id="username" type="text" class="form-control" name="username" value="{{ old('username') }}" required autofocus>
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
                <input id="password" type="password" class="form-control" name="password" required>
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
                 <a class="btn btn-link w3-text-orange" href="{{ route('password.request') }}">
                                    Forgot Your Password?
                 </a>
              </td>
          </tr>
          </table>
      </form>
   </div>
</div>
@endsection

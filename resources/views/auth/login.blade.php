@extends('layouts.fullform')

@section('title')
    {{ Config::get('theme.site_title') }}
@stop

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        
        @if(session()->has('error'))
            <div class="alert alert-success alert-dismissible">{!! session('error') !!}</div>
        @endif
        
        <div class="panel panel-default">
            <div class="panel-body">
                <form name="login" class="form-horizontal" role="form" method="POST" action="{{ url('/auth/login') }}">
                    {!! csrf_field() !!}

                    <div class="loginfield form-group{{ $errors->has('username') ? ' has-error' : '' }}" id="usernamefield">
                        <label class="col-md-4 control-label">{{ T_('Username') }}</label>

                        <div class="col-md-6">
                            <input type="text" class="form-control" name="username" value="{{ old('username') }}">

                            @if ($errors->has('username'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('username') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="loginfield form-group{{ $errors->has('password') ? ' has-error' : '' }}" id="passwordfield">
                        <label class="col-md-4 control-label">{{ T_('Password') }}</label>

                        <div class="col-md-6">
                            <input type="password" class="form-control" name="password">

                            @if ($errors->has('password'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="loginfield form-group" id="remembermefield">
                        <div class="col-md-6 col-md-offset-4">
                            <label>{{ T_('Remember Me') }}</label>
                            <input type="checkbox" name="remember">
                        </div>
                    </div>

                    <div class="formValidation form-group">
                        <div class="col-md-6 col-md-offset-4">
                            <a rel="nohtml" class="button btn btn-link" href="{{ url('/password/reset') }}">{{ T_('Lost password') }}</a>
                            <input type="submit" class="button btn btn-primary" text="{{ T_('Login') }}" />
                            @if (Config::get('user.allow_public_registration'))
                                <a rel="nohtml" class="button btn btn-link" href="{{ url('/auth/register') }}">{{ T_('Register') }}</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.fullform')

@section('title')
    Register...
@stop

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            @if (Config::get('user.user_agreement'))
            <div class="panel-heading">{{ T_('User Agreement') }}</div>
            <div class="panel-body">
                <div class="registrationAgreement">
                    <div class="agreementContent">
                        {{ Registration::show_agreement() }}
                    </div>
                    <div class="agreementCheckbox">
                        <input type='checkbox' name='accept_agreement' /> {{ T_('I Accept') }}
                    </div>
                </div>
            </div>
            @endif
            <div class="panel-heading">{{ T_('User Information') }}</div>
            <div class="panel-body">
                <form class="form-horizontal" name="login" role="form" method="POST" action="{{ url('/register') }}">
                    {!! csrf_field() !!}

                    <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
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

                    @if (Registration::isFieldVisible('name'))
                    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                        <label class="col-md-4 control-label">{{ T_('Name') }}</label>

                        <div class="col-md-6">
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}">

                            @if ($errors->has('name'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <label class="col-md-4 control-label">{{ T_('E-Mail') }}</label>

                        <div class="col-md-6">
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}">

                            @if ($errors->has('email'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
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

                    <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                        <label class="col-md-4 control-label">{{ T_('Confirm Password') }}</label>

                        <div class="col-md-6">
                            <input type="password" class="form-control" name="password_confirmation">

                            @if ($errors->has('password_confirmation'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('password_confirmation') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    @if (Registration::isFieldVisible('state'))
                    <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                        <label class="col-md-4 control-label">{{ T_('State') }}</label>

                        <div class="col-md-6">
                            <input type="text" class="form-control" name="state" value="{{ old('state') }}">

                            @if ($errors->has('state'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('state') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (Registration::isFieldVisible('city'))
                    <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                        <label class="col-md-4 control-label">{{ T_('City') }}</label>

                        <div class="col-md-6">
                            <input type="text" class="form-control" name="city" value="{{ old('city') }}">

                            @if ($errors->has('city'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('city') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (Registration::isFieldVisible('website'))
                    <div class="form-group{{ $errors->has('website') ? ' has-error' : '' }}">
                        <label class="col-md-4 control-label">{{ T_('Website') }}</label>

                        <div class="col-md-6">
                            <input type="text" class="form-control" name="website" value="{{ old('website') }}">

                            @if ($errors->has('website'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('website') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (Config::get('user.captcha_public_reg'))
                    <br />
                    <div>
                        <label class="col-md-4 control-label">{{ T_('Captcha') }} {!! Captcha::img() !!}</label>

                        <div class="col-md-6">
                            <input type="text" class="form-control" name="captcha">
                            
                            @if ($errors->has('captcha'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('captcha') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <br />
                    <div class="form-group">
                        <div class="col-md-6 col-md-offset-4 registerButtons">
                            <input type="submit" class="btn btn-primary" text="{{ T_('Register') }}" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

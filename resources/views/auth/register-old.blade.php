@extends('layouts.app')
{{-- register.blade.php --}}
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Register</div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('register') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                            <label for="username" class="col-md-4 control-label">User Name:</label>

                            <div class="col-md-6">
                                <input id="username" type="text" class="form-control" name="username" value="{{ old('username') }}" required autofocus>

                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">*E-Mail Address:</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <?php $fields = config('user.registration_mandatory_fields'); $count = count($fields);  ?>
                        @for ( $i=0; $i<$count;$i++)
                        <div class="form-group{{ $errors->has($fields[$i]) ? ' has-error' : '' }}">
                            <label for="$fields[$i]" class="col-md-4 control-label">{!! "*" . $fields[$i] . ":" !!}</label>

                            <div class="col-md-6">
                             <?php $fld = old($fields[$i]); ?>
                                <input id="{!! $fields[$i] !!}" type="{!! $fields[$i] !!}" class="form-control" name="{!! $fields[$i] !!}" value="{!! $fld !!}" required>

                                @if ($errors->has('{!! $fields[$i] !!}'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('{!! $fields[$i] !!}') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
						@endfor

                        <?php $fields = config('user.registration_display_fields'); $count = count($fields);  ?>
                        @for ( $i=0; $i<$count;$i++)
                        <div class="form-group">
                            <label for="$fields[$i]" class="col-md-4 control-label">{!! $fields[$i] . ":" !!}</label>

                            <div class="col-md-6">
                             <?php $fld = old($fields[$i]); ?>
                                <input id="{!! $fields[$i] !!}" type="{!! $fields[$i] !!}" class="form-control" name="{!! $fields[$i] !!}" value="{!! $fld !!}">

                                @if ($errors->has('{!! $fields[$i] !!}'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('{!! $fields[$i] !!}') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
						@endfor
  <input type="hidden" name="role" value="2">


                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">*Password:</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password-confirm" class="col-md-4 control-label">*Confirm Password:</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                            </div>
                        </div>

                   @if (Config::get('user.captcha_public_reg'))
                    <br />
                    <div class="form-group">
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


                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Register
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

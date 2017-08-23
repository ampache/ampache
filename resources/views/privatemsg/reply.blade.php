@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">'Compose Message</div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('messages.create', ) }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('recipient') ? ' has-error' : '' }}">
                            <label for="to_useer" class="col-md-4 control-label">REcipient:</label>

                            <div class="col-md-6">
                                <input id="pvmsg_to_user" type="text" class="form-control" name="to_user" value="{{ $_REQUEST['to_user'] }}" required autofocus>

                                @if ($errors->has('to_user'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('to_user') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('subject') ? ' has-error' : '' }}">
                            <label for="subject" class="col-md-4 control-label">Subject</label>

                            <div class="col-md-6">
                                <input type="text" class="form-control" name="subject" value="{{ $_REQUEST['subject'] }}" required>

                                @if ($errors->has('subject'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('subject') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                       <div class="form-group{{ $errors->has('message') ? ' has-error' : '' }}">
                            <label for="message" class="col-md-4 control-label">Message</label>

                            <div class="col-md-6">
        						<textarea name="message" cols="64" rows="10">{{ $_REQUEST['message'] }} </textarea>

                                @if ($errors->has('message'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('message') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>


                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember Me
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-8 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Login
                                </button>

                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                    Forgot Your Password?
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

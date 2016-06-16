@extends('layouts.fullform')

@section('title')
    {{ T_('Reset Password') }}
@stop

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-body">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                <form class="form-horizontal" name="login" role="form" method="POST" action="{{ url('/password/email') }}">
                    {!! csrf_field() !!}

                    <div class="loginfield form-group{{ $errors->has('email') ? ' has-error' : '' }}" id="emailfield">
                        <label class="col-md-4 control-label">{{ T_('Email') }}</label>

                        <div class="col-md-6">
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">

                            @if ($errors->has('email'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    <br />
                    <div class="form-group">
                        <div class="col-md-6 col-md-offset-4">
                            <input type="submit" class="btn btn-primary" text="{{ T_('Send') }}" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

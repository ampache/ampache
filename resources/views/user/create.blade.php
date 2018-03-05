@extends('layouts.app')

@section('content')
<div id="useradd" class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form id="userUpdate" class="form-horizontal" role="form" enctype="multipart/form-data" method="POST" action="{!! route('add') !!}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">E-Mail Address:</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <?php $fields = config('user.registration_mandatory_fields'); $count = count($fields);  ?>
                        @for ( $i=0; $i<$count;$i++)
                        <div class="form-group{{ $errors->has($fields[$i]) ? ' has-error' : '' }}">
                            <label for="$fields[$i]" class="col-md-4 control-label">{!! ucfirst($fields[$i]) . ":" !!}</label>

                            <div class="col-md-6">
                             <?php $fld = old($fields[$i]); ?>
                                <input id="{!! $fields[$i] !!}" type="text" height="100" class="form-control" name="{!! $fields[$i] !!}">
                            </div>
                        </div>
                        
						@endfor
                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">Password:</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password-confirm" class="col-md-4 control-label">Confirm Password:</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="level" class="col-md-4 control-label">User Level:</label>

                        	<div class="col-md-6">
                    			<select id="level" form="form-group">
  									<option value="5">Guest</option>
  									<option value="25">User</option>
  									<option value="50">Content Manager</option>
  									<option value="75">Catalog Manager</option>
  									<option value="100">Administrator</option>
								</select>
                            </div>
                        </div>
                        <div style="margin-top:10px;" class="form-group{{ $errors->has('image') ? ' has-error' : '' }}">
                            <label for="avatar" class="col-md-4 control-label">Avatar (< {!! $maxsize !!}):</label>
 
                            <div col-md-6">
                            <input type="file" name="image" id="file"> 
                             @if ($errors->has('image'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('image') }}</strong>
                                    </span>
                             @endif
                            
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Add User
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

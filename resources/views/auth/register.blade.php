@extends('layouts.app')
{{-- register.blade.php --}}
@section('content')
<div class="container">
    <script>
     $(function () {
        $("#dialog").dialog({
        	closeOnEscape: false,
         	autoOpen: false,
        	 open: function(event, ui) { 
            	 },
        	position: { my: "middle", at: "middle", of:"body"},
        	width: 400,
			height: 357,
			modal: true,
            title: "Ampache Registration",
            buttons: {
	            'Continue': function() {
					var url = "{{ route('login') }}";
	                $.post(url,
	                	    {
	                	_token: $("[name~='_token']").val(),
	                	username: $("#username").val(),
                		password: $("#email").val()
	                	    },
	                	    function(data, status){

	                	    	location.assign('{{ url("/") }}');
	          
	                	});
            	    },
	                Cancel: function() {
	                    $( this ).dialog( "close" );
	                  }
            }
        });
    });
	                $(document).ready(function(){
	                	$( 'a.ui-dialog-titlebar-close' ).remove();
	                	$('#dialog').css('overflow', 'hidden');
	   	                $("#dialog").dialog( "open" );
	                });
 </script>
 <div id="dialog" style="display: none" width="311px">
  <form>
   {{ csrf_field() }}
    <div class="row">
        <table class="table" style="border-collapse: collapse; border: none;">
        	<tbody>
        	  <tr style="border: none;">
        	    <td style="border: none;">
                     <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                            <label for="username" class="control-label">UserName:</label>
                            <div>
                                <input id="username" type="username" class="form-control" name="username" height="30px !important" required autofocus>
                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                           </div>
                    </div>
                  </td>
              </tr>
              <tr style="border: none;">
                  <td style="border: none;">
                     <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                         <label for="email" class="control-label">Email:</label>
                        <input id="email" type="email" class="form-control" name="email" height="30px !important" required>
                        @if ($errors->has('email'))
                           <span class="help-block">
                               <strong>{{ $errors->first('email') }}</strong>
                           </span>
                        @endif
                     </div>
              	  </td>
              </tr>
             </tbody>
          </table>
       </div>
   </form>
 </div>
</div>
@endsection

@extends('layouts.fullform')

@section('content')
<div class="container">
    <script>
     $(function () {
        $("#dialog").dialog({
        	closeOnEscape: false,
         	autoOpen: false,
        	 open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide()},
        	position: { my: "top", at: "bottom", of:"#headerlogo"},
        	width: 300,
			height: 200,
			modal: true,
        
            title: "Installing Ampache",
            buttons: {
	            'Continue': function() {
		            var language = $("#selectlanguage").val();
					var url = "{{ url("/install/setlanguage") }}" + "/" + language;
	                $.post(url,
	                	    {
	                	_token: $("[name~='_token']").val()
	                	    },
	                	    function(data, status){

	                	    	location.assign('{{ url("/install/system_check") }}');
	          
	                	});
	                $(this).dialog('close');
            	    }
            }
        });
    });
	                $(document).ready(function(){
	                	$( 'a.ui-dialog-titlebar-close' ).remove();
	   	                $("#dialog").dialog( "open" );
	                });
</script>
<div id="dialog" style="display: none" width="350px">
   <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Select Language</div>

                <div class="panel-body" style="padding: 0">
                <form>
                {{ csrf_field() }}
                    <select class="form-control" id="selectlanguage" style="color:#333;" name="languages">
                    @foreach ($languages as $language)
                    
                    	@if ($language === 'en_US')
                    	    <option value="{{ $language }}" selected="selected">{{ $language }}</option>
                    	@else
                    	    <option value="{{ $language }}">{{ $language }}</option>
                    	@endif
                    @endforeach
					</select>
				</form> 
                </div>
            </div>
        </div>
    </div>
 </div>
</div>
    
@endsection

<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>
@extends('layouts.installer')
@section('content')

  <div class="container">
        <div  class="page-header requirements">
            <h3><?php echo T_('Creating Admin Account: This step creates your initial Ampache admin account. ' .  
                'Once your admin account has been created you will be redirected to the login page.'); ?></h3>
        </div>

    <div id="install_progress" class="well">
    <h2><?php echo T_('Install progress'); ?></h2>
        <div class="progress-bar progress-bar-warning"
            role="progressbar"
            aria-valuenow="60"
            aria-valuemin="0"
            aria-valuemax="100"
            style="width: 99%">
            99%
        </div>
        <br><br>
    </div>
</div>
   
   <script>

   $(function () {
       $("#dialog").dialog({
       	closeOnEscape: false,
        	autoOpen: false,
   	    open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide()},
       	position: { my: "top", at: "bottom", of:'#install_progress'},
       	minWidth: 320,
       	width: 378,
			height: "auto",
			modal: false,
       
           title: "Creating Administrative User:",
           buttons: {
	            'Continue': function() {
    		        var validator = $( "#dialog_form" ).validate({
    		     	   rules: {
    		     		  local_pass: "required",
    		     		  local_pass2: {
    		     		       equalTo: "#local_pass"
    		     		     }
    		     		   }
    		     		 });

			        var t = validator.form();
					var url = "{{  url('install/create_account') }}";
			        if (t == true) {  
			        	$.post(url,
	                     {
               	        	_token: $("[name~='_token']").val(),
               	        	local_username: $("#local_username").val(),
              	        	local_pass: $("#local_pass").val(),
	                	 },
	                	 function(data, status){
		                	 if (data == "back") {
	              	    		location.assign('{{ url("/install/show_account") }}');
		                	 } else {
		              	    		location.assign('{{ url("/") }}');
			                 }
	              	          
	                	 });
	                    $(this).dialog('close');
			        }
           	},
          }
       }).dialog("widget").draggable("option","containment","none");
   });

	$(document).ready(function(){
	    $( 'a.ui-dialog-titlebar-close' ).remove();
	    $("#dialog").parent().find('.ui-dialog-buttonset'      ).css({'width':'100%','text-align':'center'});
	    
	   	$("#dialog").dialog( "open" );
	});
</script>
<div class="container">
	<div id="dialog" style="display: none" width="auto">
		<form id="dialog_form" role="form" class="form" method="post" action="{{ url('install/create_config') }}" autocomplete="off">
				{{ csrf_field() }}
			<div class="form-group">
    			<label for="local_username" class="control-label"><?php echo T_('Username'); ?></label>
    		<div>
        		<input type="text" class="form-control" id="local_username" name="local_username" value="admin" required>
   			</div>
			</div>
			<div class="form-group">
    			<label for="local_pass" class="control-label"><?php echo T_('Password:'); ?></label>
    			<div>
       				 <input type="password" class="form-control" id="local_pass" name="local_pass" required>
    			</div>
			</div>
			<div class="form-group">
    			<label for="local_pass2" class="control-label"><?php echo T_('Confirm Password:'); ?></label>
    		<div>
        		<input type="password" class="form-control" id="local_pass2" name="local_pass2" required>
    		</div>
			</div>
	
		</form>
	</div>
</div>
@endsection

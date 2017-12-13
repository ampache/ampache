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
            <h2><?php echo T_('Installing Database'); ?></h2>
        </div>

    <div id="install_progress" class="well">
    <h2><?php echo T_('Install progress'); ?></h2>
        <div class="progress-bar progress-bar-warning"
            role="progressbar"
            aria-valuenow="60"
            aria-valuemin="0"
            aria-valuemax="100"
            style="width: 33%">
            33%
        </div>
        <br><br>
    <p><strong><?php echo T_('Step 1 - Create the Ampache database'); ?></strong></p>
    <dl>
        <dd><strong>{{ T_('This step creates and inserts the Ampache database, so please provide a MySQL account with database creation rights.
         This step may take some time on slower computers.') }}</strong></dd>
    </dl>
    <ul class="list-unstyled" style="margin-bottom: 0">
        <li><?php echo T_('Step 2 - Create configuration files'); ?></li>
        <li><?php echo T_('Step 3 - Set up the initial account'); ?></li>
    </ul>
    </div>
</div>
    <script>
    $(function () {
        $("#dialog").dialog({
        	closeOnEscape: false,
         	autoOpen: false,
        	 open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide()},
        	position: { my: "top", at: "bottom", of:"#install_progress"},
        	minWidth: 420,
        	width: 420,
			height: "auto",
			modal: false,
        
            title: "MySQL  Database Information:",
            buttons: {
	            'Continue': function() {
					var url = "{{  url('/install/create_db') }}";
    		        var validator = $("#dialog_form").validate();
			        var t = validator.form();
			        if (t == true) {  
	                   $.post(url,
	                     {
	                	    _token:         $("[name~='_token']").val(),
	                	    local_db:       $("#local_db").val(),
	                	    local_host:     $("#local_host").val(),
	                	    local_port:     $("#local_port").val(),
	                	    admin_username: $("#admin_username").val(),
	                	    admin_pass:     $("#admin_pass").val(),
	                	    create_db: 		document.getElementById("create_db").checked,
	                	    overwrite_db: 	document.getElementById("overwrite_db").checked,
	                	    create_tables:  document.getElementById("create_tables").checked,
	                	    db_username:	$("#db_username").val(),
	                	    db_pass:		$("#db_pass").val(),
	                	    create_db_user:	document.getElementById("create_db_user").checked
	                	 },
	                	 function(data, status){
	                	    	location.assign(data);
	          
	                	 });
	                    $(this).dialog('close');
			        }
            	},
                "Skip Admin": function () {
					var url = "{{  url('/install/create_db') }}";
    		        var validator = $("#dialog_form").validate();
			        var t = validator.form();
			        if (t == true) {
		               $.post(url,
		  	              {
		  	               _token:          $("[name~='_token']").val(),
		  	               admin_username:  $("#admin_username").val(),
		  	               admin_pass:      $("#admin_pass").val(),
	                	   db_username:		$("#db_username").val(),
	                	   db_pass:			$("#db_pass").val(),
	                	   create_db_user:	document.getElementById("create_db_user").checked,
	                	   skip_admin:		true,
                   	 	   local_db:       $("#local_db").val(),
	                	   local_host:     $("#local_host").val()
		  	             },
		  	             function(data, status){
   		  	               	location.assign(data);
		  	             });
	                    $(this).dialog('close');	  				        
			        }
        },

           }
        }).dialog("widget").draggable("option","containment","none");
    });

 	$(document).ready(function(){
	    $( 'a.ui-dialog-titlebar-close' ).remove();
	    $("#dialog").parent().find('.ui-dialog-buttonset'      ).css({'width':'100%','text-align':'right'});
	    $("#dialog").parent().find('button:contains("Skip")').css({'float':'left'});
	    
	   	$("#dialog").dialog( "open" );
	});
	                
	function enableDbUser() {
	    var t = $("#specificuser").css("display");
	    console.log(t);
	    $("#specificuser").toggle();$("#specificpass").toggle();
	   	if (t == "none") {; 
	  		$("#db_username").attr("required", "");
	        $("#db_password").attr("required", "");
	    }
	    else
	        {
	        	$("#db_username").removeAttr("required");
	            $("#db_password").removeAttr("required");
	                		
	         }
	};
	      	                
</script>
<div class="container">
 <div id="dialog" style="display: none" width="auto">
 <form id="dialog_form" role="form" class="form" method="post" action="{{ url('/install/create_db') }}" autocomplete="off">
                        {{ csrf_field() }}
 
     <div class="form-group" {{ $errors->has('local_db') ? ' has-error' : '' }}">
        <label for="local_db"><?php echo T_('Desired Name'); ?></label>
             <input type="text" class="form-control" id="local_db" name="local_db" value="ampache" required>
               @if ($errors->has('local_db'))
                 <span class="help-block">
                 <strong>{{ $errors->first('local_db') }}</strong>
                 </span>
               @endif
     </div>
     
     <div class="form-group" {{ $errors->has('local_host') ? ' has-error' : '' }}">
        <label for="local_host"><?php echo T_('Hostname'); ?></label>
            <input type="text" class="form-control" id="local_host" name="local_host" value="localhost">
               @if ($errors->has('local_host'))
                 <span class="help-block">
                 <strong>{{ $errors->first('local_host') }}</strong>
                 </span>
               @endif
    </div>

    <div class="form-group" {{ $errors->has('local_port') ? ' has-error' : '' }}">
        <label for="local_port"><?php echo T_('Port'); ?></label>
      
            <input type="text" class="form-control" id="local_port" name="local_port" placeholder="3306 (Default)">
               @if ($errors->has('local_port'))
                 <span class="help-block">
                 <strong>{{ $errors->first('local_port') }}</strong>
                 </span>
               @endif
       
   </div>
    <div class="form-group" {{ $errors->has('admin_username') ? ' has-error' : '' }}">
        <label for="admin_username"><?php echo T_('Admin Username'); ?></label>
       
            <input type="text" class="form-control" id="admin_username" name="admin_username" value="root"
               title="Enter MySQL user with database creation rights." required>
               @if ($errors->has('admin_username'))
                 <span class="help-block">
                 <strong>{{ $errors->first('admin_username') }}</strong>
                 </span>
               @endif
       
    </div>
    <div class="form-group" {{ $errors->has('admin_pass') ? ' has-error' : '' }}">
        <label for="admin_pass"><?php echo T_('Admin Password'); ?></label>
        
            <input type="password" class="form-control" id="admin_pass" name="admin_pass" placeholder="(Required)" 
             title="Enter password for above user." required>
               @if ($errors->has('admin_pass'))
                 <span class="help-block">
                 <strong>{{ $errors->first('admin_pass') }}</strong>
                 </span>
               @endif
       
    </div>
      <ul class="list-inline">
      <li>
      
        <div class="form-group">
            <label for="create_db"><?php echo T_('Create Database'); ?></label>
            
                <input type="checkbox" checked id="create_db" name="create_db" 
                   onclick='$("#overwrite_db_div").toggle();$("#create_tables_div").toggle();'
            	/>
        	
        </div>
        
      </li>
      <li>
          <div class="form-group" id="overwrite_db_div" >
        	<label for="overwrite_db"><?php echo T_('Overwrite?'); ?></label>
        	
            	<input type="checkbox" value="1"id="overwrite_db" name="overwrite_db"/>
        	
    	</div>
      </li>
      <li>
          <div class="form-group" id="create_tables_div">
        <label for="create_tables"><?php echo T_('Create Tables'); ?></label>
            <input
                type="checkbox" value="1" checked
                id="create_tables" name="create_tables"
            />
        </div>
      </li>
      </ul>
     <div class="form-group">
        <label for="db_user"><?php echo T_('Create Database User'); ?></label>
            <input
                type="checkbox" value="create_db_user" name="db_user"
                id="create_db_user"
                onclick='enableDbUser();'
            />
    </div>
        <div class="form-group" style="display: none;" id="specificuser" {{ $errors->has('db_username') ? ' has-error' : '' }}">
        <label for="db_username"><?php echo T_('Username'); ?></label>
       
            <input type="text" class="form-control" id="db_username" name="db_username" value="ampache">
                @if ($errors->has('db_username'))
                 <span class="help-block">
                 <strong>{{ $errors->first('db_username') }}</strong>
                 </span>
               @endif
   
    </div>
    <div class="form-group" style="display: none;" id="specificpass" {{ $errors->has('db_pass') ? ' has-error' : '' }}">
        <label for="db_pass"><?php echo T_('User Password'); ?></label>
            <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Password (Required)">
               @if ($errors->has('db_pass'))
                 <span class="help-block">
                 <strong>{{ $errors->first('db_pass') }}</strong>
                 </span>
               @endif
    </div>
    
 
 </form>
 
</div> <!-- End of Dialog -->
</div>

@endsection
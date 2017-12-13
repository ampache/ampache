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
            <h2><?php echo T_('Configuring Ampache: This step takes the basic config values and updates the various config files.'); ?></h2>
        </div>

    <div id="install_progress" class="well">
    <h2><?php echo T_('Install progress'); ?></h2>
        <div class="progress-bar progress-bar-warning"
            role="progressbar"
            aria-valuenow="60"
            aria-valuemin="0"
            aria-valuemax="100"
            style="width: 66%">
            66%
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
       	minWidth: 620,
       	width: 678,
			height: "auto",
			modal: false,
       
           title: "Updating Ampache  Configuration:",
           buttons: {
	            'Continue': function() {
					var url = "{{  url('install/create_config') }}";
 	                   $.post(url,
	                     {
	                   db_username: $("#transcode_template").val(),
                	        _token: $("[name~='_token']").val(),
	                	  web_path: $("#web_path").val(),
	                  install_type: $("input:radio[name=usecase]:checked").val(),
	                      backends: function () {
	                          var names = "";
	                          $("input:checkbox[name=backends]").each(function () {
	                          if (this.checked) {
	                              names += $(this).val() + ",";
	                          }
	                          });
	                          $("#names").val(names);
	                          }
	                	 },
	                	 function(data, status){
	                	    	location.assign(data);
	          
	                	 });
	                    $(this).dialog('close');
			   
           	},
            "Skip": function () {
				var url = "{{  url('install/show_account') }}";
	               $.post(url,
	  	              {
	  	               _token:          $("[name~='_token']").val(),
	  	             },
	  	             function(data, status){
//		  	               	location.assign(data);
	  	             });
                    $(this).dialog('close');	  				        
		   
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
</script>
<div class="container">
	<div id="dialog" style="display: none" width="auto">
		<form id="dialog_form" role="form" class="form" method="post" action="{{ url('install/create_config') }}" autocomplete="off">
				{{ csrf_field() }}
			<div class="form-group">
    		<label for="web_path" class="col-sm-2 control-label">{{ T_('Web Path') }}</label>
    		<div class="col-sm-8">
        		<input type="text" class="form-control" id="web_path" name="web_path" value="">
    		</div>
			</div>
			<br>
			<h4><?php echo T_('Installation type'); ?></h4>
			<div>{{ T_('Configure Ampache at best for your use case, enabling / disabling features automatically.') }}</div>
		<div class="form-group">
    <div class="radio">
      <label><input type="radio" name="usecase" value="default" <?php if (!isset($_REQUEST['usecase']) || $_REQUEST['usecase'] == 'default') {
    echo 'checked';
} ?>><?php echo T_('Default'); ?> &mdash; <?php echo T_('Ampache is configured for personal use with most greatest features.'); ?></label>
    </div>
    <div class="radio">
      <label><input type="radio" name="usecase" value="minimalist" <?php if (isset($_REQUEST['usecase']) && $_REQUEST['usecase'] == 'minimalist') {
    echo 'checked';
} ?>>{{ T_('Minimalist') }} &mdash;{{ T_('only essential features are enabled to stream simply your music from a web interface.') }}</label>
    </div>
    <div class="radio">
      <label><input type="radio" name="usecase" value="community" <?php if (isset($_REQUEST['usecase']) && $_REQUEST['usecase'] == 'community') {
    echo 'checked';
} ?>>{{ T_('Community') }} &mdash;{{ T_('use recommended settings when using Ampache as a frontend for a music community.') }}</label>
    </div>
</div>
	<h4>{{ T_('Transcoding') }}</h4>
<div>
   
    {{ T_('This section apply default transcoding configuration according to the application you want to use. You may need to customize settings once this setup ended') }}. <a href="https://github.com/ampache/ampache/wiki/Transcoding" target="_blank">{{ T_('See wiki page')  }}.</a>
</div>
<br />
<div class="form-group">
    <label for="transcode_template" class="col-sm-4"><?php echo T_('Template Configuration'); ?></label>
    <div class="col-sm-4">
        <select class="form-control" id="transcode_template" name="transcode_template">
        <option value=""><?php echo T_('None'); ?></option>
            @foreach ($modes as $mode)
                <option value="{{ $mode }}">
                   {{ $mode }}
                </option>
           @endforeach
        </select>
        <?php
        if (count($modes) == 0) {
            ?>
        <label><?php echo T_('No default transcoding application found. You may need to install a popular application (ffmpeg, avconv ...) or customize transcoding settings manually after installation.'); ?></label>
        <?php
        } ?>
    </div>
</div>
<br>
<h4><?php echo T_('Players'); ?></h4>
<div><?php echo T_('Ampache is more than only a web interface. Several backends are implemented to ensure you can stream your media from anywhere.'); ?></div>
<div><?php echo T_('Select backends to enable. Depending the backend, you may need to perform additional configuration.'); ?> <a href="https://github.com/ampache/ampache/wiki/API" target="_blank"><?php echo T_('See wiki page'); ?>.</a></div>
<br />
<div class="form-group">
    <div class="checkbox-inline disabled">
        <label><input type="checkbox" value="1" checked disabled>Web interface</label>
    </div>
    <div class="checkbox-inline disabled">
        <label><input type="checkbox" value="1" checked disabled>Ampache API</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends" value="subsonic" <?php if (!isset($_REQUEST['backends']) || in_array('subsonic', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>Subsonic</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends" value="plex" <?php if (isset($_REQUEST['backends']) && in_array('plex', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>Plex</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends" value="upnp" <?php if (isset($_REQUEST['backends']) && in_array('upnp', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>UPnP</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends" value="daap" <?php if (isset($_REQUEST['backends']) && in_array('daap', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>DAAP (iTunes)</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends" value="webdav" <?php if (isset($_REQUEST['backends']) && in_array('webdav', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>WebDAV</label>
    </div>
</div>

			

</form>
            
</div>
</div>

@endsection

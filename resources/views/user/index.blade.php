@extends('layouts.app')

@section('content')
    <div class="col-sm-offset-4 col-sm-4">
        @if(session()->has('ok'))
            <div class="alert alert-success alert-dismissible">{!! session('ok') !!}</div>
        @endif
        
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">{{ T_('Users') }}</h3>
                <a class='btn btn-info pull-right' href="{{ url('/user/create') }}">Register</a>
            </div>
            <br />
            {!! $links !!}
            <table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="user">
                <thead>
                    <tr class="th-top">
                        <th id="MediaTable-0-mediaTableCol-0" class="cel_username essential persist"><u>{{ T_('Name') }}</u></th>
                        <th id="MediaTable-0-mediaTableCol-1" class="cel_lastseen"><u>{{ T_('Last Seen') }}</u></th>
                        <th id="MediaTable-0-mediaTableCol-2" class="cel_registrationdate"><u>{{ T_('Registration Date') }}</u></th>
                        @if (Auth::check() && Auth::user()->isContentManager())
                            <th id="MediaTable-0-mediaTableCol-3" class="cel_activity"><u>{{ T_('Activity') }}</u></th>
                            @if (config('user.track_user_ip'))
                                <th id="MediaTable-0-mediaTableCol-4" class="cel_lastip"><u>{{ T_('Last Ip') }}</u></th>
                            @endif
                        @endif
                        <th id="MediaTable-0-mediaTableCol-5" class="cel_action essential"><u>{{ T_('Action') }}</u></th>
                        <th id="MediaTable-0-mediaTableCol-6" class="cel_online"><u>{{ T_('On-line') }}</u></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr class="<?php echo App\Support\UI::flip_class(); ?>">
                        <td class="cel_name" headers="MediaTable-0-mediaTableCol-0">
                                @if ($user->has_avatar())
                                     <img src="{{ $user->avatar_url() }} " alt="HTML5 Icon" style="width:32px;height:32px;">
                                @endif
                            <a href="{!! route('show', [$user->id]) !!}">
                             {{ $user->username }}
                                @if ($user->name_public || (Auth::check() && Auth::user()->isAdmin()))
                                    ({{ $user->fullname }})
                                @endif
                            </a>
                        </td>
                        <td class="cel_lastseen" headers="MediaTable-0-mediaTableCol-1">{{ $user->last_seen }}</td>
                        <td class="cel_registrationdate" headers="MediaTable-0-mediaTableCol-1">{{ $user->created_at }}</td>
                        @if (Auth::check() && Auth::user()->isContentManager())
                            <td class="cel_activity" headers="MediaTable-0-mediaTableCol-2">{{ $user->usage() }}</td>
                            @if (config('user.track_user_ip'))
                                <td class="cel_lastip" headers="MediaTable-0-mediaTableCol-3">
                                    <a href="{!! route('user.ip_history', [$user->id]) !!}">
                                        {{ $user->ip_history }}
                                    </a>
                                </td>
                            @endif
                        @endif

                        @if (Auth::check() && Auth::user()->isAdmin())
                            <td class="cel_action" headers="MediaTable-0-mediaTableCol-5">
							    <table>
							    	<tr>
                        	  			<td>
 		                         			@if (Auth::check() && config('feature.sociable'))
                                				<a href="{!! url('/messages/index') !!}"><img src="{!! url_icon('mail') !!}" title="{{ T_('Send private message') }}"></a>
                         		 			@endif
     						  			</td>
                           				<td>                        		 			
											<form id="edit{{ $user->id }}" action="{{ url('/user/destroy/'.$user->id) }}" method="POST">
   														{{ csrf_field() }}
											   <a href="javascript:editUser('{{ $user->username }}', {{ $user->id }})"><img id="say_it" src="{{ url_icon('edit') }}" title="{{ T_('Edit') }}"/></a>
                                			</form>
    						  			</td>
                           				<td>
											<form id="delete{{ $user->id }}" action="{{ url('/user/destroy/'.$user->id) }}" method="POST">
   														{{ method_field('DELETE') }}
   														{{ csrf_field() }}
   														<a href="javascript:deleteUser('{{ $user->username }}', {{ $user->id }})"><img src="{{ url_icon('delete') }}" title="{{ T_('Delete') }}" /></a>
                                			</form>
                            			</td>
                        				<td>
                           					@if ($user->disabled)
                                    			<a href="{!! url('user/enable', [$user->id]) !!}"><img src="{!! url_icon('enable') !!}" title="{{ T_('Enable') }}" /></a>
                            				@else
                                    			<a href="{!! url('user/disable', [$user->id]) !!}"><img src="{!! url_icon('disable') !!}" title="{{ T_('Disable') }}" /></a>
                            				@endif
                            			</td>
                            		</tr>
                            	</table>
                            </td>
                        @endif
    
                        @if ($user->is_logged_in())
                            <td class="cel_online user_online" title="Logged In" headers="MediaTable-0-mediaTableCol-6"> &nbsp; </td>
                        @elseif ($user->disabled)
                            <td class="cel_online user_disabled" title="disabled" headers="MediaTable-0-mediaTableCol-6"> &nbsp; </td>
                        @else
                            <td class="cel_online user_offline" title="Off Line" headers="MediaTable-0-mediaTableCol-6"> &nbsp; </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
    <div id="hello" title="Hello  World!"><p><font face="Georgia" size="4">
Hey, world, I just said "Hello!"</font></p></div>           
            {!! $links !!}
        </div>
    <script>
		function deleteUser(username, id) {
			if (confirm("Are you sure you want to permanently delete user '" + username + "'?")) {
				document.getElementById(id).submit();
			}
		};
		$( "#hello" ).dialog({
			 autoOpen: false,
				width: 400,
				height: 500,
				modal: true,
				
		        buttons: {
		            'Save': function() {
			            var email = $("#email").val();
		            	if ((email.indexOf('@') == -1) && (email.length > 0)) {
				            alert("please enter a valid email address");
				            return false;
				        }
			            var id = $(this).data("id");
						var url = "{{ url("update") }}" + "/" + id;
		                $.post(url,
		                	    {
	                	            username: $("#user").val(),
		                	        email: $("#email").val(),
		                	        _token: $("[name~='_token']").val(),
		                	        password: $("#password").val(),
		                	        fullname: $("#fullname").val()
		                	    },
		                	    function(data, status){
		                	        alert(data.status);
		                	});
		                $(this).dialog('close');
                	    },
		            'Cancel': function() {
			            $(this).dialog('close');
		          },
			 
			  }
		});
		
		function editUser(username, id) {
			var url = "{{ url("edit") }}";
			$("#hello").html("");
			$("#hello").css('overflow', 'hidden');
			$("#hello").data("id", id).dialog("option", "title", "Loading...").dialog("open");
			$("#hello").load(url + "/" + id.toString() + " #useredit");
			$("#hello").dialog("option", "title","Updating: " + username);
			$("#hello").dialog("option", id);
			}
 	</script>
 
@stop

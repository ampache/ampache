@extends('layouts.app')

@section('content')
    <div class="col-sm-offset-4 col-sm-4">
        @if(session()->has('ok'))
            <div class="alert alert-success alert-dismissible">{!! session('ok') !!}</div>
        @endif
        
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">{{ T_('Users') }}</h3>
                {!! link_to_route('user.create', T_('Create user'), [], ['class' => 'btn btn-info pull-right']) !!}
            </div>
            <br />
            {!! $links !!}
            <table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="user">
                <thead>
                    <tr class="th-top">
                        <th class="cel_username essential persist">{{ T_('Name') }}</th>
                        <th class="cel_lastseen">{{ T_('Last Seen') }}</th>
                        <th class="cel_registrationdate">{{ T_('Registration Date') }}</th>
                        @if (Auth::check() && Auth::user()->isContentManager())
                            <th class="cel_activity">{{ T_('Activity') }}</th>
                            @if (Config::get('user.track_user_ip'))
                                <th class="cel_lastip">{{ T_('Last Ip') }}</th>
                            @endif
                        @endif
                        @if (Auth::check() && Config::get('feature.sociable'))
                            <th class="cel_follow essential">{{ T_('Following') }}</th>
                        @endif
                        <th class="cel_action essential">{{ T_('Action') }}</th>
                        <th class="cel_online">{{ T_('On-line') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr class="{{ UI::flip_class() }}">
                        <td class="cel_name">
                            <a href="{!! route('user.show', [$user->id]) !!}">{{ $user->name }}
                                @if ($user->name_public || (Auth::check() && Auth::user()->isAdmin()))
                                    ({{ $user->name }})
                                @endif
                            </a>
                        </td>
                        <td class="cel_lastseen">{{ $user->last_seen }}</td>
                        <td class="cel_registrationdate">{{ $user->created_at }}</td>
                        @if (Auth::check() && Auth::user()->isContentManager())
                            <td class="cel_activity">{{ $libitem->f_useage }}</td>
                            @if (Config::get('user.track_user_ip'))
                                <td class="cel_lastip">
                                    <a href="{!! route('user.ip_history', [$user->id]) !!}">
                                        {{ $user->ip_history }}
                                    </a>
                                </td>
                            @endif
                        @endif
                        @if (Auth::check() && Config::get('feature.sociable'))
                                <td class="cel_follow">{{ $libitem->get_display_follow() }}
                        @endif
                        <td class="cel_action">
                        @if (Auth::check && Config::get('feature.sociable'))
                                <a id="<a href="{!! route('messages.write', [$user->name]) !!}"><img src="{!! url_icon('mail') !!}" title="{{ T_('Send private message') }}" /></a>
                        @endif
                        @if (Auth::check() && Auth::user()->isAdmin())
                            <td>
                                <a href="{!! route('user.edit', [$user->id]) !!}"><img src="{!! url_icon('edit') !!}" title="{{ T_('Edit') }}" /></a>
                            </td>
                            <td>
                                {!! Form::open(['method' => 'DELETE', 'route' => ['user.destroy', $user->id]]) !!}
                                    <a href="javascript: confirm('{{ T_('Are you sure you want to permanently delete %s?'), $user->name }}') ? this.form.submit() : void();"><img src="{{ url_icon('delete') }}" title="{{ T_('Delete') }}" /></a>
                                {!! Form::close() !!}
                            </td>
                            @if ($user->disabled)
                                <td>
                                    <a href="{!! route('user.enable', [$user->id]) !!}"><img src="{!! url_icon('enable') !!}" title="{{ T_('Enable') }}" /></a>
                                </td>
                            @else
                                <td>
                                    <a href="{!! route('user.disable', [$user->id]) !!}"><img src="{!! url_icon('disable') !!}" title="{{ T_('Disable') }}" /></a>
                                </td>
                            @endif
                        @endif
                        </td>
                        @if (($user->is_logged_in()) & ($user->is_online()))
                            <td class="cel_online user_online"> &nbsp; </td>
                        @elseif ($user->disabled)
                            <td class="cel_online user_disabled"> &nbsp; </td>
                        @else
                            <td class="cel_online user_offline"> &nbsp; </td>;
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            {!! $links !!}
        </div>
    </div>
@stop
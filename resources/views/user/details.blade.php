@extends('layouts.app')

@section('content')

<div class="container-fluid">
   <h3>    <?php echo $user->username; ?> </h3>
   <div class="user_avatar">
       @if ($user->has_avatar())
           <img src="{{ $user->avatar_url() }} " alt="HTML5 Icon" style="width:256px;height:256px;">
       @endif
   </div>
   <dl class="media_details">
   <?php $rowparity = \App\Support\UI::flip_class() ?>
   <dt class="{{ $rowparity }}">{{ T_('Display Name') }}</dt>
          <dd class="{!! $rowparity !!}">
          <span>{{ $user->username }}.</span>

              @if (($user->isRegisteredUser()) && (config('feature.sociable')))
            <a id="{{ 'reply_pvmsg' . $user->id }}" href="{!! url('message/new/' . $user->username) !!}">
                <img src="{!! url_icon('mail') !!}" title="{!! T_('Send private message') !!}"/>
            </a>
		@endif
		@if ($user->isAdmin())
		   <a href="{!! route('edit', [$user->id]) !!}"><img src="{!! url_icon('edit') !!}" title="{{ T_('Edit') }}"/></a>
		@endif
       </dd>
   
    
       
  </dl>   


</div>

@endsection

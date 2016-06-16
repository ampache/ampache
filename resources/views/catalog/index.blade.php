@extends('layouts.app')

@section('content')
    <div class="col-sm-offset-4 col-sm-4">
        @if(session()->has('ok'))
            <div class="alert alert-success alert-dismissible">{!! session('ok') !!}</div>
        @endif
        
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">{{ T_('Catalogs') }}</h3>
                {!! link_to_route('catalog.create', T_('Create catalog'), [], ['class' => 'btn btn-info pull-right']) !!}
            </div>
            <br />
            {!! $links !!}
            <table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="user">
                <thead>
                    <tr class="th-top">
                        <th class="cel_catalog essential persist"><?php echo T_('Name'); ?></th>
                        <th class="cel_info essential"><?php echo T_('Info'); ?></th>
                        <th class="cel_lastverify optional"><?php echo T_('Last Verify'); ?></th>
                        <th class="cel_lastadd optional"><?php echo T_('Last Add'); ?></th>
                        <th class="cel_lastclean optional "><?php echo T_('Last Clean'); ?></th>
                        <th class="cel_action cel_action_text essential"><?php echo T_('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($catalogs as $catalog)
                    <tr class="{{ UI::flip_class() }}">
                        
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            {!! $links !!}
        </div>
    </div>
@endsection
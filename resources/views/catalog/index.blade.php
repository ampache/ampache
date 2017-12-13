@extends('layouts.app')

@section('content')
  <h3 style="text-align:left;">{{ T_('Catalogs') }}</h3>
        
  <div class="table-responsive">          
  	<table class="table">
       <thead>
           <tr>
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
                <tr class="{{ \App\Support\UI::flip_class() }}"> </tr>
            @endforeach
        </tbody>
    </table>
         
             
    </div>
@endsection
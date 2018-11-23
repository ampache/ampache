@extends('layouts.install')
@section('content')
{{-- install/language.blade.php --}}
   <div class="w3-container" role="main">
       <div class="jumbotron">
            <h1 id="headerlogo"><img src="{{ url('/themes/reborn/images/ampache.png') }}" title="Ampache" alt="Ampache">{{ __('Ampache Installation') }}</h1>
        </div>
        <div class="page-header">
            <h1>{{ __('Choose Installation Language') }}</h1>
        </div>
     </div>
                <form class="form-group" role="form" method="post" action="{{ url('setLanguage') }}" enctype="multipart/form-data">
                {{ csrf_field() }}
                    <div class="form-group" style="padding: 0">
                    <select class="form-control" id="selectlanguage" style="color:#333;" name="languages">
                    @foreach ($languages as $language)
                    
                    	@if ($locale === $language)
                    	    <option value="{{ $language }}" selected="selected">{{ $language }}</option>
                    	@else
                    	    <option value="{{ $language }}">{{ $language }}</option>
                    	@endif
                    @endforeach
					</select>
               </div>
            <button type="submit" class="btn btn-warning"><?php echo __('Start configuration'); ?></button>
				</form> 
      
@endsection

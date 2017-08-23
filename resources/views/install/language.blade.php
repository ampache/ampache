@extends('layouts.fullform')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-4 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Select Language</div>

                <div class="panel-body">
                    <select name="cars">
                    @foreach ($languages as $language)
                    
                    	@if ($language === 'en_US')
                    	    <option value="{{ $language }}"select>{{ $language }}</option>
                    	@else
                    	    <option value="{{ $language }}">{{ $language }}</option>
                    	@endif
                    @endforeach
					</select> 
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

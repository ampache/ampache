{{-- /resources/views/modules/plugins.blade.php --}}
@extends('layouts.app')

@section('title', '| Plugin Modules')

@section('content')
<div class="w3-section" style="margin:14px;">
    <h4><i class="fa fa-reorder"></i> {{ $title }}</h4>
    <hr>
    <div class="w3-container">
        <table class="w3-table table-bordered table-striped w3-small" style="width:70%;">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Version</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
		      @if (count($catalogs))
                @foreach ($catalogs as $catalog)
                <tr>
                    <td class="cat-type">{{ $catalog->get_type() }}</td>
                    <td class="cat-description">{{ $catalog->get_description() }}</td>
                    <td class="cat-version">{{ $catalog->get_version() }}</td>
                    <td class="cat-action" onclick="toggleAction({{ $catalog->get_type() }},
                         {{ $catalog->is_installed() ? 'Disable' : 'Activate' }})">
                        {{ $catalog->is_installed() ? "Disable" : "Activate" }}
                        
                    </td>
                </tr>
                @endforeach
               @else
                 <tr>
                 <td>No {{ $type }} found</td>
                 </tr>
                 @endif
                
            </tbody>

        </table>
    </div>
</div>
<script>
function toggleAction(type, action) {
    $.get("{{ url('/modules/') }} + type + '/' + action, function(data, status){
        alert("Data: " + data + "\nStatus: " + status);
    });
	
}

</script>
@endsection
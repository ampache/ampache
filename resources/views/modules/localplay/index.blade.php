{{-- /resources/views/modules/localplay/index.blade.php --}}
@extends('layouts.app')

@section('title', '| Localplay')

@section('content')
<div class="w3-section" style="margin:14px;">
    <h4><i class="fa fa-reorder"></i> Localplay modules</h4>
    <hr>
    <div class="w3-container">
        <table class="w3-table table-bordered table-striped w3-small" style="width:70%;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Version</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
		      @if (count($modules))
                @foreach ($modules as $module)
                <tr>
                    <td class="lp-type">{{ $module->get_type() }}</td>
                    <td class="lp-description">{{ $module->get_description() }}</td>
                    <td class="lp-version">{{ $module->get_version() }}</td>
                    <td class="lp-action" onclick="toggleAction('{{ $module->get_type()')" }},
                         {{ $module->is_installed() ? 'Disable' : 'Activate' }})">
                        {{ $module->is_installed() ? "Disable" : "Activate" }}
                        
                    </td>
                </tr>
                @endforeach
               @else
                 <tr>
                 <td>No Localplay modules found</td>
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
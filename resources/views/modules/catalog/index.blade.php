{{-- /resources/views/modules/catalogs/index.blade.php --}}
@extends('layouts.app')

@section('title', '| Catalog Modules')

@section('content')
<div class="w3-section" style="margin:14px;">
    <h4><i class="fa fa-reorder"></i> catalogs</h4>
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
		      @if (count($modules))
                @foreach ($modules as $module)
                <tr>
                    <td class="cat-type">{{ $module->get_type() }}</td>
                    <td class="cat-description">{{ $module->get_description() }}</td>
                    <td class="cat-version">{{ $module->get_version() }}</td>
                    <td  id="{{ $module->get_type() }}" class="cat-action" style="cursor:pointer" onclick="toggleAction('{{ $module->get_type() }}')">
                        {{ $module->is_installed() ? "Disable" : "Activate" }}
                        
                    </td>
                </tr>
                @endforeach
               @else
                 <tr>
                 <td>No catalog modules found</td>
                 </tr>
                 @endif
                
            </tbody>

        </table>
    </div>
</div>
<script>
function toggleAction(type, action) {
    var ctype = type;
    var caction = document.getElementById(ctype).innerHTML.trim();
    $.get("{{ url('/modules/') }}" + '/' + type + '/' + caction, function(data, status){
        alert(ctype + " catalog: " + "now " + caction + "d");
        if (caction == "Activate") {
            document.getElementById(ctype).innerHTML = "Disable";
        } else {
            document.getElementById(ctype).innerHTML = "Activate";
        }
    });
	
}

</script>
@endsection
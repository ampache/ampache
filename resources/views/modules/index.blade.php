{{-- /resources/views/modules/catalogs.blade.php --}}
@extends('layouts.app')

@section('title', '| {{ $title }} Modules')

@section('content')

@if ($title == 'Plugin')
   @inject('plugin', 'App\Services\PluginService')
@endif
<div class="w3-section" style="margin:14px;">
    <h4><i class="fa fa-reorder"></i> {{ $title }} Modules</h4>
    <hr>
    <div class="w3-container">
        <table class="w3-table table-bordered w3-hoverable w3-small" style="width:70%;">
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
                <tr class="w3-hover-amber">
                    <td class="mod-type">{{ ucfirst($module->get_type()) }}</td>
                    <td class="mod-description">{{ $module->get_description() }}</td>
                    <td class="mod-version">{{ $module->get_version() }}</td>
                    <td class="mod-action">
                     <form id="{{ $module->get_type() }}" action="/$moduleaction_page.php" enctype="multipart/form-data">
                       @method('PUT')
                        <label class="switch">
                             <input type="checkbox" name="{{ $module->get_type() }}"
                             @if ($title == "Plugin")
                                {{ $plugin::is_installed($module->get_type()) ? 'checked' : '' }}>
                                <span class="slider round" title="{{ $plugin::is_installed($module->get_type()) ? 'Enabled' : 'Disabled' }}" ></span>
                             @else
                                {{ $module->is_installed() ? 'checked' : '' }}>
                                <span class="slider round" title="{{ $module->is_installed() ? 'Enabled' : 'Disabled' }}" ></span>
                             @endif
                        </label>
                        <input type="hidden" name="module" value="{{ $title }}">
                     </form>
                    </td>
                 </tr>
                @endforeach
               @else
                 <tr>
                 <td>No {{ $type }} modules found</td>
                 </tr>
                 @endif
                
            </tbody>

        </table>
    </div>
</div>
        </div>
        <div id="dialog-confirm"> <div id="alert"></div>
        </div>
<script>
$(":checkbox").on("change", function(){
    var id = this.form.id;
        var moduleName = this.name;
        var state = "enable";
        if (this.checked) {
            this.nextElementSibling.title  = "Enabled";
            state = "Enable";
            
        } else {

            if (confirm("Are you sure you wnat to remove catalog and all it's entries?")) {
                this.nextElementSibling.title = "Disabled";
                state = "Disable";
            } else {
                this.checked = true;
                exit;
            }
        }
        var mUrl = "{{ url('/modules/') }}" + "/" + moduleName + "/" + state;
        var data = $("#" + id + " :input").serialize() + "&" + moduleName + "=" + this.checked;
        $.ajax({
            url: mUrl,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },          
            type: 'POST',
            data: data,
            success : function (result) {
                this.title = "Enabled";
            },
            error : function (result) {
                console.log(result);
            }
        })
});

</script>
@endsection
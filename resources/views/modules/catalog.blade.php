{{-- /resources/views/modules/catalogs.blade.php --}}
@extends('layouts.app')

@section('title', '| Catalogs')

@section('content')
<div class="w3-section" style="margin:14px;">
    <h4><i class="fa fa-reorder"></i> {{ $title }}</h4>
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
		      @if (count($catalogs))
                @foreach ($catalogs as $catalog)
                <tr class="w3-hover-amber">
                    <td class="cat-type">{{ ucfirst($catalog->get_type()) }}</td>
                    <td class="cat-description">{{ $catalog->get_description() }}</td>
                    <td class="cat-version">{{ $catalog->get_version() }}</td>
                    <td class="cat-action">
                     <form id="{{ $catalog->get_type() }}" action="/action_page.php" enctype="multipart/form-data">
                       @method('PUT')
                        <label class="switch">
                             <input type="checkbox" name="{{ $catalog->get_type() }}"
                              {{ $catalog->is_installed() ? 'checked' : '' }}>
                             <span class="slider round" title="{{ $catalog->is_installed() ? 'Enabled' : 'Disabled' }}" ></span>
                        </label>
                     </form>
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
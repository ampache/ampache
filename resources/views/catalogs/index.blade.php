{{-- /resources/views/catalogs/index.blade.php --}}
@extends('layouts.app')

@section('title', '| Catalogs')

@section('content')
<div class="w3-section" style="margin:14px;">
    <h4><i class="fa fa-reorder"></i> catalogs</h4>
    <hr>
    <div class="w3-container">
        <table class="w3-table table-bordered table-striped w3-small" style="width:70%;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Owner</th>
                    <th>Last Add</th>
                    <th>Last Verify</th>
                    <th>Last Clean</th>
                    <th>Action</th>
                    
                </tr>
            </thead>

            <tbody>
		      @if ($catalogs->count())
                @foreach ($catalogs as $catalog)
                <tr>
                    <td>{{ $catalog->name }}</td>
                    <td>{{ $catalog->owner }}</td>
                    <td>{{ $catalog->last_add }}</td>
                    <td>{{ $catalog->last_verify }}</td>
                    <td>{{ $catalog->last_clean }}</td>
                    <td>
                        <form>
                        <select name="catalog_action_menu">
                            <option value="add_to_catalog"><?php echo T_('Add'); ?></option>
                            <option value="update_catalog"><?php echo T_('Verify'); ?></option>
                            <option value="clean_catalog"><?php echo T_('Clean'); ?></option>
                            <option value="full_service"><?php echo T_('Update'); ?></option>
                            <option value="gather_media_art"><?php echo T_('Gather Art'); ?></option>                    
                            <option value="show_delete_catalog"><?php echo T_('Delete'); ?></option>
                         </select>
                         <input type="button" onClick="NavigateTo('
                         <?php echo $web_path; ?>/admin/catalog.php?action=' + this.form.catalog_action_menu.options[this.form.catalog_action_menu.selectedIndex].value + '&catalogs[]=<?php echo $libitem->id; ?>');" value="<?php echo T_('Go'); ?>">
                         </form>
                    </td>
                </tr>
                @endforeach
               @else
                 <tr>
                 <td>No catalogs found</td>
                 </tr>
                 @endif
                
            </tbody>

        </table>
    </div>
</div>

@endsection
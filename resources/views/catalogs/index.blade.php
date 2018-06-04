@extends('layouts.app')

@section('content')
        
 <div id="catalog_show" class="w3-display-container w3-black">
    <div class="row">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Catalogs') }}</h3>
            </div>
            <br />
            <table class="w3-table" cellpadding="0" cellspacing="0" data-objecttype="user">
                     <tr>
                        <th class="cel_catalog essential persist">{!! __('Name') !!}</th>
                        <th class="cel_info essential"><?php echo __('Info'); ?></th>
                        <th class="cel_lastverify optional"><?php echo __('Last Verify'); ?></th>
                        <th class="cel_lastadd optional"><?php echo __('Last Add'); ?></th>
                        <th class="cel_lastclean optional "><?php echo __('Last Clean'); ?></th>
                        <th class="cel_action cel_action_text essential"><?php echo __('Actions'); ?></th>
                    </tr>
                @if (isset($Catalogs))
                    @foreach ($Catalogs as $catalog)
                     @php
                        $icon                 = $catalog->enabled ? 'disable' : 'enable';
                        $button_flip_state_id = 'button_flip_state_' . $catalog->id;
                     @endphp
               		    <tr class="w3-hover-red" id="catalog_{!! $catalog->id !!}">
                 		<td style="cursor: pointer;" class="cel_catalog" onclick="dialogEdit('{!! $catalog->catalog_id !!}', '{!! $catalog->name !!}', 
                     		'{!! $catalog->path !!}', 'catalog-edit')">
                 		<a id="name" href="#" title="Edit Name">{!! $catalog->name !!}</a> </td>
                        <td class="cel_info">{!! e($catalog->path) !!}</td>
                        <td class="cel_lastverify">{!! e($catalog->f_update) !!}</td>
                        <td class="cel_lastadd">{!! e($catalog->f_add) !!}</td>
                        <td class="cel_lastclean">{!! e($catalog->f_clean) !!}</td>
                        <td class="cel_action cel_action_text">
                       <?php if (!$catalog->isReady()) {
                       ?>
                        <a href="{{ url('catalog/add_to_catalog') . "/" . $catalog->catalog_id }}"><b>{{ __('Make it ready ...') }}</b></a><br />
						<?php
                        } ?>
                  <form id="form_{!! $catalog->id !!}" >
                      <select id="catalog_action_menu_{!! $catalog->catalog_id !!}">
                     <?php if ($catalog->isReady()) {
                     ?>
                     <option value="add_to_catalog">{!! __('Add') !!}</option>
                     <option value="update_catalog">{!! __('Verify') !!}</option>
                     <option value="clean_catalog">{!! __('Clean') !!}</option>
                     <option value="full_service">{!! __('Update') !!}</option>
                     <option value="gather_art">{!! __('Gather Art') !!}</option>
                    <?php
                   } ?>
                    <option value="delete_catalog">{!! __('Delete') !!}</option>
                   </select>
                  <input type="button" onClick="NavigateTo({!! $catalog->catalog_id !!})" value="{!! __('Go') !!}">
                   @if (config('catalog.catalog_disable')) 
                  <span id="<?php echo($button_flip_state_id); ?>">
                  </span>
                   @endif
               </form>
              </td>                        
             </tr>
            @endforeach
            @else
            <tr>
              <td>
                No Catalogs
              </td>
              </tr>
            @endif
            </table>
        </div>
        </div>
         
    <script>

    $( "#dialog-confirm").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        height: "auto",
        width: 400,
       buttons: [{
            text: "Continue",
            "id": "btnOk",
            click: function () {
                var action = $( this ).data("action");
                var id = $( this ).data("id");
                var url = "{{ url('catalogs/action') }}" + "/"  + action + "/" + id;
                
                $.get(url, function(data, status){
 //                   alert("Data: " + data + "\nStatus: " + status);
                    $("#catalog_" + id ).remove();
                });
                                          
                $( this ).dialog( "close" );
            },

        }, {
            text: "Cancel",
            "id": "btnCancel",
            click: function () {
                $( this ).dialog( "close" );
           },
        }],
    });

    
    function NavigateTo(id)
    {
        var message = "";
        var el = "catalog_action_menu_" + id;
        var action = document.getElementById(el).value;
        $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button:first').hide();
        $("#btnCancel").html('<span class="ui-button-text">'+ "Close" +'</span>')
        switch (action) {
            case "add_to_catalog":
            case "update_catalog":
            case "full_service":
                message = "Catalog update started.";
                break;
            case "clean_catalog":
                message = "Catalog cleaning started.";
                break;
            case "gather_art":
                message = "Media Art Search started.";
                break;
            case "delete_catalog":
                message = "The catalog will be permanently deleted. Are you sure?";
                $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button:first').show();
                $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button:last').show();
                $("#btnCancel").html('<span class="ui-button-text">'+ "Cancel" +'</span>')
                break;
            default:
        }
        document.getElementById("alert").innerHTML = message;
        $("#dialog-confirm").data("action", action );
        $( "#dialog-confirm" ).data( "id", id );
        $( "#dialog-confirm" ).dialog( "open" );
     }


    </script>
        
 @endsection
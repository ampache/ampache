@extends('layouts.app')

@section('content')
        
 <div id="catalog_show" class="w3-display-container w3-black">
    <div class="row">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Catalogs') }}</h3>
            </div>
            <br />
            <table id="catalog_table" class="w3-table" cellpadding="0" cellspacing="0" data-objecttype="user">
               <thead>
                     <tr>
                        <th class="cel_catalog essential persist">{!! __('Name') !!}</th>
                        <th class="cel_info essential">{!! __('Info') !!}</th>
                        <th class="cel_lastverify optional">{!! __('Last Verify') !!}</th>
                        <th class="cel_lastadd optional">{!! __('Last Add') !!}</th>
                        <th class="cel_lastclean optional ">{!! __('Last Clean') !!}</th>
                        <th class="cel_action cel_action_text essential">{!! __('Actions') !!}</th>
                    </tr>
                </thead>
                @if (isset($Catalogs))
                    @foreach ($Catalogs as $catalog)
                     @php
                        $icon = $catalog->enabled ? 'disable' : 'enable';
                      @endphp
                        <tbody>
               		    <tr class="w3-hover-red" id="catalog_{!! $catalog->id !!}">
                 		<td style="cursor: pointer;" class="cel_catalog" onclick="dialogEdit('{!! $catalog->catalog_id !!}', '{!! $catalog->name !!}', 
                     		'{!! $catalog->path !!}', 'catalog-edit')">
                 		<a id="name" href="#" title="Edit Name">{!! $catalog->name !!}</a> </td>
                        <td class="cel_info">{!! e($catalog->path) !!}</td>
                        <td class="cel_lastverify">{!! e($catalog->f_update) !!}</td>
                        <td class="cel_lastadd">{!! e($catalog->f_add) !!}</td>
                        <td class="cel_lastclean">{!! e($catalog->f_clean) !!}</td>
                        <td class="cel_action cel_action_text">
                       @if (!$catalog->isReady())
                        <a href="{{ url('catalog/add_to_catalog') . "/" . $catalog->catalog_id }}"><b>{{ __('Make it ready ...') }}</b></a><br />
					   @endif
                  <form id="form_{!! $catalog->id !!}" >
                      <select id="catalog_action_menu_{!! $catalog->catalog_id !!}">
                      @if ($catalog->isReady())
                         <option value="add_to_catalog">{!! __('Add') !!}</option>
                         <option value="update_catalog">{!! __('Verify') !!}</option>
                         <option value="clean_catalog">{!! __('Clean') !!}</option>
                         <option value="full_service">{!! __('Update') !!}</option>
                         <option value="gather_art">{!! __('Gather Art') !!}</option>
                      @endif
                    <option value="delete_catalog">{!! __('Delete') !!}</option>
                   </select>
                  <input type="button" onClick="NavigateTo({!! $catalog->catalog_id !!})" value="{!! __('Go') !!}">
                   @if (config('catalog.catalog_disable')) 
                  <span>
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
            </tbody>
            </table>
        </div>
        <div id="dialog-confirm"> <div id="alert"></div>
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
            text: "OK",
            "id": "btnOk",
            click: function () {
                var action = $( this ).data("action");
                var url = $( this ).data("url");
                var id = $( this ).data("id");
                if (action == "delete_catalog") {
                    var rowcount = $('#catalog_table >tbody >tr').length;
                    $.get(url, function(data, status){
                        if (rowcount > 1) {
                           $("#catalog_" + id).remove();
                        } else {
                            $('#catalog_table >tbody >tr').eq(0).html("<h3>No Catalogs</h3>");
                        }
                    });
                }                
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
        $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button:last').hide();
        var url = "{{ url('catalogs/action') }}" + "/"  + action + "/" + id;
        switch (action) {
            case "add_to_catalog":
            case "update_catalog":
            case "full_service":
                message = "Catalog update started...";
                sendAction(url);
                break;
            case "clean_catalog":
                message = "Catalog cleaning started.";
                sendRest(url);
                break;
            case "gather_art":
                message = "Media Art Search started.";
                sendRest(url);
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
        $("#dialog-confirm").data("url", url );       
        $("#dialog-confirm").data("action", action );
        $( "#dialog-confirm" ).data( "id", id );
        $( "#dialog-confirm" ).dialog( "open" );
     }

    function sendAction(url) {
         $.get(url, function(data, status){
        });
        return status;
    }


    </script>
        
 @endsection
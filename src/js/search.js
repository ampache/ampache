$.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderItem: function( ul, item ) {
        var itemhtml = "";
        if (item.link !== '') {
            itemhtml += "<a href='" + item.link + "'>";
        } else {
            itemhtml += "<a>";
        }
        if (item.image !== '') {
            itemhtml += "<img src='" + item.image + "' class='searchart' alt=''>";
        }
        itemhtml += "<span class='searchitemtxt'>" + item.label + ((item.rels === '') ? "" : " - " + item.rels) + "</span>";
        itemhtml += "</a>";

        return $( "<li class='ui-menu-item'>" )
            .data("ui-autocomplete-item", item)
            .append( itemhtml + "</li>")
            .appendTo( ul );
    },
    _renderMenu: function( ul, items ) {
        var that = this, currentType = "";
        $.each( items, function( index, item ) {
            if (item.type !== currentType) {
                $( "<li class='ui-autocomplete-category'>")
                    .data("ui-autocomplete-item", item)
                    .append( item.type + "</li>" )
                    .appendTo( ul );

                currentType = item.type;
            }
            that._renderItem( ul, item );
        });
    }
});

$(function() {
    var minSearchChars = 2;
    $( "#searchString" )
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "custom-catcomplete" ).widget().is(":visible") ) {
                event.preventDefault();
            }
        })
        // reopen previous search results
        .bind( "click", function( event ) {
            if ($( this ).val().length >= minSearchChars) {
                $( this ).data( "custom-catcomplete" ).search();
            }
        })
        .catcomplete({
            source: function( request, response ) {
                $.getJSON( jsAjaxUrl, {
                    page: 'search',
                    action: 'search',
                    target: $('#searchStringRule').val(),
                    search: request.term,
                    xoutput: 'json'
                }, response );
            },
            search: function() {
                // custom minLength
                if ($( this ).val().length < minSearchChars) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                if (event.keyCode === $.ui.keyCode.ENTER) {
                    NavigateTo(ui.item.link);
                }

                return false;
            }
        });
});
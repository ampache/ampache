@extends('layouts.app')

@section('content')
	<div class="container">
		<h3 class="box-title">Compose Message</h3>
		<form name="label" method="post" action="http://192.168.2.3/pvmsg.php?action=add_message">
			<div class="mediaTableWrapper gt100 gt150 gt200 gt250 gt300 gt350 gt400 gt450 gt500 gt550 gt600 gt650 gt700 gt750 gt800 gt850 gt900" data-respond="">
			<table class="tabledata activeMediaTable" id="MediaTable-0" cellspacing="0" cellpadding="0">
				<tbody>
					<tr>
						<td>Recipient</td>
						<td>
							<input name="to_user" value="ernie" id="pvmsg_to_user" class="ui-autocomplete-input" autocomplete="off" type="text">
						</td>
					</tr>
					<tr>
						<td>Subject</td>
						<td>
							<input name="subject" value="" type="text">
						</td>
					</tr>
					<tr>
						<td>Message</td>
						<td>
							<textarea name="message" cols="64" rows="10"></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			</div>
	<div class="formValidation">
<input name="form_validation" value="2ac78c23722647e42647f6c436fcd6d2" type="hidden">    <input class="button" value="Send" type="submit">
</div>
</form>
<script type="text/javascript">
$(function() {
    $( "#pvmsg_to_user" ).catcomplete({
        source: function( request, response ) {
            $.getJSON( jsAjaxUrl, {
                page: 'search',
                action: 'search',
                target: 'user',
                search: request.term,
                xoutput: 'json'
            }, response );
        },
        search: function() {
            // custom minLength
            if (this.value.length < 2) {
                return false;
            }
        },
        focus: function() {
            // prevent value inserted on focus
            return false;
        },
        select: function( event, ui ) {
            if (ui.item != null) {
                $(this).val(ui.item.value);
            }
            return false;
        }
    });
});
    </script>
    </div>
@endsection

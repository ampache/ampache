@extends('layouts.app')

@section('content')
    <div class="container">
        <div id="browse_content_pvmsg" class="browse_content">
          <h3>Private Messages</h3>
          <br>
             Total: {{ $messages->count() }} 
     <div class="table-responsive">          
   <table id="pvtmsgtable" class="table tabledata">
    <thead>
        <tr class="th-top">
            <th style="color:#ff9d00;" class="cel_select essential persist">{{ T_('Select') }}</th>
            <th style="color:#ff9d00; cursor: pointer;" class="cel_subject essential persist" onclick="sortTable(0)">{{ T_('Subject') }}</th>
            <th style="color:#ff9d00; cursor: pointer;" class="cel_sender essential persist" onclick="sortTable(1)">{{ T_('Sender') }}</th>
            <th style="color:#ff9d00; cursor: pointer;" class="cel_from_user essential" onclick="sortTable(2)">{{ T_('Recipient') }}</th>
            <th style="color:#ff9d00; cursor: pointer;" class="cel_date essential" onclick="sortTable(3)">{{ T_('Date') }}</th>
            <th style="color:#ff9d00;" class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
            <tbody>
                    @foreach ($messages as $msg)
                    <tr class="<?php echo App\Support\UI::flip_class(); ?>">
						<td class="cel_select"><input type="checkbox" name="pvmsg_select[]" value="{{ $msg->id }}" title="{{ T_('Select') }}"/></td>
						<td class="cel_subject">{{ $msg->subject }}</td>
						<td class="cel_sender">{{ $privateMsg->senderName($msg->from_user_id)  }}</td>
						<td class="cel_recipient">{{ $privateMsg->recipientName($msg->to_user_id)  }}</td>
						<td class="cel_message_date">{{ $privateMsg->messageDate($msg->id)  }}</td>
                            <td class="cel_action" headers="MediaTable-0-mediaTableCol-5">
					    <table>
    				    	<tr>
                                <td>                        		 			
			        			    <form id="reply{{ $msg->id }}" action="{{ url('/messages/reply/'.$msg->id) }}" method="POST">
   					    				{{ csrf_field() }}
						        	    <a href="javascript:replyMessage('{{ $msg->id }})"><img id="reply_to" src="{{ url_icon('reply') }}" title="{{ T_('Reply tp') }}"/></a>
                                    </form>
    					        </td>
                                <td>
						            <form id="delete{{ $msg->id }}" action="{{ url('/messages/destroy/'.$msg->id) }}" method="POST">
   								    {{ method_field('DELETE') }}
   								    {{ csrf_field() }}
   							            <a href="javascript:deleteMessage({{ $msg->id }})"><img src="{{ url_icon('delete') }}" title="{{ T_('Delete') }}" /></a>
                                     </form>
                                </td>
                            </tr>
                        </table>
					@endforeach
          </tbody>
         </table>
        </div>
	<div class="row" style="padding-top:50px">
          {{ $messages->links() }}
</div>
      </div>
          <div id="reply"><p><font face="Georgia" size="4"></font></p></div>           
      
    </div>
    <script>
	function deleteMessage(id) {
		if (confirm("Are you sure you want to permanently delete message?")) {
			document.getElementById(id).submit();
		}
	};

function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("pvtmsgtable");
  switching = true;
  //Set the sorting direction to ascending:
  dir = "asc";
  /*Make a loop that will continue until
  no switching has been done:*/
  while (switching) {
    //start by saying: no switching is done:
    switching = false;
    rows = table.getElementsByTagName("TR");
    /*Loop through all table rows (except the
    first, which contains table headers):*/
    for (i = 1; i < (rows.length - 1); i++) {
      //start by saying there should be no switching:
      shouldSwitch = false;
      /*Get the two elements you want to compare,
      one from current row and one from the next:*/
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /*check if the two rows should switch place,
      based on the direction, asc or desc:*/
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch= true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch= true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /*If a switch has been marked, make the switch
      and mark that a switch has been done:*/
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      //Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /*If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again.*/
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}

function replyMessage(username, id) {
	var url = "{{ url("messages/reply") }}";
	$("#reply").html("");
	$("#reply").css('overflow', 'hidden');
	$("#reply").data("id", id).dialog("option", "title", "Loading...").dialog("open");
	$("#reply").load(url + "/" + id.toString() + " #useredit");
	$("#reply").dialog("option", "title","Reply: " + username);
	$("#reply").dialog("option", id);
	}

</script>
@stop
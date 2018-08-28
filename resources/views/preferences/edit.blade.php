<!-- \resources\views\preferences\index.blade.php -->

@extends('layouts.app')

@section('title', '| Preferences Index')

@section('content')
 <div id="preferences_edit" class="w3-display-container w3-black">
    <div class="w3-row">
      <div class="w3-col m4 l3">
         <h3 class="panel-title">{{ __('Editing System Configuration') }}</h3>
      </div>
    </div>
    <div class="w3-row">
      <div class="w3-col m4 l3">
         <h4>Interface</h4>
      </div>
    </div>
   <div class="w3-row">
<table id="user_table" class="w3-table" style="width: 70%">
    <thead >
       <tr>
           <th>Preference</th>
           <th>Value</th>
           <th>Permissions</th>
           <th>Update</th>
       </tr>
    </thead>
             @inject('UserPreferences', 'App\Services\UserPreferences')
    <tbody>
    @foreach ($preferences as $preference)
        <tr>
            <td style="width:450px">
             {{ $preference->description }}      
            </td>
            <td style="width:250px">
             <form id="value_{{ $preference->id }}" action="/action_page.php" enctype="multipart/form-data">
               @csrf
              @method('PUT')
               {{ $UserPreferences->create_preference_input($preference->name, $preference->value, $preference->id) }}             
             </form>
            </td>
    <td>
    @php
      $current_preference = App\Models\preference::find($preference->id);
      $current_roles = $current_preference->roles;
      $t = $current_roles->where('name', 'Administrator');
      $x = 0;
    @endphp
      <form id="permissions_{{ $preference->id }}" action="/action_page.php" enctype="multipart/form-data">
      <div class="w3-container w3-text-black">
      <div class="ms-options-wrap ms-active">
        <button type="button" class="roles">
            <span>Select Roles</span>
        </button>
        <div id="div{!! $preference->id !!}" class="ms-options" style="max-width: 200px; max-height: 200px;display:none">
            @foreach ($roles as $role)
                <input title="{{ $role->name }}" id="ms-opt-{{ ($x += 1) . "-" . $preference->id }}" value="1" type="checkbox">
                <label for="ms-opt-{{ $x . "-" . $preference->id }}">{{ $role->name }}</label><br>
            @endforeach
         </div>
       </div>
    </div>
          </form>
     </td>
    <td>
             <button style="display:none;" id="update_{{$preference->id }}" class="w3-white" title="Update Preferences">Update</button>
    </td>
      </tr>
    @endforeach
    
    </tbody>
</table>   
</div>
</div>rom
{{ $preferences->links() }}
<script>

$("button.w3-white").on("click", function()
    {
        if (this.innerText == "Update") {;
            var id = this.id;
            var pos = id.indexOf("_");
            var update_id = id.slice(pos+1, id.length);
            var preferenceName = $("#name_" + update_id).attr('name');
            var data = $("#value_" + update_id).serialize()  + "&" + $("#permissions_" + update_id).serialize();
            $.ajax({
                url: "{{ url('/preference') }}" + "/" + update_id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },          
                type: 'POST',
 //               dataType: 'json',
                data: data,
                success : function (result) {
                    $("#alert").append(json.response_alert);
                },
                error : function (result) {
                    console.log(result);
                }
            })
        }
    });
$("button.roles").bind("click", function(){
    var sibling = this.nextElementSibling;
    $("#" + sibling.id).toggle();
});

$("div.ms-options").on({
    mouseenter: function(){
        $(this).show();
    },
    mouseleave: function(){
       $(this).hide();
    }
});

$(":checkbox").on("change", function(){
    var sibling = this.offsetParent.previousElementSibling;
    if (this.checked == true) {
        var innerText = sibling.innerText;
        if (innerText.search("Select Roles") > -1) {
            innerText = innerText.replace("Select Roles","");
        }
        var result = innerText.concat(",", this.title);
        sibling.innerText = result;
    } else {

    }
        
    var id = s.slice(3);
    var parent_Id = "";
    $("#update_" + id).show();
});

</script>
@endsection
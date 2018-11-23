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
         <h4 style="text-decoration:underline">{!! title_case($title) !!}</h4>
      </div>
    </div>
   <div class="w3-row">
<table id="user_table" class="w3-medium" style="width: 60%">
    <thead >
       <tr>
           <th>Preference</th>
           <th>Value</th>
           <th>Permissions</th>
       </tr>
    </thead>
             @inject('PreferenceService', 'App\Services\PreferenceService')
    <tbody>
      @php $item = $preferences->first(); 
         $currentSubcategory = '';
      @endphp
    @foreach ($preferences as $preference)
        <tr>
        @if (($preference->subcategory !== $currentSubcategory))
            <td class="w3-text-orange"> {!! ucfirst($preference->subcategory) !!}</td>
            <td></td>
            <td></td>
            </tr>
            <tr>
         @endif
            <td style="width:450px">    
             {{ $preference->description }}  
            </td>
            <td style="width:300px">
             <form id="value_{{ $preference->id }}" action="/action_page.php" enctype="multipart/form-data">
              @method('PUT')
               {{ $PreferenceService->create_preference_input($preference->name, $preference->value, $preference->id) }}             
            </td>
            </form>
            <td>
        @php
          $current_roles = $preference->roles->pluck('name');
          $x = 0;
        @endphp
      <div class="ms-options-wrap ms-active">
        <button type="button" class="roles">
            <span>{{ ($current_roles->count() > 0) ? $current_roles->count() . " roles selected" : "Select Roles" }}</span>
        </button>
        <div id="div{!! $preference->id !!}" class="ms-options w3-text-black" style="max-width: 200px; max-height: 200px;display:none">
              <form id="form_{{ $preference->id }}" action="/action_page.php" enctype="multipart/form-data">
              @method('PUT')
            @foreach ($roles as $role)
                {{ Form::checkbox('roles[]',  $role->id, $current_roles->contains($role->name)) }}
                {{ Form::label($role->name, ucfirst($role->name)) }}<br>
            
                @php $x += 1; @endphp
            @endforeach
               </form>
          </div>
       </div>
    
     </td>
   </form>
    @php $currentSubcategory = $preference->subcategory; @endphp
      </tr>
    @endforeach
    
    </tbody>
</table>   
</div>
</div>
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
$(document).ready(function(){
    $(":button.roles").siblings().filter(function(){
        var currentButton = $(":button.roles");
        var total = 0;
        $(this).children().each(function() {
        if (this.checked == true) total+=1;
        });
    });
    var sibling = undefined;
    setRoleCount(sibling);
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

function setRoleCount(sibling) {

    if (sibling !== undefined) {
        var id = sibling.nextElementSibling.firstElementChild.id;
        var roles =  $("#" + id).find("input:checkbox:checked");
        displayRoleCount(sibling, roles.length);
        return;
    }   
    var options = $("div.ms-options-wrap").find("div.ms-options > form");
    $.each(options, function(k, v){
        var roles = $(this).find("input:checkbox:checked");
          sibling = this.parentElement.previousElementSibling;
          displayRoleCount(sibling, roles.length);       
      });
}

function displayRoleCount(sibling, count) {
    if (count > 1 ) {
        sibling.innerText = count + " roles selected";        
     } else if (count == 1) {
         sibling.innerText = count + " role selected";
     } else {
         sibling.innerText = "Select Roles";        
     }
}

$(":checkbox").on("change", function(){
    var id = this.form.id;
    var pos = id.indexOf("_");
    var update_id = id.slice(pos+1, id.length);
     if (this.parentNode.className !== 'switch') {
         var sibling = this.offsetParent.previousElementSibling;
         setRoleCount(sibling);
        var data = $("#form_" + update_id).serialize();
    } else {
        var preferenceName = this.name;
        if (this.checked) {
            this.nextElementSibling.title  = "Enabled";
        } else {
            this.nextElementSibling.title = "Disabled";
        }
        var data = $("#value_" + update_id + " :input").serialize() + "&" + preferenceName + "=" + this.checked;
    }
        $.ajax({
            url: "{{ url('/preference') }}" + "/" + update_id,
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


$(":text").on("blur", function() {
  if (this.value !== this.defaultValue) {
      var id = this.form.id;
      var pos = id.indexOf("_");
      var update_id = id.slice(pos+1, id.length);
      var data = $("#value_" + update_id + " :input").serialize();
      $.ajax({
          url: "{{ url('/preference') }}" + "/" + update_id,
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },          
          type: 'POST',
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


$("select").on("blur", function() {
    if (this.value !== this.defaultValue) {
        var id = this.form.id;
        var pos = id.indexOf("_");
        var update_id = id.slice(pos+1, id.length);
        var data = $("#value_" + update_id + " :input").serialize();
        $.ajax({
            url: "{{ url('/preference') }}" + "/" + update_id,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },          
            type: 'POST',
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
</script>
@endsection
{{-- /resources/views/catalogs/create.blade.php --}}
@extends('layouts.app')

@section('title', '| Add Catalog')

@section('content')
@if (session('status'))
<div class="w3-container">
<div class="w3-panel w3-red w3-display-container">
  <span onclick="this.parentElement.style.display='none'"
  class="w3-button w3-red w3-large w3-display-topright">&times;</span>
  <h3>Error!</h3>
     <p>{{ session('status') }}</p>
</div>
</div>
@endif
<div id="catalog_create" class="w3-display-container w3-black w3-section">
    <div  class="w3-card" style="text-align: center;"><h3>Add A Catalog</h3>
    </div>
    
    <div  class="w3-card"><p style="font-family:DejaVuSansCondensed,Helvetica,Arial,sans-serif">In the form below enter either a local path (i.e. /data/music)
    or the URL to a remote Ampache installation (i.e http://theotherampache.com)</p>
    </div>

<div class="w3-container  w3-center  w3-black">
<form name="catalog_form" id="editForm" method="post" action="{{ url('/catalogs/store') }}" data-parsley-validate='' enctype='multipart/form-data'>
@csrf
    <table class="w3-table w3-small">
    <tr>
      <td>Catalog Name:</td>
      <td>
          <input id="catalog_name" required="" type="text" class="w3-round" name="catalog_name" value="{{ old('catalog_name') }}">
      </td>
      <td style="vertical-align:top; font-family: monospace;" rowspan="4" id="patterns_example">
          <strong>Auto-inserted Fields:</strong><br>
          <span class="format-specifier">%A</span> = album name<br>
          <span class="format-specifier">%a</span> = artist name<br>
          <span class="format-specifier">%c</span> = id3 comment<br>
          <span class="format-specifier">%T</span> = track number<br> (padded with leading 0)<br>
          <span class="format-specifier">%t</span> = song title<br>
          <span class="format-specifier">%y</span> = year<br>
          <span class="format-specifier">%o</span> = other<br>
      </td>
    </tr>
    <tr>
      <td>Catalog Type:</td>
      <td>
          <select required="" data-parsley-error-message="Please select a catalog type" class = 'catalog_type' name='catalog_type' id='catalog_type' onChange='catalogTypeChanged();'>
              {!! $sel_types!!}
            </select>
      </td>
    </tr>
    <tr>
      <td>Filename Pattern:</td>
      <td>
      <div class="form-group rename-pattern">
         <div class="w3-content">
             <input id="rename_pattern" required="" class="w3-round" name="rename_pattern" value="%a/%A" type="text" value="{{ old('rename_pattern') }}">
         </div>
        </div>
      </td>
    </tr>
    <tr>
      <td>Folder Pattern:</td>
      <td>
      <div class="form-group sort-pattern">
             <input id="sort_pattern" required="" class="w3-round" name="sort_pattern" value="%a/%A" type="text" value="{{ old('sort_pattern') }}">
       </div>
      </td>
    </tr>
    <tr>
      <td>Gather Art:</td>
      <td>
         <div class="form-group">
           <input id="gather_art" value="1" checked type="checkbox" name="gather_art" value="{{ old('gather_art') }}">
      </div>
      </td>
    </tr>
    <tr>
      <td>Build Playlists from playlist Files<br> (m3u, m3u8, asx, pls, xspf):</td>
      <td>
      <div class="form-group">
          <input id="parse_playlist" value="1" type="checkbox" name="parse_playlist" value="{{ old('parse_playlist') }}">
       </div>    
      </td>
    </tr>
    <tr>
      <td>Gather media types:</td>
      <td>
      <div class="form-group catalog-media">
         <select id="gather_media" name="gather_media" data-parsley-required>
            <option value="music">Music</option>
            <option value="clip">Music Clip</option>
            <option value="tvshow">TV Show</option>
            <option value="movie">Movie</option>
            <option value="personal_video">Personal Video</option>
            <option value="podcast">Podcast</option>
          </select>
         <div class="messages"></div>
        </div>
      </td>
    </tr>
    <tr>
      <td>Catalog Owner:</td>
      <td>
      <div class="form-group">
        <select id="catalog_owner" name="catalog_owner">
        	@foreach ($Users as $user)
              <option value="{{ $user->id }}">{{ $user->username }}</option>
            @endforeach
        </select>
     <div class="messages"></div>
       </div>
      </td>
     </tr>
  </table>
  <div id="catalog_type_fields" class="form-group">
  </div>
  <div class="w3-container w3-margin-top" style="margin-left: 32%; margin-right: auto;width: 20%;">
           <input class="w3-button w3-orange w3-small" value="Add Catalog" type="submit">
  </div>
 </form>
</div>
<script>

$(function () {
    $('#editForm').parsley().on('field:validated', function() {
      var ok = $('.parsley-error').length === 0;
      $('.bs-callout-info').toggleClass('hidden', !ok);
      $('.bs-callout-warning').toggleClass('hidden', ok);
    })
    .on('form:submit', function() {
      return true;
    });
  });

</script>
</div>
@endsection
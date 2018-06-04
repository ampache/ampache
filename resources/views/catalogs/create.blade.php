{{-- /resources/views/catalogs/create.blade.php --}}
@extends('layouts.app')

@section('title', '| Add Catalog')

@section('content')
<div id="catalog_create" class="w3-display-container w3-black w3-section">
  <div class="w3-container w3-cell w3-center">
    @if (session('status'))
     <div class="alert alert-success">
        {{ session('status') }}
     </div>
    @endif
    </div>    
    <div>In the form below enter either a local path (i.e. /data/music)<br>
    or the URL to a remote Ampache installation (i.e http://theotherampache.com)</div>

<div class="w3-container  w3-center  w3-black">
<form name="catalog_form" id="editForm" method="post" action="{{ url('/catalogs') }}" novalidate>
@csrf
    <table class="w3-table w3-small">
    <tr>
      <td>Catalog Name:</td>
      <td>
       <div class="form-group">
          <input id="catalog_name" type="text" class="w3-round" name="catalog_name" value="{{ old('catalog_name') }}">
          <div class="messages w3-text-red"></div>
      </div>
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
      <div class="form-group">
          <select class = 'catalog_type' name='catalog_type' id='catalog_type' onChange='catalogTypeChanged();' required>
          @foreach ($sel_types as $type)
              <option>{!! $type !!}</option>
          @endforeach
            </select>
        <div class="messages"></div>
      </div>
      </td>
    </tr>
    <tr>
      <td>Filename Pattern:</td>
      <td>
      <div class="form-group rename-pattern">
         <div class="w3-content">
             <input id="rename_pattern" class="w3-round" name="rename_pattern" value="%a/%A" type="text" value="{{ old('rename_pattern') }}">
         </div>
          <div class="messages"></div>
       </div>
      </td>
    </tr>
    <tr>
      <td>Folder Pattern:</td>
      <td>
      <div class="form-group sort-pattern">
             <input id="sort_pattern" class="w3-round" name="sort_pattern" value="%a/%A" type="text" value="{{ old('sort_pattern') }}">
          <div class="messages"></div>
       </div>
      </td>
    </tr>
    <tr>
      <td>Gather Art:</td>
      <td>
         <div class="form-group">
           <input id="gather_art" value="1" checked type="checkbox" name="gather_art" value="{{ old('gather_art') }}">
         <div class="messages"></div>
      </div>
      </td>
    </tr>
    <tr>
      <td>Build Playlists from playlist Files<br> (m3u, m3u8, asx, pls, xspf):</td>
      <td>
      <div class="form-group">
          <input id="parse_playlist" value="1" type="checkbox" name="parse_playlist" value="{{ old('parse_playlist') }}">
          <div class="messages"></div>
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
 </form>
</div>
<script>



</script>
</div>
@endsection
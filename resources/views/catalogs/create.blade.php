{{-- /resources/views/catalogs/create.blade.php --}}
@extends('layouts.app')

@section('title', '| Add Calalog')

@section('content')
<div class="w3-container w3-section  w3-black">
    <h4><i class="fa fa-database"></i> Add catalog</h4>
    <div>In the form below enter either a local path (i.e. /data/music)<br>
    or the URL to a remote Ampache installation (i.e http://theotherampache.com)</div>
<form name="update_catalog" method="post" action="{{ url('/catalogs') }}" enctype="multipart/form-data">
{{ csrf_field() }}
<div class="w3-container  w3-center">
   <table class="w3-table w3-small" style="width:60%">
    <tr>
      <td>Catalog Name:</td>
      <td><input type="text" class="w3-round"></td>
      <td style="vertical-align:top; font-family: monospace;" rowspan="6" id="patterns_example">
                <strong>Auto-inserted Fields:</strong><br>
                <span class="format-specifier">%A</span> = album name<br>
                <span class="format-specifier">%a</span> = artist name<br>
                <span class="format-specifier">%c</span> = id3 comment<br>
                <span class="format-specifier">%T</span> = track number (padded with leading 0)<br>
                <span class="format-specifier">%t</span> = song title<br>
                <span class="format-specifier">%y</span> = year<br>
                <span class="format-specifier">%o</span> = other<br>
            </td>
    </tr>
    <tr>
      <td>Catalog Type:</td>
      <td>
          <select name="type" style="color:#333" id="catalog_type" onChange="catalogTypeChanged();">
            <option value="none" selected>[select]</option>
            @foreach ($catalogs as $catalog)
              <option value="{{ $catalog->get_type() }}">{{ $catalog->get_type() }}</option>
            @endforeach
          </select>
        </td>
    </tr>
    <tr>
      <td>Filename Pattern:</td>
      <td><input class="w3-round" name="sort_pattern" value="%a/%A" type="text"></td>
    </tr>
    <tr>
      <td>Gather Art:</td>
      <td><input name="gather_art" value="1" checked type="checkbox"></td>
    </tr>
    <tr>
      <td>Build Playlists from playlist Files<br> (m3u, m3u8, asx, pls, xspf):</td>
      <td><input name="parse_playlist" value="1" type="checkbox"></td>
    </tr>
    <tr>
      <td>Gather media types:</td>
      <td>
         <select name="gather_media">
            <option value="music">Music</option>
            <option value="clip">Music Clip</option>
            <option value="tvshow">TV Show</option>
            <option value="movie">Movie</option>
            <option value="personal_video">Personal Video</option>
            <option value="podcast">Podcast</option>
          </select>
      </td>
    </tr>
  </table>
</div>
<div class="formValidation">
        <input name="action" value="add_catalog" type="hidden">
        <input name="form_validation" value="170f38f43795d499a4de7e15fe1f1051" type="hidden">
        <input class="button" value="Add Catalog" type="submit">
    </div>
</form>
</div>
@endsection
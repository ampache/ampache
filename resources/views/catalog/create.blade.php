@extends('layouts.app')

@section('content')
<div class="row">
    <div class="panel-heading">{{ T_('Add Catalog') }}</div>
    <div class="panel-body">
        <p>{{ T_("In the form below enter either a local path (i.e. /data/music) or the URL to a remote Ampache installation (i.e http://theotherampache.com)") }}</p>
        <form name="update_catalog" method="post" action="{{ url('/catalog/create') }}" enctype="multipart/form-data">
            <table class="tabledata" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 25%;">{{ T_('Catalog Name') }}: </td>
                    <td>
                        <input type="text" name="name" value="{{ old('name') }}" />
                        @if ($errors->has('name'))
                            <span class="help-block">
                                <strong>{{ $errors->first('name') }}</strong>
                            </span>
                        @endif
                    </td>
                    <td style="vertical-align:top; font-family: monospace;" rowspan="6" id="patterns_example">
                        <strong>{{ T_('Auto-inserted Fields') }}:</strong><br />
                        <span class="format-specifier">%A</span> = {{ T_('album name') }}<br />
                        <span class="format-specifier">%a</span> = {{ T_('artist name') }}<br />
                        <span class="format-specifier">%c</span> = {{ T_('id3 comment') }}<br />
                        <span class="format-specifier">%T</span> = {{ T_('track number (padded with leading 0)') }}<br />
                        <span class="format-specifier">%t</span> = {{ T_('song title') }}<br />
                        <span class="format-specifier">%y</span> = {{ T_('year') }}<br />
                        <span class="format-specifier">%o</span> = {{ T_('other') }}<br />
                    </td>
                </tr>
                <tr>
                    <td>{{ T_('Catalog Type') }}: </td>
                    <td>
                        <script language="javascript" type="text/javascript">
                            var type_fields = new Array();
                            type_fields['none'] = '';
                            @foreach (Catalog::getCatalogModules() as $catalog)
                                type_fields['{{ $catalog->getType() }}'] = '';
                                @if ($catalog->hasHelp())
                                    type_fields['{{ $catalog->getType() }}'] += '<tr><td></td><td>{{ $catalog->getHelp() }}</td></tr>';
                                    @foreach ($catalog->getFields() as $key=>$field)
                                        type_fields['{{ $catalog->getType() }}'] += '<tr><td style="width: 25%;">{{ $field['description'] }}:</td><td>';
                                        @if ($field['type'] === 'checkbox')
                                            type_fields['{{ $catalog->getType() }}'] += '<input type="checkbox" name="{{ $key }}" value="1" {{ $field['value'] ? 'checked' : '' }} />';
                                        @elseif ($field['type'] === 'password')
                                            type_fields['{{ $catalog->getType() }}'] += '<input type="password" name="{{ $key }}" value="{{ $field['value'] }}"/>';
                                        @else
                                            type_fields['{{ $catalog->getType() }}'] += '<input type="text" name="{{ $key }}" value="{{ $field['value'] }}" />';
                                        @endif
                                        type_fields['{{ $catalog->getType() }}'] += '</td></tr>';
                                    @endforeach
                                @endif
                            @endforeach

                            function catalogTypeChanged() {
                                var sel = document.getElementById('catalog_type');
                                var seltype = sel.options[sel.selectedIndex].value;
                                var ftbl = document.getElementById('catalog_type_fields');
                                ftbl.innerHTML = '<table class="tabledata" cellpadding="0" cellspacing="0">' + type_fields[seltype] + '</table>';
                            }
                        </script>
                        <select name="type" id="catalog_type" onChange="catalogTypeChanged();">
                            @foreach (Catalog::getCatalogModules() as $catalog)
                                <option value="{{ $catalog->getType() }}">{{ $catalog->getType() }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>{{ T_('Filename Pattern') }}: </td>
                    <td>
                        <input type="text" name="rename_pattern" value="{{ old('rename_pattern') }}" />
                        @if ($errors->has('rename_pattern'))
                            <span class="help-block">
                                <strong>{{ $errors->first('rename_pattern') }}</strong>
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>{{ T_('Folder Pattern') }}:<br />{{ T_("(no leading or ending '/')") }}</td>
                    <td valign="top">
                        <input type="text" name="sort_pattern" value="{{ old('sort_pattern') }}" />
                        @if ($errors->has('sort_pattern'))
                            <span class="help-block">
                                <strong>{{ $errors->first('sort_pattern') }}</strong>
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td valign="top">{{ T_('Gather Art') }}:</td>
                    <td><input type="checkbox" name="gather_art" value="1" checked /></td>
                </tr>
                <tr>
                    <td valign="top">{{ T_('Build Playlists from playlist Files') }} (m3u, m3u8, asx, pls, xspf):</td>
                    <td><input type="checkbox" name="parse_playlist" value="1" /></td>
                </tr>
                <tr>
                    <td valign="top">{{ T_('Gather media types') }}:</td>
                    <td>

                        <select name="gather_media">
                            <option value="music">{{ T_('Music') }}</option>
                            @if (Config::get('feature.allow_video'))
                                <option value="clip">{{ T_('Music Clip') }}</option>
                                <option value="tvshow">{{ T_('TV Show') }}</option>
                                <option value="movie">{{ T_('Movie') }}</option>
                                <option value="personal_video">{{ T_('Personal Video') }}</option>
                            @endif
                            @if (Config::get('feature.podcast'))
                                <option value="podcast">{{ T_('Podcast') }}</option>
                            @endif
                        </select>
                    </td>
                </tr>
            </table>
            <div id="catalog_type_fields">
            </div>
            <div class="formValidation">
                <input type="hidden" name="action" value="add_catalog" />
                <input class="button" type="submit" value="{{ T_('Add Catalog') }}" />
            </div>
        </form>
    </div>
</div>
@endsection
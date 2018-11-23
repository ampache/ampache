<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

?>
@extends('layouts.app')

@section('content')


<div id="catalog-edit" class="w3-display-container w3-black w3-section">
<form id="edit-form" method="POST" action="{{ url('/catalogs') . '/' . $catalog->catalog_id }}">
    @method('PUT')
    @csrf
    <table class="w3-table" cellspacing="10" cellpadding="0">
        <tr>
            <td> {!! __('Name') !!}:</td>
            <td><input id="catalog_name" type="text" name="name" value="{{ $catalog->name }}" autofocus required></input></td>
            <td class="w3-padding" style="vertical-align:top; font-family: monospace;" rowspan="5">
                <strong>{!! __('Auto-inserted Fields') !!}:</strong><br />
                <span class="format-specifier">%A</span>= {!! __('album name') !!}><br />
                <span class="format-specifier">%a</span>= {!! __('artist name') !!}><br />
                <span class="format-specifier">%c</span>= {!! __('id3 comment') !!}<br />
                <span class="format-specifier">%T</span>= {!! __('track number (padded with leading 0)') !!} <br />
                <span class="format-specifier">%t</span>= {!! __('song title') !!} <br />
                <span class="format-specifier">%y</span>= {!! __('year') !!}<br />
                <span class="format-specifier">%o</span>= {!! __('other') !!}<br />
            </td>
        </tr>
        <tr>
            <td>{!! __('Catalog Type') !!}</td>
            <td>{{ (ucfirst($catalog->catalog_type)) }}</td>
        </tr>
        <tr>
            <td>{!! __('Filename pattern') !!}:</td>
            <td>
                <input id="rename_pattern" type="text" name="rename_pattern" value="{{ $catalog->rename_pattern }}" />
            </td>
        </tr>
        <tr>
            <td>
               {!! __('Folder Pattern') !!}:<br />{!! __('(no leading or ending \'/\')') !!}
            </td>
            <td>
                <input id="sort_pattern" type="text" name="sort_pattern" value="{{ $catalog->sort_pattern }}" />
            </td>
        </tr>
       <tr>
            <td>
                {!! __('Owner') !!}:<br />
            </td>
            <td>
               <select id="catalog_owner_menu" name="owner">
               @foreach ($Users as $owner)
               
               <option value="{!! $owner->id !!}" {!! $owner->id == $catalog->owner ? 'selected="selected"' : '' !!}}> {!! $owner->username !!}
               
               @endforeach
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="catalog_id" value="{{ $catalog->id }}" />
        <input type="hidden" name="action" value="update_catalog_settings" />
    </div>
 </div>
</form>

@endsection
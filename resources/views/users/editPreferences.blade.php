
{{-- \resources\views\preferences\userPreferences.blade.php --}}
@extends('layouts.app')

@section('title', '| User Preferences')

@section('content')
<div id="main-content" class="w3-container w3-section" style="margin-left:14%">
<div class="w3-display-container w3-black" style="height:500px;">
<div class="w3-display-middle" style="width:400px">

<h4><i class="fa fa-key"></i>{!! sprintf(__('Editing %s preferences'), $client->fullname) !!}</h4>
<hr>
<form method="post" name="preferences" action="{{ url('preferences', $client->id) }}" enctype="multipart/form-data">
@method('PUT')
<table class="w3-bordered w3-small">

<thead>
<tr>
<th>Permissions</th>
<th>Operation</th>
</tr>
</thead>
<tbody>
@inject('user_prefs', 'App\Services\UserPreferences')

@foreach ($preferences as $preference)
    <tr>
    <td>{{ __($preference['description']) }}</td>
    <td>
        {{ $user_prefs->create_preference_input($preference['name'], $preference['value']) }}
    </td>
    <td>    
    </td>
    </tr>
    @endforeach
    </tbody>
    </table>
        
    </div>
    </div>
    </div>
    
    @endsection
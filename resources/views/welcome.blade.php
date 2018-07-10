@extends('layouts.app')

@section('content')

<div class="w3-display-container w3-black w3-section" style="height:300px;">
  <div class="w3-padding w3-display-middle">
  <H3>Hello {!! $Name !!} Welcome to {{ config('app.name') }}.  You can now log in.</H3>

  </div>
</div>

@endsection
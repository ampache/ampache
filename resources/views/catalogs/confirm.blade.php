@extends('layouts.app')

@section('title', '| Catalog Confirmation')

@section('content')

<div class="w3-display-container" style="height:300px;">

<div id="edit-content" class="w3-display-middle">

<H3>{!! $text !!}</H3>
    <br />
    <br />
<a href="{!! $nextPath !!}" class="w3-button w3-orange">Continue</a>

@if ($cancel)
    
    <form method="get" action="{!! $nextURL() !!}" style="display:inline;">
        <input type="submit" value="(!! __('Cancel') !!}" />
    </form>
@endif

</div>
</div>
@endsection

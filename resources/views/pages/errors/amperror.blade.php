@extends('layouts.error')

@section('content')
    {{ AmpError::display('general') }}
@stop
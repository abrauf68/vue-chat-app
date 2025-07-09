@extends('layouts.app')

@section('content')
<div class="container">
    <group-component
        :current-user="{{ json_encode(auth()->user()) }}"
    ></group-component>
</div>
@endsection

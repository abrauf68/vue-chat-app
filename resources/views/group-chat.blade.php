@extends('layouts.app')
@section('content')
<div class="container">
    <group-chat-component
        :group="{{ json_encode($group) }}"
        :users="{{ json_encode($users) }}"
        :current-user="{{ json_encode(auth()->user()) }}"
    ></group-chat-component>
</div>
@endsection
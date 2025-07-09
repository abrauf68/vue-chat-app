@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <chat-component
                :user="{{ json_encode($user) }}"
                :current-user="{{ json_encode(auth()->user()) }}"
            ></chat-component>
        </div>
    </div>
</div>
@endsection

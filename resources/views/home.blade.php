@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="row">
                        @foreach($users as $user)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <a href="{{ route('chat', $user->id) }}">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $user->name }}</h5>
                                            <p class="card-text">{{ $user->email }}</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

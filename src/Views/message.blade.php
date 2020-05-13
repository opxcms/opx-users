@extends('site::layout')

@section('content')
    <div class="page-content">
        <div class="article">
            @if($error)
                <p class="message message__error">{{ $message }}</p>
            @else
                <p class="message">{{ $message }}</p>
            @endif
        </div>
    </div>
@endsection
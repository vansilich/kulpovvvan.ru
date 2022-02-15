@extends('layouts.app')

@section('content')
    @if(isset($error))
        <div class="alert alert-danger" role="alert">
            {{ $error }}
        </div>
    @endif
    @if(isset($success))
        <div class="alert alert-success" role="alert">
            {{ $success }}
        </div>
    @endif

    <h1>Отписка в Mailganer</h1>
    <form action="{{ route('mailganerUnsubHandle') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="emails">Вставьте имейлы</label>
            <textarea class="form-control" id="emails" rows="10" name="emails">{!! $fails ?? '' !!}</textarea>
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

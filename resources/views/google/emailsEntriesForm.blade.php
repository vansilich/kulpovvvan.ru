@extends('layouts.app')

@section('content')

    <h1>Поиск вхождений доменов в сообщения менеджеров</h1>

    <div class="text-light bg-secondary p-3 rounded">
        <h3>Документация</h3>
        <p>Принимает домены, разделленные метасимволами '\r\n':</p>
        <div class="bg-dark p-2 rounded-2">
            <p>kamaz.ru<code>\r\n</code><br>
                lunda.ru<code>\r\n</code>
            </p>
        </div>
        <p>Ищет вхождение принятых доменов и доменов на 1 уровень выше для каждого принятого домена и любые номера телефонов.
            Поиск происходит по отправленным и принятым имейлам всех менеджеров за все время</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger m-1">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(isset($success))
        <div class="alert alert-success" role="alert">
            {{ $success }}
        </div>
    @endif

    <form action="{{ route('emailsEntriesHandle') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="emails">Вставьте имейлы</label>
            <textarea class="form-control" id="emails" rows="10" name="emails">{!! $fails ?? '' !!}</textarea>
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

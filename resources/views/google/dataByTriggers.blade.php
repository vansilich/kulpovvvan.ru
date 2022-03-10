@extends('layouts.app')

@section('content')

    <h1>Поиск имейлов и телефонов по тригерам в имейлах менеджера</h1>

    <div class="text-light bg-secondary p-3 rounded">
        <h3>Документация</h3>
        <p>Принимает тригеры, разделленные  переносами строк (метасимволами '\r\n'):</p>
        <div class="bg-dark p-2 rounded-2">
            <p>#KLS0732980<code>\r\n</code><br>
                #KLS0733133<code>\r\n</code>
            </p>
        </div>
        <p>Ищет все имейлы и телефоны в письмах, в subject которых входят переданные тригеры</p>
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

    <form action="{{ route('triggersEntriesHandle') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="triggers">Вставьте тригеры</label>
            <textarea class="form-control" id="triggers" rows="10" name="triggers"></textarea>
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

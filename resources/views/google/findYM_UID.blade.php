@extends('layouts.app')

@section('content')


    <h1>Поиск YM UID в таблице FL-visitors</h1>

    <h3>Документация</h3>
    <p>Принимает .txt файл, второй столбец которого является значениями CLIENT_NAME. Исходный файл должен быть формата:</p>
    <p>756071ea-1710-11e4-bb86-6cf049d36190<code>\t</code>MN0003752<code>\r\n</code></p>

    <p>Пример:</p>
    <img src="{{asset('assets/img/docs/bigqueryFindYM_UID.png')}}" alt="">

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bigqueryFindYM_UIDHandle') }}" method="POST" enctype='multipart/form-data'>
        @csrf
        <div class="mb-3">
            <label for="txtFile" class="form-label">Прикрепите файл</label>
            <input class="form-control" type="file" id="txtFile" multiple name="txtFile">
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

@extends('layouts.app')

@section('content')
    @if(isset($error))
        <div class="alert alert-danger" role="alert">
            {{ $error }}
        </div>
    @endif

    <h1>Проверка txt</h1>

    <div class="text-light bg-secondary p-3 rounded">
        <h3>Документация</h3>
        <p>Принимает несколько .txt файлов из отчетности по имейлам менеджеров и скрепляет их в 1 .csv файл. Удаляет дубликаты имейлов, оставляя
            только 1 запись у первого попавшегося менеджера, либо у менеджера, в чьем файле было больше всего дубликатов.
            Файлы должны называться так:
            </p>
        <div class="bg-dark p-2 rounded-2">
            <p><code>{manager}</code>fluid-line.ru.txt</p>
        </div>
        <p>Где <b>{manager}</b> - ник менеджера (без скобок). Конечный файл содержит 2 столбца - ник менеджера и имейлы.</p>
    </div>

    <form action="{{ route('checkTxtHandle') }}" method="POST" enctype='multipart/form-data'>
        @csrf
        <div class="mb-3">
            <label for="txtFiles" class="form-label">Прикрепите файлы</label>
            <input class="form-control" type="file" id="txtFiles" multiple name="txtFiles[]">
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

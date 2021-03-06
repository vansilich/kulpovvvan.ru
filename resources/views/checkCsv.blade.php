@extends('layouts.app')

@section('content')
    @if(isset($error))
        <div class="alert alert-danger" role="alert">
            {{ $error }}
        </div>
    @endif

    <h1>Проверка csv</h1>

    <p>Принимает несколько .csv файлов из отчетности по имейлам менеджеров и скрепляет их в 1 .csv файл. Удаляет дубликаты имейлов, оставляя
        только 1 запись у первого попавшегося менеджера, либо у менеджера, в чьем файле было больше всего дубликатов.
        Конечный файл содержит 2 столбца - ник менеджера и имейлы.
    </p>

    <form action="{{ route('checkCsvHandle') }}" method="POST" enctype='multipart/form-data'>
        @csrf
        <div class="mb-3">
            <label for="csvFiles" class="form-label">Прикрепите файлы</label>
            <input class="form-control" type="file" id="csvFiles" multiple name="csvFiles[]">
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

@extends('layouts.app')

@section('content')

    <h1>Отчет по звонкам от Comagic</h1>

    <form action="{{ route('comagicCallsReportHandler') }}" method="POST" class="w-50">
        @csrf
        <h1>Введите даты:</h1>

        <div class="text-light bg-secondary p-3 rounded">
            <h3>Документация</h3>
            <p>Отчет в выбраном промежутке дат. Выводит таблицу из 2 колонок (visitor_id и контактный номер телефона)</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col">
                <label for="dateStart" class="form-label">Начальная дата</label>
                <input class="form-control" type="date" id="dateStart" multiple name="dateStart">
            </div>
            <div class="col">
                <label for="dateEnd" class="form-label">Конечная дата</label>
                <input class="form-control" type="date" id="dateEnd" multiple name="dateEnd">
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

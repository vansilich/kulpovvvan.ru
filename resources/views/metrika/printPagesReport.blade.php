@extends('layouts.app')

@section('content')

    <h4>Загружены даннные для отчета в период <b>{{ $from }} - {{ $to }}</b> (включительно)</h4>

    <form action="{{ route('metricaPrintPagesReportHandle') }}" method="POST" class="w-50">
        @csrf
        <h1>Введите даты:</h1>

        @if (isset($errors) && $errors->any())
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

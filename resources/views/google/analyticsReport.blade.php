@extends('layouts.app')

@section('content')

    <form action="{{ route('analyticReportHandle') }}" method="POST" class="w-50">
        @csrf
        <h1>Введите даты:</h1>
        @if(isset($errors))
            <div class="alert alert-danger" role="alert">
                {{ $errors }}
            </div>
        @endif

        <div class="row mb-3">
            <div class="col">
                <label for="txtFiles" class="form-label">Начальная дата</label>
                <input class="form-control" type="date" id="txtFiles" multiple name="dateStart">
            </div>
            <div class="col">
                <label for="txtFiles" class="form-label">Конечная дата</label>
                <input class="form-control" type="date" id="txtFiles" multiple name="dateEnd">
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Отправить</button>
    </form>

@endsection

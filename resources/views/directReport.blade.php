@extends('layouts.app')

@section('content')

    <form action="{{ route('directReportHandle') }}" method="POST" class="w-50">
        @csrf
        <h1>Введите даты:</h1>

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

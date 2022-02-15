@extends('layouts.app')

@section('content')

    <h1>Статистика менеджеров по email</h1>

    @foreach($data as $date => $row)
        <div class="m-5">
            <h2>Новые подписчики за {{ $date }}</h2>

            <table class="table table-striped table-bordered">
                <tbody>
                @foreach($row as $manager => $value)
                    <tr>
                        <th scope="row">{{ $manager }}</th>
                        <th scope="row">{{ $value }}</th>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <form action="{{ route('ManagerEmailStatLogs') }}" method="POST">
                @csrf
                <input type="hidden" value="{{ $date }}" name="date">
                <button class="btn btn-primary" type="submit">Скачать лог</button>
            </form>
        </div>
    @endforeach

@endsection

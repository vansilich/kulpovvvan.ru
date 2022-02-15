@extends('layouts.app')

@section('content')

    <h1>Отчет по звонкам от Comagic</h1>

    <div class="m-5">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th scope="col">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($row as $value)
                        <th scope="row">{{ $value }}</th>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

@endsection

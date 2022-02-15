<html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Иван - @yield('title')</title>

        <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    </head>
    <body>

        <main class="container-fluid">
            <div class="row flex-nowrap">
                <div class="col-auto col-md-5 col-xl-3 px-0">
                    @include('components.sidebar')
                </div>
                <div class="col py-3" style="overflow-y: scroll; max-height: 100vh;">
                    @yield('content')
                </div>
            </div>
        </main>

        <script src="{{ asset('assets/js/bootstrap.bundle.js') }}"></script>
    </body>

</html>

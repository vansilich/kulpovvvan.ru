<div class="d-flex flex-column align-items-center px-3 pt-2 text-white min-vh-100 bg-dark">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none text-white">
        <span class="fs-4">Меню</span>
    </a>

    <ul class="nav nav-pills flex-column mb-auto w-100">
        <li class="nav-item">
            <a href="/" class="link-dark text-decoration-none text-white">
                <span class="fs-4">Валидация отчетов</span>
            </a>
            <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                <li class="nav-item">
                    <a href="{{ route('checkTxtForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'checkTxtForm'  ? 'active' : '' !!}" aria-current="page">
                        Проверка txt
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('checkCsvForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'checkCsvForm'  ? 'active' : '' !!}" aria-current="page">
                        Проверка csv
                    </a>
                </li>
            </ul>
        </li>
        <li class="nav-item">
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a class="link-dark text-decoration-none text-white" aria-current="page">
                        <span class="fs-4">Mailganer</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                        <li class="nav-item">
                            <a href="{{ route('ManagerEmailStatForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'ManagerEmailStatForm'  ? 'active' : '' !!}" aria-current="page">
                                Статистика менеджеров по email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('mailganerUnsubForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'mailganerUnsubForm'  ? 'active' : '' !!}" aria-current="page">
                                Отписка имейлов
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="link-dark text-decoration-none text-white" aria-current="page">
                        <span class="fs-4">Comagic</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                        <li class="nav-item">
                            <a href="{{ route('comagicCallsReport') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'comagicCallsReport'  ? 'active' : '' !!}" aria-current="page">
                                Отчет по звонкам
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="link-dark text-decoration-none text-white" aria-current="page">
                        <span class="fs-4">Яндекс.Директ</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                        <li class="nav-item">
                            <a href="{{ route('directReportForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'directReportForm'  ? 'active' : '' !!}" aria-current="page">
                                Отчет по целям "Emailtracking ROIstat" и "Звонок"
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="link-dark text-decoration-none text-white" aria-current="page">
                        <span class="fs-4">Google</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                        <li class="nav-item">
                            <a class="link-dark text-decoration-none text-white" aria-current="page">
                                <span class="fs-4">BigQuery</span>
                            </a>
                            <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                                <li class="nav-item">
                                    <a href="{{ route('bigqueryFindYM_UIDForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'bigqueryFindYM_UIDForm'  ? 'active' : '' !!}" aria-current="page">
                                        Поиск YM_UID по ROISTAT_ID
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="link-dark text-decoration-none text-white" aria-current="page">
                                <span class="fs-4">Analytics</span>
                            </a>
                            <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                                <li class="nav-item">
                                    <a href="{{ route('analyticReportForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'analyticReportForm'  ? 'active' : '' !!}" aria-current="page">
                                        Отчет по целям "CoMagic Звонки" и "Emailtracking"
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="link-dark text-decoration-none text-white" aria-current="page">
                                <span class="fs-4">Gmail</span>
                            </a>
                            <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                                <li class="nav-item">
                                    <a href="{{ route('emailsEntriesForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'emailsEntriesForm'  ? 'active' : '' !!}" aria-current="page">
                                        Поиск вхождения email-адресов и телефонов в имейлы менеджеров
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('triggersEntriesForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'triggersEntriesForm'  ? 'active' : '' !!}" aria-current="page">
                                        Поиск имейлов и телефонов по тригерам в имейлах менеджера
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="link-dark text-decoration-none text-white" aria-current="page">
                        <span class="fs-4">Яндекс.Метрика</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
{{--                        <li class="nav-item">--}}
{{--                            <a href="{{ route('metrikaPagesReportForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'metrikaPagesReportForm'  ? 'active' : '' !!}" aria-current="page">--}}
{{--                                Данные посещаемости страниц--}}
{{--                            </a>--}}
{{--                        </li>--}}
                        <li class="nav-item">
                            <a href="{{ route('metrikaPrintPagesReportForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'metrikaPrintPagesReportForm'  ? 'active' : '' !!}" aria-current="page">
                                Распечатать данные посещаемости страниц
                            </a>
                        </li>
                    </ul>

                </li>

                <li class="nav-item">
                    <a class="link-dark text-decoration-none text-white" aria-current="page">
                        <span class="fs-4">Dashboard</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto" style="margin-left: 20px">
                        <li class="nav-item">
                            <a href="{{ route('monthlyReportForm') }}" class="nav-link text-ali text-white {!! Route::current()->getName() == 'monthlyReportForm'  ? 'active' : '' !!}" aria-current="page">
                                Ежемесячный SEO  отчет
                            </a>
                        </li>
                    </ul>

                </li>
            </ul>
        </li>
    </ul>
</div>

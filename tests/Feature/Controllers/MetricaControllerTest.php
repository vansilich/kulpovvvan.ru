<?php

namespace Tests\Feature\Controllers;

use App\Models\UrlViewsReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MetricaControllerTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_success_pagesReportForm()
    {
        $response = $this->get( \route('metrikaPagesReportForm') );

        $response->assertViewIs('metrika.pagesReport');
        $response->assertStatus(200);
    }

//    public function test_success_printPagesReportForm()
//    {
//        $response = $this->get( \route('metrikaPrintPagesReportForm'));
//
//        $response->assertViewIs('metrika.printPagesReport');
//        $response->assertStatus(200);
//
//        $to = UrlViewsReport::select('day')->distinct()->orderBy('day', 'asc')->get()->last()->day;
//        $from = Cache::get('FNUV_lastFetchedDate');
//
//        $response->assertSeeText([$from, $to], true);
//    }
//
//    public function test_success_printPagesReportHandle()
//    {
//        $response = $this->post( \route('metricaPrintPagesReportHandle'), [
//            'dateStart' => '2022-01-19',
//            'dateEnd' => '2022-01-22'
//        ]);
//
//        $response->assertDownload();
//        $response->assertStatus(200);
//    }

}

<?php

namespace App\Http\Controllers;

use App\Handlers\FilesEmailsDuplicates;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\CsvHandler;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CheckCsvController extends Controller
{

    public int $timestamp;
    private array $source_files_names = [];

    public function index(): View
    {
        new CsvHandler( storage_path('app/public') . '/testfile.csv');
        return view('checkCsv');
    }

    public function handle( Request $request ): View|BinaryFileResponse
    {

        if ( !$request->hasFile('csvFiles') || empty($request->hasFile('csvFiles')) ) {
            return view('checkCsv')->with('error', 'Файлы не загружены');
        }

        $this->timestamp = Carbon::now()->timestamp;

        foreach ( $request->file('csvFiles') as $file) {

            $manager = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // without .csv
            $source_file_name = '_'.$this->timestamp.'_'.$file->getClientOriginalName();
            $this->source_files_names[$manager] = $source_file_name;

            $file->move( storage_path('app/public'), $source_file_name );
        }

        $path = $this->startHandle();

        return response()->download($path);
    }

    private function startHandle(): string
    {
        $tmp_arr = [];

        foreach ($this->source_files_names as $manager => $file_name) {

            $source_file_path = storage_path('app/public').'/'.$file_name;

            $stream = fopen( $source_file_path, 'r' );
            $tmp_arr[$manager] = CsvHandler::arrFromMailsCsv( $stream );
        }

        $managers = array_keys($tmp_arr);
        $tmp_arr = FilesEmailsDuplicates::removeDuplicates( $tmp_arr, $managers );

        $filePath = FilesEmailsDuplicates::saveToFile($managers, $tmp_arr);

        foreach ($this->source_files_names as $file_name) {
            $source_file_path = 'public/'.$file_name;
            Storage::disk()->delete( $source_file_path );
        }

        return $filePath;
    }

}

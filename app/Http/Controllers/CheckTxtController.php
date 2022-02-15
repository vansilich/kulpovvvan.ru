<?php

namespace App\Http\Controllers;

use App\Handlers\FilesEmailsDuplicates;
use App\Helpers\TxtHandler;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CheckTxtController extends Controller
{
    public int $timestamp;

    public function index()
    {
        return view('checkTxt');
    }

    public function handle( Request $request ): View|BinaryFileResponse
    {

        if ( !$request->hasFile('txtFiles') || empty($request->hasFile('txtFiles')) ) {
            return view('txtFiles')->with('error', 'Файлы не загружены');
        }

        $tmp_arr = [];
        foreach ( $request->file('txtFiles') as $file) {
            $manager = str_replace('fluid-line.ru.txt', '', $file->getClientOriginalName());

            $tmp_arr[$manager] = TxtHandler::arrMailsFromTxt( $file );
        }

        $managers = array_keys($tmp_arr);

        $tmp_arr = FilesEmailsDuplicates::removeDuplicates( $tmp_arr, $managers );
        $filePath = FilesEmailsDuplicates::saveToFile($managers, $tmp_arr);

        return response()->download( $filePath );
    }

}

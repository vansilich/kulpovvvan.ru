<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GmailOauthCallback extends Controller
{

    public function printCode( Request $request )
    {
        echo "Ваш код: \n" . $request->get('code');
    }
}

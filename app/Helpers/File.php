<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class File
{

    public string $fileName;
    public $stream;

    /**
     * @param string $filePath - Абсолютный путь файла
     */
    public function __construct(
        public string $filePath
    )
    {
        $this->fileName = substr($filePath, strripos($filePath, '/') + 1);
    }

    /**
     * Delete file after sending response to client.
     */
    public function deleteAfterResp(): void
    {
        App::terminating( function () {
            unlink( $this->filePath );
        });
    }

    public function openStream(): void
    {
        $this->stream = fopen($this->filePath, 'w+');
    }

    public function closeStream(): void
    {
        fclose($this->stream);
    }

}

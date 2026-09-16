<?php

namespace App\Service;

use Smalot\PdfParser\Parser;

class CvParser
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extractText(string $filePath): string
    {
        $pdf = $this->parser->parseFile($filePath);

        return $pdf->getText();
    }
}
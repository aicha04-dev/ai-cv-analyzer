<?php

namespace App\Service;

use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                'PDF file not found: ' . $filePath
            );
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException(
                'PDF file is not readable: ' . $filePath
            );
        }

        try {
            $pdf = $this->parser->parseFile($filePath);

            $text = $pdf->getText();

            $text = trim($text);

            if ($text === '') {
                throw new \RuntimeException(
                    'The PDF contains no extractable text. It may be scanned or image-based.'
                );
            }

            return $text;

        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'PDF text extraction failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
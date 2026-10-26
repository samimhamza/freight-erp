<?php

namespace App\Support\Pdf;

use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class DocumentPdf
{
    public static function make(string $view, array $data, string $filename): PdfBuilder
    {
        $company = config('company');
        $logoPath = public_path('images/logo.png');
        if (empty($company['logo_base64']) && file_exists($logoPath)) {
            $company['logo_base64'] = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
        }

        return Pdf::view($view, array_merge($data, [
            'company' => $company,
        ]))
            ->driver('dompdf')
            ->format('a4')
            ->margins(10, 10, 10, 10, 'mm')
            ->name($filename);
    }
}

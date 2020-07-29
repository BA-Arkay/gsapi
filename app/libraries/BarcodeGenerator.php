<?php

namespace App\Libraries;

class BarcodeGenerator
{
    public function generate_barcode($code_text)
    {
        $barcode = new BarcodeHelper();
        $path = app()->basePath('public/barcodes/');
        // Generate Barcode data
        $barcode->barcode();
        $barcode->setType('C128');
        $barcode->setCode($code_text);
        $barcode->setSize(100, 220);
        $filename = $code_text . '.png';
        // Generate filename
        $path = $path . $filename;
        // Generates image file on server
        $barcode->writeBarcodeFile($path);
        return $filename;
    }

}
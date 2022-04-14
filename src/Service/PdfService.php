<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    public const PATH = 'http://127.0.0.1/uploads/files/';

    private $domPdf;
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->domPdf = new DomPdf();
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Garamond');
        $this->domPdf->setOptions($pdfOptions);
        $this->projectDir = $projectDir;
    }

    public function showPdfFile($html)
    {
        $this->domPdf->loadHtml($html->getContent());
        $this->domPdf->setPaper('A4', 'landscape');
        $this->domPdf->render();
        $this->domPdf->stream("details.pdf", [
            'Attachement' => true
        ]);
    }

    /**
     * @param $html
     * @return string|null
     */
    public function generateBinaryPDF($html): string
    {
        $this->domPdf->loadHtml($html->getContent());
        $this->domPdf->setPaper('A4', 'landscape');
        $this->domPdf->render();
        $output = $this->domPdf->output();

        $fileName = '/files/invoice' . date('YmdHis') . '.pdf';
        $filePath = $this->projectDir . '/public' . $fileName;

        file_put_contents($filePath, $output);

        return $fileName;
    }
}

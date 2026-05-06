<?php

declare(strict_types=1);

namespace App\Services\Document;

use TCPDF;

final class BrandedTcpdf extends TCPDF
{
    private string $headerLogoLeft = '';
    private string $headerLogoRight = '';
    private string $documentName = '';
    private string $printedBy = '';
    private string $printedAt = '';
    private string $footerText = '';

    public function setHeaderFooterData(
        string $documentName,
        string $printedBy,
        string $printedAt,
        string $logoLeftPath,
        string $logoRightPath,
        string $footerText
    ): void {
        $this->documentName = $documentName;
        $this->printedBy = $printedBy;
        $this->printedAt = $printedAt;
        $this->headerLogoLeft = $logoLeftPath;
        $this->headerLogoRight = $logoRightPath;
        $this->footerText = $footerText;
    }

    public function Header()
    {
        $margins = $this->getMargins();
        $pageWidth = $this->getPageWidth();
        $usableWidth = $pageWidth - $margins['left'] - $margins['right'];

        $startY = 8.0;
        $logoW = 18.0;
        $logoH = 0.0;

        if ($this->headerLogoLeft !== '' && is_file($this->headerLogoLeft)) {
            $this->Image($this->headerLogoLeft, $margins['left'], $startY, $logoW, $logoH, '');
        }
        if ($this->headerLogoRight !== '' && is_file($this->headerLogoRight)) {
            $rightX = $pageWidth - $margins['right'] - $logoW;
            $this->Image($this->headerLogoRight, $rightX, $startY, $logoW, $logoH, '');
        }

        $this->SetY($startY);
        $this->SetFont('dejavusans', 'B', 13);
        $this->Cell(0, 6, $this->documentName, 0, 1, 'C');

        $this->SetFont('dejavusans', '', 8);
        $info = trim('Imprimé par: ' . $this->printedBy . '   |   Imprimé le: ' . $this->printedAt);
        $this->SetX($margins['left']);
        $this->MultiCell($usableWidth, 4, $info, 0, 'C', false, 1, $margins['left'], $this->GetY(), true);

        $y = $this->GetY() + 1;
        $this->Line($margins['left'], $y, $pageWidth - $margins['right'], $y);
    }

    public function Footer()
    {
        $margins = $this->getMargins();
        $pageWidth = $this->getPageWidth();
        $usableWidth = $pageWidth - $margins['left'] - $margins['right'];

        $this->SetY(-18);
        $this->SetFont('dejavusans', '', 8);

        if (trim($this->footerText) !== '') {
            $this->SetX($margins['left']);
            $this->MultiCell($usableWidth, 4, $this->footerText, 0, 'C', false, 1, $margins['left'], $this->GetY(), true);
        }

        $this->SetFont('dejavusans', 'I', 8);
        $pageLabel = 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
        $this->Cell(0, 4, $pageLabel, 0, 0, 'R');
    }
}


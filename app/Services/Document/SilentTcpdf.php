<?php

declare(strict_types=1);

namespace App\Services\Document;

use TCPDF;

final class SilentTcpdf extends TCPDF
{
    public function __construct(
        $orientation = 'P',
        $unit = 'mm',
        $format = 'A4',
        $unicode = true,
        $encoding = 'UTF-8',
        $diskcache = false,
        $pdfa = false
    ) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->tcpdflink = false;
    }

    public function Header()
    {
    }

    public function Footer()
    {
    }
}


<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfBillExtractor
{
    public function extract(string $absolutePath): array
    {
        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($absolutePath);
            $text   = $pdf->getText();
        } catch (\Throwable) {
            return [];
        }

        return [
            'numero_factura_proveedor' => $this->extractNumeroFactura($text),
            'fecha_factura'            => $this->extractFecha($text),
            'subtotal'                 => $this->extractSubtotal($text),
            'iva_pct'                  => $this->extractIvaPct($text),
            'iva_monto'                => $this->extractIvaMonto($text),
            'total'                    => $this->extractTotal($text),
        ];
    }

    // 001-001-000000001
    private function extractNumeroFactura(string $text): ?string
    {
        if (preg_match('/(\d{3}-\d{3}-\d{9})/', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // dd/mm/yyyy
    private function extractFecha(string $text): ?string
    {
        if (preg_match('/\b(\d{2})\/(\d{2})\/(\d{4})\b/', $text, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return null;
    }

    // En SRI el valor va ANTES de la etiqueta: "204.35SUBTOTAL SIN IMPUESTOS"
    private function extractSubtotal(string $text): ?float
    {
        if (preg_match('/([\d.,]+)\s*SUBTOTAL SIN IMPUESTOS/i', $text, $m)) {
            return $this->parseAmount($m[1]);
        }
        if (preg_match('/([\d.,]+)\s*SUBTOTAL 15%/i', $text, $m)) {
            return $this->parseAmount($m[1]);
        }
        if (preg_match('/([\d.,]+)\s*SUBTOTAL 0%/i', $text, $m)) {
            return $this->parseAmount($m[1]);
        }
        return null;
    }

    // "IVA 15%" — extrae el porcentaje (etiqueta primero aquí)
    private function extractIvaPct(string $text): ?int
    {
        if (preg_match('/IVA\s+(\d+)%/i', $text, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    // "30.65IVA 15%"
    private function extractIvaMonto(string $text): ?float
    {
        if (preg_match('/([\d.,]+)\s*IVA\s+\d+%/i', $text, $m)) {
            return $this->parseAmount($m[1]);
        }
        return null;
    }

    // "235.00VALOR TOTAL" — evitar "VALOR TOTAL SIN SUBSIDIO"
    private function extractTotal(string $text): ?float
    {
        if (preg_match('/([\d.,]+)\s*VALOR TOTAL(?!\s+SIN)/i', $text, $m)) {
            return $this->parseAmount($m[1]);
        }
        return null;
    }

    private function parseAmount(string $raw): ?float
    {
        $clean = preg_replace('/[^\d.,]/', '', $raw);

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $lastComma = strrpos($clean, ',');
            $lastDot   = strrpos($clean, '.');
            $clean = $lastComma > $lastDot
                ? str_replace('.', '', str_replace(',', '.', $clean))
                : str_replace(',', '', $clean);
        } elseif (str_contains($clean, ',')) {
            $parts = explode(',', $clean);
            $clean = (count($parts) === 2 && strlen(end($parts)) <= 2)
                ? str_replace(',', '.', $clean)
                : str_replace(',', '', $clean);
        }

        $value = (float) $clean;
        return $value > 0 ? $value : null;
    }
}

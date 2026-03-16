<?php

namespace App\Exports;

use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceGeneralExport
{
    protected int    $empresaId;
    protected string $fechaCorte;
    protected string $nombreEmpresa;

    public function __construct(int $empresaId, string $fechaCorte, string $nombreEmpresa)
    {
        $this->empresaId     = $empresaId;
        $this->fechaCorte    = $fechaCorte;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    // -------------------------------------------------------------------------
    // Carga de saldos (mismo criterio que la página)
    // -------------------------------------------------------------------------

    protected function getSaldos(): Collection
    {
        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $this->empresaId)
            ->whereIn('type', ['activo', 'pasivo', 'patrimonio'])
            ->where('accepts_movements', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($cuenta) {
                $baseQ = fn() => JournalEntryLine::where('account_plan_id', $cuenta->id)
                    ->whereHas('journalEntry', fn($q) => $q
                        ->withoutGlobalScopes()
                        ->where('empresa_id', $this->empresaId)
                        ->where('status', 'confirmado')
                        ->where('esta_cuadrado', true)
                        ->whereDate('fecha', '<=', $this->fechaCorte)
                    );

                $debe  = (float) $baseQ()->sum('debe');
                $haber = (float) $baseQ()->sum('haber');

                $cuenta->saldo = $cuenta->nature === 'deudora'
                    ? $debe - $haber
                    : $haber - $debe;

                return $cuenta;
            })
            ->filter(fn($c) => round($c->saldo, 2) != 0);
    }

    // -------------------------------------------------------------------------
    // Descarga
    // -------------------------------------------------------------------------

    public function download(string $filename): StreamedResponse
    {
        $cuentas = $this->getSaldos();

        $activosCte    = $cuentas->filter(fn($c) => str_starts_with($c->code, '1.1'));
        $activosNoCte  = $cuentas->filter(fn($c) => str_starts_with($c->code, '1.2'));
        $pasivosCte    = $cuentas->filter(fn($c) => str_starts_with($c->code, '2.1'));
        $pasivosNoCte  = $cuentas->filter(fn($c) => str_starts_with($c->code, '2.2'));
        $patrimonioAll = $cuentas->filter(fn($c) => $c->type === 'patrimonio');

        $tActCte   = $activosCte->sum('saldo');
        $tActNoCte = $activosNoCte->sum('saldo');
        $tActivos  = $tActCte + $tActNoCte;

        $tPasCte   = $pasivosCte->sum('saldo');
        $tPasNoCte = $pasivosNoCte->sum('saldo');
        $tPasivos  = $tPasCte + $tPasNoCte;

        $tPat    = $patrimonioAll->sum('saldo');
        $tPasPat = $tPasivos + $tPat;

        // ----- Estilos -----
        $stTitle = [
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $stSubTitle = [
            'font'      => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $stSectionHeader = fn(string $rgb) => [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
        ];
        $stSubSection = [
            'font'      => ['bold' => true, 'italic' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
        ];
        $stSubtotal = [
            'font'    => ['bold' => true],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $stTotal = [
            'font'    => ['bold' => true],
            'borders' => [
                'top'    => ['borderStyle' => Border::BORDER_MEDIUM],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ];
        $stGrandTotal = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
        ];

        // ----- Spreadsheet -----
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Balance General');

        // Encabezado
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', strtoupper($this->nombreEmpresa));
        $sheet->getStyle('A2')->applyFromArray($stTitle);

        $sheet->mergeCells('A3:D3');
        $sheet->setCellValue('A3', 'ESTADO DE SITUACIÓN FINANCIERA');
        $sheet->getStyle('A3')->applyFromArray($stSubTitle);

        $sheet->mergeCells('A4:D4');
        $sheet->setCellValue('A4', 'Al ' . $this->fechaCorte);
        $sheet->getStyle('A4')->applyFromArray($stSubTitle);

        $sheet->mergeCells('A5:D5');
        $sheet->setCellValue('A5', 'Expresado en dólares de los Estados Unidos de América');
        $sheet->getStyle('A5')->applyFromArray(['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Encabezados de tabla
        $row = 7;
        foreach (['A' => 'Código', 'B' => 'Descripción', 'C' => 'Valor', 'D' => ''] as $col => $val) {
            $sheet->setCellValue($col . $row, $val);
        }
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $row++;

        // ===================== ACTIVOS =====================
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'ACTIVOS');
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stSectionHeader('1E3A5F'));
        $row++;

        $this->writeSubSection($sheet, $row, '1. ACTIVOS CORRIENTES', $activosCte,
            'Total Activos Corrientes', $tActCte, $stSubSection, $stSubtotal);

        $this->writeSubSection($sheet, $row, '2. ACTIVOS NO CORRIENTES', $activosNoCte,
            'Total Activos No Corrientes', $tActNoCte, $stSubSection, $stSubtotal);

        $sheet->setCellValue("B{$row}", 'TOTAL ACTIVOS');
        $sheet->setCellValue("C{$row}", $tActivos);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stTotal);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
        $row += 2;

        // ===================== PASIVOS =====================
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'PASIVOS');
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stSectionHeader('7F1D1D'));
        $row++;

        $this->writeSubSection($sheet, $row, '1. PASIVOS CORRIENTES', $pasivosCte,
            'Total Pasivos Corrientes', $tPasCte, $stSubSection, $stSubtotal);

        $this->writeSubSection($sheet, $row, '2. PASIVOS NO CORRIENTES', $pasivosNoCte,
            'Total Pasivos No Corrientes', $tPasNoCte, $stSubSection, $stSubtotal);

        $sheet->setCellValue("B{$row}", 'TOTAL PASIVOS');
        $sheet->setCellValue("C{$row}", $tPasivos);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stTotal);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
        $row += 2;

        // ===================== PATRIMONIO =====================
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'PATRIMONIO NETO');
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stSectionHeader('14532D'));
        $row++;

        foreach ($patrimonioAll as $item) {
            $indent = str_repeat('  ', max(0, $item->level - 1));
            $sheet->setCellValue("A{$row}", $item->code);
            $sheet->setCellValue("B{$row}", $indent . $item->name);
            $sheet->setCellValue("C{$row}", $item->saldo);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'TOTAL PATRIMONIO');
        $sheet->setCellValue("C{$row}", $tPat);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stTotal);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
        $row += 2;

        // ===================== GRAN TOTAL =====================
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL PASIVOS + PATRIMONIO NETO');
        $sheet->setCellValue("C{$row}", $tPasPat);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($stGrandTotal);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
        $row += 3;

        // Firmas
        $sheet->setCellValue("A{$row}", '____________________________');
        $sheet->setCellValue("C{$row}", '____________________________');
        $row++;
        $sheet->setCellValue("A{$row}", 'Representante Legal');
        $sheet->setCellValue("C{$row}", 'Contador General');
        $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(5);

        // Respuesta HTTP
        $writer   = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    // -------------------------------------------------------------------------
    // Helper: escribe una sub-sección (título + filas + subtotal)
    // -------------------------------------------------------------------------

    protected function writeSubSection(
        $sheet,
        int &$row,
        string $title,
        Collection $data,
        string $subtotalLabel,
        float $subtotalValue,
        array $titleStyle,
        array $totalStyle
    ): void {
        $sheet->setCellValue("B{$row}", $title);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($titleStyle);
        $row++;

        foreach ($data as $item) {
            $indent = str_repeat('  ', max(0, $item->level - 1));
            $sheet->setCellValue("A{$row}", $item->code);
            $sheet->setCellValue("B{$row}", $indent . $item->name);
            $sheet->setCellValue("C{$row}", $item->saldo);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
            $row++;
        }

        $sheet->setCellValue("B{$row}", $subtotalLabel);
        $sheet->setCellValue("C{$row}", $subtotalValue);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($totalStyle);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
        $row += 2;
    }
}

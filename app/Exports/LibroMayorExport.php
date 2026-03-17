<?php

namespace App\Exports;

use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibroMayorExport
{
    protected int     $empresaId;
    protected int     $accountPlanId;
    protected ?string $fechaDesde;
    protected ?string $fechaHasta;
    protected string  $nombreEmpresa;

    public function __construct(
        int     $empresaId,
        int     $accountPlanId,
        ?string $fechaDesde,
        ?string $fechaHasta,
        string  $nombreEmpresa
    ) {
        $this->empresaId     = $empresaId;
        $this->accountPlanId = $accountPlanId;
        $this->fechaDesde    = $fechaDesde;
        $this->fechaHasta    = $fechaHasta;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    public function download(string $filename): StreamedResponse
    {
        $cuenta = AccountPlan::withoutGlobalScopes()->find($this->accountPlanId);

        // Saldo inicial acumulado antes del período
        $saldoInicial = (float) JournalEntryLine::where('account_plan_id', $this->accountPlanId)
            ->whereHas('journalEntry', fn($q) => $q
                ->withoutGlobalScopes()
                ->where('empresa_id', $this->empresaId)
                ->where('status', 'confirmado')
                ->where('esta_cuadrado', true)
                ->when($this->fechaDesde, fn($q) => $q->whereDate('fecha', '<', $this->fechaDesde))
            )->selectRaw('SUM(debe) - SUM(haber) as saldo')
            ->value('saldo') ?? 0;

        // Ajuste para cuentas acreedoras (pasivo, patrimonio, ingreso)
        if ($cuenta && $cuenta->nature === 'acreedora') {
            $saldoInicial = -$saldoInicial;
        }

        // Movimientos del período ordenados por fecha
        $lines = JournalEntryLine::where('account_plan_id', $this->accountPlanId)
            ->whereHas('journalEntry', fn($q) => $q
                ->withoutGlobalScopes()
                ->where('empresa_id', $this->empresaId)
                ->where('status', 'confirmado')
                ->where('esta_cuadrado', true)
                ->when($this->fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
                ->when($this->fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $this->fechaHasta))
            )
            ->with('journalEntry')
            ->orderBy(
                \App\Models\JournalEntry::select('fecha')
                    ->whereColumn('id', 'journal_entry_lines.journal_entry_id'),
                'asc'
            )
            ->orderBy('journal_entry_id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Libro Mayor');

        $periodoLabel = trim(($this->fechaDesde ?? '') . ' al ' . ($this->fechaHasta ?? ''), ' al ');
        $cuentaLabel  = $cuenta ? "[{$cuenta->code}] {$cuenta->name}" : "Cuenta #{$this->accountPlanId}";

        // Encabezado
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', strtoupper($this->nombreEmpresa));
        $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 13], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'LIBRO MAYOR');
        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A4:G4');
        $sheet->setCellValue('A4', 'Período: ' . $periodoLabel);
        $sheet->getStyle('A4')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A5:G5');
        $sheet->setCellValue('A5', 'Cuenta: ' . $cuentaLabel);
        $sheet->getStyle('A5')->applyFromArray(['font' => ['bold' => true, 'italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A6:G6');
        $sheet->setCellValue('A6', 'Expresado en dólares de los Estados Unidos de América');
        $sheet->getStyle('A6')->applyFromArray(['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Encabezados tabla
        $row = 8;
        $headers = ['Fecha', 'N° Asiento', 'Tipo', 'Descripción', 'Debe', 'Haber', 'Saldo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . $row, $h);
        }
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $numFmt = '#,##0.00;[Red](#,##0.00)';

        // Fila saldo inicial
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'SALDO INICIAL AL ' . ($this->fechaDesde ?? ''));
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue("G{$row}", $saldoInicial);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true, 'italic' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
        ]);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($numFmt);
        $row++;

        $saldoAcum   = $saldoInicial;
        $totalDebe   = 0.0;
        $totalHaber  = 0.0;
        $esDeudora   = ($cuenta && $cuenta->nature === 'deudora');

        foreach ($lines as $line) {
            $entry  = $line->journalEntry;
            $debe   = (float) $line->debe;
            $haber  = (float) $line->haber;

            // Saldo acumulado: deudora = +debe -haber; acreedora = +haber -debe
            $saldoAcum += $esDeudora ? ($debe - $haber) : ($haber - $debe);

            $sheet->setCellValue("A{$row}", $entry ? \Carbon\Carbon::parse($entry->fecha)->format('d/m/Y') : '');
            $sheet->setCellValue("B{$row}", $entry?->numero ?? '');
            $sheet->setCellValue("C{$row}", $entry ? strtoupper($entry->tipo) : '');
            $sheet->setCellValue("D{$row}", $line->descripcion);
            $sheet->setCellValue("E{$row}", $debe);
            $sheet->setCellValue("F{$row}", $haber);
            $sheet->setCellValue("G{$row}", $saldoAcum);

            foreach (['E', 'F', 'G'] as $col) {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($numFmt);
            }

            $totalDebe  += $debe;
            $totalHaber += $haber;
            $row++;
        }

        // Totales
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'TOTALES DEL PERÍODO');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue("E{$row}", $totalDebe);
        $sheet->setCellValue("F{$row}", $totalHaber);
        $sheet->setCellValue("G{$row}", $saldoAcum);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        foreach (['E', 'F', 'G'] as $col) {
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($numFmt);
        }

        // Anchos
        $sheet->getColumnDimension('A')->setWidth(13);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);

        $writer   = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}

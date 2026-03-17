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

class FlujoCajaExport
{
    protected int    $empresaId;
    protected string $fechaDesde;
    protected string $fechaHasta;
    protected string $nombreEmpresa;

    public function __construct(int $empresaId, string $fechaDesde, string $fechaHasta, string $nombreEmpresa)
    {
        $this->empresaId     = $empresaId;
        $this->fechaDesde    = $fechaDesde;
        $this->fechaHasta    = $fechaHasta;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    protected function getData(): Collection
    {
        $cuentasEfectivo = AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $this->empresaId)
            ->where('code', 'like', '1.1.01.%')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $cuentasEfectivo->map(function ($cuenta) {
            $entradas = (float) JournalEntryLine::where('account_plan_id', $cuenta->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->withoutGlobalScopes()
                    ->where('empresa_id', $this->empresaId)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
                )->sum('debe');

            $salidas = (float) JournalEntryLine::where('account_plan_id', $cuenta->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->withoutGlobalScopes()
                    ->where('empresa_id', $this->empresaId)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
                )->sum('haber');

            $saldoInicial = (float) JournalEntryLine::where('account_plan_id', $cuenta->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->withoutGlobalScopes()
                    ->where('empresa_id', $this->empresaId)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->whereDate('fecha', '<', $this->fechaDesde)
                )->selectRaw('SUM(debe) - SUM(haber) as saldo')
                ->value('saldo') ?? 0;

            return (object) [
                'code'          => $cuenta->code,
                'name'          => $cuenta->name,
                'saldo_inicial' => $saldoInicial,
                'entradas'      => $entradas,
                'salidas'       => $salidas,
                'saldo_final'   => $saldoInicial + $entradas - $salidas,
            ];
        });
    }

    public function download(string $filename): StreamedResponse
    {
        $data = $this->getData();

        $tSaldoInicial = $data->sum('saldo_inicial');
        $tEntradas     = $data->sum('entradas');
        $tSalidas      = $data->sum('salidas');
        $tSaldoFinal   = $data->sum('saldo_final');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Flujo de Caja');

        // Encabezado
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', strtoupper($this->nombreEmpresa));
        $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 13], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'ESTADO DE FLUJO DE EFECTIVO');
        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A4:F4');
        $sheet->setCellValue('A4', 'Período: ' . $this->fechaDesde . ' al ' . $this->fechaHasta);
        $sheet->getStyle('A4')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A5:F5');
        $sheet->setCellValue('A5', 'Expresado en dólares de los Estados Unidos de América');
        $sheet->getStyle('A5')->applyFromArray(['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Encabezados tabla
        $row = 7;
        $headers = ['Código', 'Cuenta', 'Saldo Inicial', 'Entradas (+)', 'Salidas (-)', 'Saldo Final'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . $row, $h);
        }
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $numFmt = '#,##0.00;[Red](#,##0.00)';

        foreach ($data as $c) {
            $sheet->setCellValue("A{$row}", $c->code);
            $sheet->setCellValue("B{$row}", $c->name);
            $sheet->setCellValue("C{$row}", $c->saldo_inicial);
            $sheet->setCellValue("D{$row}", $c->entradas);
            $sheet->setCellValue("E{$row}", $c->salidas);
            $sheet->setCellValue("F{$row}", $c->saldo_final);
            foreach (['C', 'D', 'E', 'F'] as $col) {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($numFmt);
            }
            $row++;
        }

        // Total
        $sheet->setCellValue("A{$row}", '');
        $sheet->setCellValue("B{$row}", 'TOTAL CONSOLIDADO');
        $sheet->setCellValue("C{$row}", $tSaldoInicial);
        $sheet->setCellValue("D{$row}", $tEntradas);
        $sheet->setCellValue("E{$row}", $tSalidas);
        $sheet->setCellValue("F{$row}", $tSaldoFinal);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        foreach (['C', 'D', 'E', 'F'] as $col) {
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($numFmt);
        }

        // Anchos
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(35);
        foreach (['C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

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

<?php

namespace App\Exports;

use App\Models\JournalEntry;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibroDiarioExport
{
    protected int     $empresaId;
    protected ?string $fechaDesde;
    protected ?string $fechaHasta;
    protected ?string $tipo;
    protected string  $nombreEmpresa;

    public function __construct(
        int     $empresaId,
        ?string $fechaDesde,
        ?string $fechaHasta,
        ?string $tipo,
        string  $nombreEmpresa
    ) {
        $this->empresaId     = $empresaId;
        $this->fechaDesde    = $fechaDesde;
        $this->fechaHasta    = $fechaHasta;
        $this->tipo          = $tipo;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    protected function getData(): Collection
    {
        return JournalEntry::withoutGlobalScopes()
            ->where('empresa_id', $this->empresaId)
            ->where('status', 'confirmado')
            ->where('esta_cuadrado', true)
            ->when($this->fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $this->fechaHasta))
            ->when($this->tipo, fn($q) => $q->where('tipo', $this->tipo))
            ->with(['lines.accountPlan'])
            ->orderBy('fecha')
            ->orderBy('numero')
            ->get();
    }

    public function download(string $filename): StreamedResponse
    {
        $entries = $this->getData();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Libro Diario');

        $periodoLabel = trim(($this->fechaDesde ?? '') . ' al ' . ($this->fechaHasta ?? ''), ' al ');

        // Encabezado
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['size' => 9, 'italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', strtoupper($this->nombreEmpresa));
        $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 13], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'LIBRO DIARIO');
        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A4:G4');
        $sheet->setCellValue('A4', 'Período: ' . $periodoLabel);
        $sheet->getStyle('A4')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A5:G5');
        $sheet->setCellValue('A5', 'Expresado en dólares de los Estados Unidos de América');
        $sheet->getStyle('A5')->applyFromArray(['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Encabezados tabla
        $row = 7;
        $headers = ['N° Asiento', 'Fecha', 'Tipo', 'Cód. Cuenta', 'Nombre Cuenta', 'Debe', 'Haber'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . $row, $h);
        }
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $numFmt      = '#,##0.00;[Red](#,##0.00)';
        $grandTotDebe  = 0.0;
        $grandTotHaber = 0.0;

        $stAsientoHeader = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            'font' => ['bold' => true],
        ];
        $stAsientoTotal = [
            'font'    => ['bold' => true],
            'borders' => [
                'top'    => ['borderStyle' => Border::BORDER_THIN],
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];

        foreach ($entries as $entry) {
            // Fila encabezado del asiento
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}", ($entry->numero ?? '-') . '  |  ' . \Carbon\Carbon::parse($entry->fecha)->format('d/m/Y') . '  |  ' . strtoupper($entry->tipo ?? ''));
            $sheet->setCellValue("D{$row}", '');
            $sheet->mergeCells("E{$row}:G{$row}");
            $sheet->setCellValue("E{$row}", $entry->descripcion);
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($stAsientoHeader);
            $row++;

            $asientoDebe  = 0.0;
            $asientoHaber = 0.0;

            foreach ($entry->lines as $line) {
                $ap = $line->accountPlan;
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("B{$row}", '');
                $sheet->setCellValue("C{$row}", '');
                $sheet->setCellValue("D{$row}", $ap?->code ?? '');
                $sheet->setCellValue("E{$row}", $ap?->name ?? '');
                $sheet->setCellValue("F{$row}", (float) $line->debe);
                $sheet->setCellValue("G{$row}", (float) $line->haber);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($numFmt);
                $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($numFmt);
                $asientoDebe  += (float) $line->debe;
                $asientoHaber += (float) $line->haber;
                $row++;
            }

            // Subtotal asiento
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->setCellValue("A{$row}", 'SUBTOTAL ASIENTO');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("F{$row}", $asientoDebe);
            $sheet->setCellValue("G{$row}", $asientoHaber);
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($stAsientoTotal);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($numFmt);
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($numFmt);

            $grandTotDebe  += $asientoDebe;
            $grandTotHaber += $asientoHaber;
            $row += 2;
        }

        // Gran total
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL GENERAL');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue("F{$row}", $grandTotDebe);
        $sheet->setCellValue("G{$row}", $grandTotHaber);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($numFmt);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($numFmt);

        // Anchos
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(40);
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

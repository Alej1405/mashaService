<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class EstadoResultadosExport
{
    protected int $empresaId;
    protected string $fechaDesde;
    protected string $fechaHasta;
    protected string $nombreEmpresa;
    protected ?string $ruc;

    public function __construct(int $empresaId, string $fechaDesde, string $fechaHasta, string $nombreEmpresa, ?string $ruc = null)
    {
        $this->empresaId = $empresaId;
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->ruc = $ruc ?? '0000000000001'; // Fallback
    }

    protected function getCuentasMonto($codes, $type, $sumField = 'haber'): Collection
    {
        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $this->empresaId)
            ->where('type', $type)
            ->where(function($q) use ($codes) {
                foreach((array)$codes as $code) {
                    $q->orWhere('code', 'like', $code . '%');
                }
            })
            ->where('accepts_movements', true)
            ->get()
            ->map(function($cuenta) use ($sumField) {
                $baseQuery = fn() => JournalEntryLine::where('account_plan_id', $cuenta->id)
                    ->whereHas('journalEntry', fn($q) => $q
                        ->withoutGlobalScopes()
                        ->where('empresa_id', $this->empresaId)
                        ->where('status', 'confirmado')
                        ->where('esta_cuadrado', true)
                        ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
                    );

                $debe  = (float) $baseQuery()->sum('debe');
                $haber = (float) $baseQuery()->sum('haber');
                $monto = ($sumField === 'haber') ? ($haber - $debe) : ($debe - $haber);

                return [
                    'code'  => $cuenta->code,
                    'name'  => $cuenta->name,
                    'monto' => $monto,
                ];
            })
            ->filter(fn($row) => round($row['monto'], 2) != 0)
            ->values();
    }

    public function download($filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estado de Resultados');

        // Estilos
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $tableHeadStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sectionTitleStyle = [
            'font' => ['bold' => true, 'italic' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
        ];

        $totalLineStyle = [
            'font' => ['bold' => true],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $grandTotalStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '111827']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ];

        // Encabezado SUPERCIAS
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', strtoupper($this->nombreEmpresa));
        $sheet->getStyle('A1')->applyFromArray($headerStyle);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'RUC: ' . $this->ruc);
        $sheet->getStyle('A2')->applyFromArray($subHeaderStyle);

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'ESTADO DE RESULTADOS INTEGRALES');
        $sheet->getStyle('A3')->applyFromArray($subHeaderStyle);

        $sheet->mergeCells('A4:C4');
        $sheet->setCellValue('A4', 'Período: ' . $this->fechaDesde . ' al ' . $this->fechaHasta);
        $sheet->getStyle('A4')->applyFromArray($subHeaderStyle);

        $sheet->mergeCells('A5:C5');
        $sheet->setCellValue('A5', 'Expresado en dólares de los Estados Unidos de América');
        $sheet->getStyle('A5')->applyFromArray(['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Encabezados Tabla
        $row = 7;
        $sheet->setCellValue('A' . $row, 'Código');
        $sheet->setCellValue('B' . $row, 'Descripción');
        $sheet->setCellValue('C' . $row, 'Valor');
        $sheet->getStyle("A$row:C$row")->applyFromArray($tableHeadStyle);
        $row++;

        // Datos
        $ingresosOrd = $this->getCuentasMonto(['4.1'], 'ingreso', 'haber');
        $otrosIngresos = $this->getCuentasMonto(['4.2', '4.3'], 'ingreso', 'haber');
        $costos = $this->getCuentasMonto(['5'], 'costo', 'debe');
        $gastosOp = $this->getCuentasMonto(['6.1', '6.2'], 'gasto', 'debe');
        $gastosNoOp = $this->getCuentasMonto(['6.3', '6.4'], 'gasto', 'debe');

        // Totales
        $totIngOrd = $ingresosOrd->sum('monto');
        $totOtrosIng = $otrosIngresos->sum('monto');
        $totalIngresos = $totIngOrd + $totOtrosIng;
        $totalCostos = $costos->sum('monto');
        $utilBruta = $totalIngresos - $totalCostos;
        $totalGastosOp = $gastosOp->sum('monto');
        $utilOperacional = $utilBruta - $totalGastosOp;
        $totalGastosNoOp = $gastosNoOp->sum('monto');
        $utilAntesImp = $utilOperacional - $totalGastosNoOp;
        $partic = $utilAntesImp > 0 ? $utilAntesImp * 0.15 : 0;
        $impRenta = ($utilAntesImp - $partic) > 0 ? ($utilAntesImp - $partic) * 0.25 : 0;
        $utilNeta = $utilAntesImp - $partic - $impRenta;

        // Renderizado de Secciones
        $this->writeSection($sheet, $row, '1. INGRESOS DE ACTIVIDADES ORDINARIAS', $ingresosOrd, 'TOTAL INGRESOS ORDINARIOS', $totIngOrd, $sectionTitleStyle, $totalLineStyle);
        $this->writeSection($sheet, $row, '2. OTROS INGRESOS', $otrosIngresos, 'TOTAL OTROS INGRESOS', $totOtrosIng, $sectionTitleStyle, $totalLineStyle);
        
        $sheet->setCellValue('B' . $row, '(=) TOTAL INGRESOS');
        $sheet->setCellValue('C' . $row, $totalIngresos);
        $sheet->getStyle("B$row:C$row")->getFont()->setBold(true);
        $row += 2;

        $this->writeSection($sheet, $row, '3. COSTO DE VENTAS Y PRODUCCIÓN', $costos, '(-) TOTAL COSTO DE VENTAS', $totalCostos * -1, $sectionTitleStyle, $totalLineStyle);
        
        $sheet->setCellValue('B' . $row, '(=) UTILIDAD BRUTA');
        $sheet->setCellValue('C' . $row, $utilBruta);
        $sheet->getStyle("B$row:C$row")->applyFromArray(['font' => ['bold' => true]]);
        $row += 2;

        $this->writeSection($sheet, $row, '4. GASTOS OPERACIONALES', $gastosOp, '(-) TOTAL GASTOS OPERACIONALES', $totalGastosOp * -1, $sectionTitleStyle, $totalLineStyle);

        $sheet->setCellValue('B' . $row, '(=) UTILIDAD OPERACIONAL');
        $sheet->setCellValue('C' . $row, $utilOperacional);
        $sheet->getStyle("B$row:C$row")->getFont()->setBold(true);
        $row += 2;

        $this->writeSection($sheet, $row, '5. GASTOS NO OPERACIONALES', $gastosNoOp, '(-) TOTAL GASTOS NO OPERACIONALES', $totalGastosNoOp * -1, $sectionTitleStyle, $totalLineStyle);

        $sheet->setCellValue('B' . $row, '(=) UTILIDAD ANTES DE IMPUESTOS');
        $sheet->setCellValue('C' . $row, $utilAntesImp);
        $sheet->getStyle("B$row:C$row")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('B' . $row, '(-) 15% Participación Trabajadores');
        $sheet->setCellValue('C' . $row, $partic * -1);
        $row++;

        $sheet->setCellValue('B' . $row, '(-) 25% Impuesto a la Renta');
        $sheet->setCellValue('C' . $row, $impRenta * -1);
        $row++;

        $sheet->setCellValue('B' . $row, '(=) UTILIDAD NETA DEL EJERCICIO');
        $sheet->setCellValue('C' . $row, $utilNeta);
        $sheet->getStyle("B$row:C$row")->applyFromArray($grandTotalStyle);
        $row += 3;

        // Firmas
        $sheet->setCellValue('A' . $row, '_________________________');
        $sheet->setCellValue('C' . $row, '_________________________');
        $row++;
        $sheet->setCellValue('A' . $row, 'Representante Legal');
        $sheet->setCellValue('C' . $row, 'Contador General');
        $sheet->getStyle("A$row:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Formato General
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        
        // Formato de Números y Colores para Negativos
        $sheet->getStyle('C8:C' . ($row-5))->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    protected function writeSection(&$sheet, &$row, $title, $data, $subtotalLabel, $subtotalValue, $titleStyle, $totalStyle)
    {
        $sheet->setCellValue('B' . $row, $title);
        $sheet->getStyle('B' . $row . ':C' . $row)->applyFromArray($titleStyle);
        $row++;

        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['code']);
            $sheet->setCellValue('B' . $row, $item['name']);
            $sheet->setCellValue('C' . $row, $item['monto']);
            $row++;
        }

        $sheet->setCellValue('B' . $row, $subtotalLabel);
        $sheet->setCellValue('C' . $row, $subtotalValue);
        $sheet->getStyle("B$row:C$row")->applyFromArray($totalStyle);
        $row += 2;
    }
}

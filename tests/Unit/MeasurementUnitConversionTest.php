<?php

namespace Tests\Unit;

use App\Models\MeasurementUnit;
use PHPUnit\Framework\TestCase;

/**
 * Lógica de conversión de unidades (compra ↔ uso). Sin BD: prueba pura del modelo.
 */
class MeasurementUnitConversionTest extends TestCase
{
    private function unidad(string $tipo, float $factor): MeasurementUnit
    {
        return new MeasurementUnit(['tipo' => $tipo, 'factor' => $factor]);
    }

    public function test_convierte_entre_misma_familia(): void
    {
        $litro = $this->unidad('volumen', 1000);
        $ml    = $this->unidad('volumen', 1);

        // 20 L = 20.000 ml
        $this->assertEqualsWithDelta(20000, $litro->convertir(20, $ml), 0.0001);
        // 250 ml = 0.25 L
        $this->assertEqualsWithDelta(0.25, $ml->convertir(250, $litro), 0.0001);
    }

    public function test_convierte_longitud(): void
    {
        $metro = $this->unidad('longitud', 1);
        $cm    = $this->unidad('longitud', 0.01);

        // 3 m = 300 cm
        $this->assertEqualsWithDelta(300, $metro->convertir(3, $cm), 0.0001);
    }

    public function test_distinta_familia_no_convierte(): void
    {
        $litro = $this->unidad('volumen', 1000);
        $metro = $this->unidad('longitud', 1);

        // Masa ≠ volumen: el sistema NO inventa la conversión.
        $this->assertNull($litro->convertir(1, $metro));
        $this->assertFalse($litro->esCompatibleCon($metro));
    }

    public function test_sin_tipo_no_convierte(): void
    {
        $a = $this->unidad('conteo', 1);
        $b = new MeasurementUnit(['factor' => 1]); // sin tipo

        $this->assertNull($a->convertir(1, $b));
    }
}

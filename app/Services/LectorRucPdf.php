<?php

namespace App\Services;

/**
 * Lee el certificado de RUC del SRI y saca los datos de la compañía.
 *
 * El PDF del SRI trae el texto en streams comprimidos: se descomprimen y se
 * leen los operadores de texto. Sin dependencias nuevas: solo zlib, que PHP ya
 * trae. Si el formato del certificado cambia, los campos que no se reconozcan
 * vuelven vacíos y se llenan a mano — nunca se inventa un valor.
 */
class LectorRucPdf
{
    /** @return array<string, mixed> */
    public function leer(string $rutaPdf): array
    {
        return $this->interpretar($this->texto($rutaPdf));
    }

    /** Texto plano del PDF, en el orden en que aparece. */
    public function texto(string $rutaPdf): string
    {
        $crudo = file_get_contents($rutaPdf);
        $partes = [];

        if (preg_match_all('/stream\r?\n(.*?)endstream/s', $crudo, $coincidencias)) {
            foreach ($coincidencias[1] as $stream) {
                $plano = @gzuncompress($stream) ?: @gzinflate(substr($stream, 2)) ?: null;
                if ($plano) {
                    $partes[] = $plano;
                }
            }
        }

        $contenido = implode("\n", $partes);
        $trozos = [];

        // Operadores de texto: (texto) Tj  y  [(a) -2 (b)] TJ
        if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $contenido, $textos)) {
            foreach ($textos[0] as $t) {
                $trozos[] = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], substr($t, 1, -1));
            }
        }

        $plano = trim(preg_replace('/\s+/', ' ', implode(' ', $trozos)));

        return mb_convert_encoding($plano, 'UTF-8', 'Windows-1252');
    }

    /** @return array<string, mixed> */
    public function interpretar(string $texto): array
    {
        // Sin acentos y en mayúsculas: el certificado mezcla mayúsculas y la
        // codificación del PDF no es fiable para comparar tildes.
        $plano = mb_strtoupper(strtr($texto, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','Ü'=>'U',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u','•'=>' ',
        ]), 'UTF-8');

        $buscar = function (string $patron) use ($plano): ?string {
            return preg_match($patron, $plano, $m) ? trim($m[1], " .\t") : null;
        };
        $siNo = function (string $patron) use ($plano): ?bool {
            return preg_match($patron, $plano, $m) ? ($m[1] === 'SI') : null;
        };

        $actividades = [];
        if (preg_match_all('/([A-Z]\d{6})\s*-\s*(.+?)(?=\s+[A-Z]\d{6}\s*-|\s+ESTABLECIMIENTOS)/u', $plano, $m, PREG_SET_ORDER)) {
            foreach ($m as $a) {
                $actividades[] = ['codigo' => $a[1], 'descripcion' => trim($a[2], ' .')];
            }
        }

        return [
            'ruc'                    => $buscar('/NUMERO RUC\s+(\d{13})/'),
            'razon_social'           => $buscar('/CONTRIBUYENTES\s+(.+?)\s+NUMERO RUC/'),
            'representante_legal'    => $buscar('/REPRESENTANTE LEGAL\s+(.+?)\s+ESTADO/'),
            'estado'                 => $buscar('/ESTADO\s+(ACTIVO|PASIVO|SUSPENDIDO)/'),
            'regimen'                => $buscar('/REGIMEN\s+(GENERAL|RIMPE[A-Z ]*|MICROEMPRESAS)/'),
            'tipo_contribuyente'     => $buscar('/\b(SOCIEDADES|PERSONA NATURAL)\b/'),
            // El valor va antes del rótulo en el certificado.
            'fecha_constitucion'     => $this->fecha($buscar('/(\d{2}\/\d{2}\/\d{4})\s+FECHA DE CONSTITUCION/')),
            'inicio_actividades'     => $this->fecha($buscar('/INICIO DE ACTIVIDADES\s+(\d{2}\/\d{2}\/\d{4})/')),
            'jurisdiccion'           => $buscar('/(ZONA \\d+ \\/ [A-Z ]+ \\/ [A-Z]+)/'),
            'provincia'              => $buscar('/PROVINCIA:\s*([A-Z ]+?)\s+CANTON:/'),
            'canton'                 => $buscar('/CANTON:\s*([A-Z ]+?)\s+PARROQUIA:/'),
            'parroquia'              => $buscar('/PARROQUIA:\s*([A-Z ]+?)\s+[A-Z]{4}/'),
            'obligado_contabilidad'  => $siNo('/PARROQUIA:.*?\s(SI|NO)\s+UBICACION GEOGRAFICA/') ?? $siNo('/OBLIGADO A LLEVAR CONTABILIDAD\s+(SI|NO)/'),
            'agente_retencion'       => $siNo('/AGENTE DE RETENCION\s+(SI|NO)/'),
            'contribuyente_especial' => $siNo('/CONTRIBUYENTE ESPECIAL\s+(SI|NO)/'),
            'direccion'              => $buscar('/CALLE:\s*(.+?)\s+REFERENCIA:/'),
            'actividades'            => $actividades,
        ];
    }

    private function fecha(?string $ddmmyyyy): ?string
    {
        if (! $ddmmyyyy) {
            return null;
        }

        [$d, $m, $a] = explode('/', $ddmmyyyy);

        return "{$a}-{$m}-{$d}";
    }
}

<?php

namespace App\Services;

use App\Models\Administrador;
use App\Models\Empresa;
use App\Models\Socio;
use Illuminate\Support\Carbon;

/**
 * Los datos del REBEFICS de una empresa: quién es dueño y quién administra.
 *
 * Una sociedad en general (tabla 1 = 01) reporta sus titulares de derecho (10)
 * con su participación, que suma 100 %, y su administrador (05). Las personas
 * naturales con participación son beneficiarias finales por propiedad y van
 * también en la sección II con su nacimiento y su domicilio.
 *
 * El ERP arma los datos; el microservicio escribe y valida el XML.
 * Marco: skill `declaraciones-sri-ec` y la ficha técnica REBEFICS.
 */
class RebeficsService
{
    /** Anual: el corte es el 31 de diciembre y el mes va 00. */
    public function datos(int $empresaId, int $anio): array
    {
        $empresa = Empresa::withoutGlobalScopes()->findOrFail($empresaId);
        $ruc = preg_replace('/\D/', '', (string) $empresa->numero_identificacion);
        $corte = Carbon::create($anio, 12, 31);
        $avisos = [];

        $socios = Socio::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('activo', true)
            ->where('participacion', '>', 0)->get();
        $administradores = Administrador::withoutGlobalScopes()->where('empresa_id', $empresaId)
            ->whereDate('desde', '<=', $corte)
            ->where(fn ($q) => $q->whereNull('hasta')->orWhereDate('hasta', '>=', Carbon::create($anio, 1, 1)))
            ->get();

        if ($socios->isEmpty()) {
            $avisos[] = 'No hay socios con participación: el REBEFICS necesita los titulares de derecho.';
        }
        if ($administradores->isEmpty()) {
            $avisos[] = "No hay administrador vigente en {$anio}: cárgalo en Configuración → Administradores.";
        }

        $accionistas = [];
        foreach ($socios as $s) {
            $accionistas[] = $this->relacionado($ruc, $s->identificacion, $s->nombre, '10',
                number_format((float) $s->participacion, 2, '.', ''), $s->residencia_fiscal ?: '593');
        }
        foreach ($administradores as $a) {
            $accionistas[] = $this->relacionado($ruc, $a->identificacion, $a->nombre, '05', '0', '593');
        }

        $beneficiarios = $socios->map(fn (Socio $s) => [
            'esPersonaNatural'     => 'SI',
            'tipoIdentificacion'   => $this->tipoId($s->identificacion),
            'numeroIdentificacion' => $s->identificacion,
            ...$this->nombres($s->nombre),
            'fechaNacimiento'      => $s->fecha_nacimiento?->format('d/m/Y'),
            'porPropiedad'         => 'SI',
            'porcentajePropiedad'  => number_format((float) $s->participacion, 2, '.', ''),
            'porOtrosMotivos'      => '09',
            'porOtrosRelacionados' => 'NA',
            'porAdministracion'    => 'NO',
            'nacionalidadUno'      => $s->pais_nacionalidad ?: '593',
            'residenciaFiscal'     => $s->residencia_fiscal ?: '593',
            'jurisdiccion'         => 'NA',
            'ciudad'               => 'NA',
            'provincia'            => $s->provincia,
            'canton'               => $s->canton,
            'parroquia'            => $s->parroquia,
            'calle'                => $s->calle,
            'numero'               => $s->numero ?: 'SN',
            'interseccion'         => $s->interseccion,
            'codigoPostal'         => $s->codigo_postal,
            'referencia'           => $s->referencia,
        ])->values()->all();

        foreach ($socios->whereNull('fecha_nacimiento') as $s) {
            $avisos[] = "Falta la fecha de nacimiento de {$s->nombre}: complétala en Configuración → Socios.";
        }

        return [
            'cabecera' => [
                'TipoIDInformante'            => 'R',
                'IdInformante'                => $ruc,
                'TipoSociedad'                => '01',
                'Anio'                        => (string) $anio,
                'Mes'                         => '00',
                'PorcentajeAccionarioNoBolsa' => '0.00',
                'codigoOperativo'             => 'APS',
                'PorcentajeAccionarioBolsa'   => '0.00',
                'Anticipada'                  => 'NO',
                'TipoDeclaracion'             => '01',
            ],
            'accionistas'   => $accionistas,
            'beneficiarios' => $beneficiarios,
            'avisos'        => $avisos,
        ];
    }

    /** Un relacionado persona natural de primer nivel, residente en Ecuador. */
    private function relacionado(string $ruc, string $id, string $nombre, string $relacion, string $porcentaje, string $pais): array
    {
        return [
            'tipoSujeto'                    => '01',
            'tipoIdentificacion'            => $this->tipoId($id),
            'identificacionInformantePadre' => $ruc,
            'numeroIdentificacion'          => $id,
            ...$this->nombres($nombre),
            'nombresRazonSocial'            => $nombre,
            'tipoRegimenFiscal'             => '01',
            'tipoSociedadExt'               => 'NA',
            'figuraJuridicaExt'             => 'NA',
            'figuraJuridicaOtroExt'         => 'NA',
            'esSociedadPublicaExt'          => 'NO',
            'Menor10porc'                   => 'NA',
            'porcentajeAccionarioNoBolsaExt' => '0.0',
            'porcentajeAccionarioBolsaExt'  => '0.0',
            'esBeneficiarioFinal'           => 'SI',
            'codigoNivel'                   => '1',
            'tipoRelacionadoSociedad'       => $relacion,
            'porcentajeParticipacion'       => $porcentaje,
            // Socio con participación y administrador lo son por definición (art. 4 LRTI).
            'parteRelacionadaInformante'    => 'SI',
            'paisResidenciaFiscal'          => $pais,
        ];
    }

    private function tipoId(string $id): string
    {
        return match (strlen(preg_replace('/\D/', '', $id))) { 10 => 'C', 13 => 'R', default => 'P' };
    }

    /** El ERP guarda el nombre completo con los nombres primero: «JOSSELYN MARGORIE REMACHE TIXE». */
    private function nombres(string $completo): array
    {
        $p = preg_split('/\s+/', trim($completo));

        return match (count($p)) {
            1       => ['primerNombre' => $p[0], 'segundoNombre' => 'NA', 'primerApellido' => $p[0], 'segundoApellido' => 'NA'],
            2       => ['primerNombre' => $p[0], 'segundoNombre' => 'NA', 'primerApellido' => $p[1], 'segundoApellido' => 'NA'],
            3       => ['primerNombre' => $p[0], 'segundoNombre' => 'NA', 'primerApellido' => $p[1], 'segundoApellido' => $p[2]],
            default => ['primerNombre' => $p[0], 'segundoNombre' => $p[1], 'primerApellido' => $p[2], 'segundoApellido' => implode(' ', array_slice($p, 3))],
        };
    }
}

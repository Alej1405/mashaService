<?php

namespace App\Http\Requests\Sitio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * El contacto del sitio es un solo campo: un correo o un telefono.
 * 'mensaje' y 'origen' son opcionales; 'sitio_web' es la trampa para robots
 * (un humano no la ve, un formulario automatico la llena).
 */
class GuardarMensajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contacto'  => ['required', 'string', 'min:6', 'max:190'],
            'mensaje'   => ['nullable', 'string', 'max:2000'],
            'origen'    => ['nullable', 'string', 'max:160'],
            'sitio_web' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'contacto.required' => 'Escribe un correo o un número donde responderte.',
            'contacto.min'      => 'Ese dato es muy corto para poder responderte.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $contacto = trim((string) $this->input('contacto'));

                $esCorreo   = filter_var($contacto, FILTER_VALIDATE_EMAIL) !== false;
                $esTelefono = preg_match('/^\+?[\d\s\-()]{7,20}$/', $contacto) === 1;

                if (! $esCorreo && ! $esTelefono) {
                    $validator->errors()->add('contacto', 'Debe ser un correo o un número de teléfono.');
                }
            },
        ];
    }
}

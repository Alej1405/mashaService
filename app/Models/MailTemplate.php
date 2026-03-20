<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MailTemplate extends Model
{
    use HasFactory, HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'name',
        'subject',
        'header_text',
        'header_background_color',
        'header_text_color',
        'body',
        'button_text',
        'button_url',
        'button_color',
        'button_text_color',
        'footer_text',
        'font_family',
        'base_font_size',
        'text_color',
        'background_color',
        'content_background_color',
    ];

    protected $casts = [
        'base_font_size' => 'integer',
    ];

    /**
     * Genera el HTML completo con CSS inline listo para enviar por correo.
     * Si se provee $logoUrl se inserta el logo de la empresa encima del encabezado.
     */
    public function toHtml(?string $logoUrl = null): string
    {
        $fontStack = match ($this->font_family) {
            'Inter'        => "'Inter', Arial, sans-serif",
            'Georgia'      => "Georgia, 'Times New Roman', serif",
            'Verdana'      => 'Verdana, Geneva, sans-serif',
            'Trebuchet MS' => "'Trebuchet MS', Helvetica, sans-serif",
            'Tahoma'       => 'Tahoma, Geneva, sans-serif',
            default        => 'Arial, Helvetica, sans-serif',
        };

        $fontSize = ($this->base_font_size ?? 16) . 'px';
        $bg       = $this->background_color ?? '#f3f4f6';
        $contentBg = $this->content_background_color ?? '#ffffff';
        $textColor = $this->text_color ?? '#374151';

        // Sección de logo de la empresa
        $logoHtml = '';
        if (! empty($logoUrl)) {
            $logoHtml = "
            <tr>
              <td style=\"background-color:{$contentBg};padding:24px 40px 0;text-align:center;\">
                <img src=\"{$logoUrl}\" alt=\"Logo\" style=\"max-height:64px;max-width:200px;object-fit:contain;\">
              </td>
            </tr>";
        }

        // Sección de encabezado
        $headerHtml = '';
        if (! empty($this->header_text)) {
            $headerBg      = $this->header_background_color ?? '#1e40af';
            $headerTxtColor = $this->header_text_color ?? '#ffffff';
            $headerHtml = "
            <tr>
              <td style=\"background-color:{$headerBg};padding:32px 40px;\">
                <p style=\"margin:0;color:{$headerTxtColor};font-size:22px;font-weight:700;line-height:1.3;\">{$this->header_text}</p>
              </td>
            </tr>";
        }

        // Sección de botón CTA
        $buttonHtml = '';
        if (! empty($this->button_text)) {
            $btnColor    = $this->button_color ?? '#1e40af';
            $btnTxtColor = $this->button_text_color ?? '#ffffff';
            $btnUrl      = ! empty($this->button_url) ? $this->button_url : '#';
            $buttonHtml = "
            <tr>
              <td style=\"padding:8px 40px 32px;text-align:center;\">
                <a href=\"{$btnUrl}\" style=\"display:inline-block;background-color:{$btnColor};color:{$btnTxtColor};padding:14px 36px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;\">{$this->button_text}</a>
              </td>
            </tr>";
        }

        // Sección de pie
        $footerHtml = '';
        if (! empty($this->footer_text)) {
            $footerHtml = "
            <tr>
              <td style=\"background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 40px;color:#6b7280;font-size:12px;text-align:center;line-height:1.6;\">
                {$this->footer_text}
              </td>
            </tr>";
        }

        $body = $this->body ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$this->subject}</title>
</head>
<body style="margin:0;padding:0;background-color:{$bg};font-family:{$fontStack};">
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:{$bg};min-height:100%;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:{$contentBg};border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
          {$logoHtml}
          {$headerHtml}
          <tr>
            <td style="padding:40px;color:{$textColor};font-size:{$fontSize};line-height:1.75;">
              {$body}
            </td>
          </tr>
          {$buttonHtml}
          {$footerHtml}
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}

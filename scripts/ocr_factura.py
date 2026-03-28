#!/usr/bin/env python3
"""
OCR de facturas del SRI (Ecuador) usando OpenAI Vision.
Uso: python3 ocr_factura.py <ruta_imagen> <openai_api_key>
Salida: JSON con los campos extraídos o {"error": "mensaje"}
"""

import sys
import json
import base64
import urllib.request
import urllib.error


def imagen_a_base64(ruta: str) -> str:
    with open(ruta, "rb") as f:
        return base64.b64encode(f.read()).decode("utf-8")


def extraer_factura(ruta_imagen: str, api_key: str) -> dict:
    b64 = imagen_a_base64(ruta_imagen)

    # Detectar extensión para el mime type
    ext = ruta_imagen.lower().split(".")[-1]
    mime = {"jpg": "image/jpeg", "jpeg": "image/jpeg", "png": "image/png", "webp": "image/webp"}.get(ext, "image/jpeg")

    prompt = """Analiza esta factura del SRI de Ecuador y extrae los siguientes datos en formato JSON.
Si un campo no se encuentra claramente, usa null.

Devuelve ÚNICAMENTE el JSON, sin texto adicional, con esta estructura exacta:
{
  "ruc_proveedor": "string o null",
  "razon_social": "string o null",
  "numero_factura": "string (ej: 001-001-000000001) o null",
  "fecha": "string formato YYYY-MM-DD o null",
  "items": [
    {
      "descripcion": "string",
      "cantidad": número,
      "precio_unitario": número,
      "subtotal": número
    }
  ],
  "subtotal_sin_iva": número o null,
  "subtotal_con_iva": número o null,
  "iva_porcentaje": número o null,
  "iva_monto": número o null,
  "total": número o null
}"""

    payload = {
        "model": "gpt-4o-mini",
        "max_tokens": 1000,
        "messages": [
            {
                "role": "user",
                "content": [
                    {"type": "text", "text": prompt},
                    {"type": "image_url", "image_url": {"url": f"data:{mime};base64,{b64}", "detail": "high"}},
                ],
            }
        ],
    }

    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        "https://api.openai.com/v1/chat/completions",
        data=data,
        headers={
            "Content-Type": "application/json",
            "Authorization": f"Bearer {api_key}",
        },
        method="POST",
    )

    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            result = json.loads(resp.read().decode("utf-8"))
            content = result["choices"][0]["message"]["content"].strip()
            # Limpiar posibles bloques markdown
            if content.startswith("```"):
                content = content.split("```")[1]
                if content.startswith("json"):
                    content = content[4:]
            return json.loads(content)
    except urllib.error.HTTPError as e:
        error_body = e.read().decode("utf-8")
        return {"error": f"API error {e.code}: {error_body}"}
    except Exception as e:
        return {"error": str(e)}


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Uso: ocr_factura.py <imagen> <api_key>"}))
        sys.exit(1)

    ruta = sys.argv[1]
    key = sys.argv[2]

    resultado = extraer_factura(ruta, key)
    print(json.dumps(resultado, ensure_ascii=False))

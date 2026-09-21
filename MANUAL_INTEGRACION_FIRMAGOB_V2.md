# Manual Definitivo de Integración con FirmaGob v2 (Gobierno Digital - Chile)
### Guía Práctica de Arquitectura, Implementación y Resolución de Errores para Firma Electrónica Avanzada (FEA)

---

## 1. Introducción y Arquitectura

La plataforma **FirmaGob v2** de la Secretaría de Gobierno Digital (Ministerio de Hacienda de Chile) permite a las instituciones públicas firmar documentos electrónicamente mediante certificados de Firma Electrónica Avanzada (FEA) custodiados en un Repositorio Centralizado de Firmas (HSM / Banco de Firmas).

### Flujo de Integración
```
┌────────────────────────────────┐            ┌────────────────────────────────┐
│   Aplicación Cliente (PHP)     │            │    API FirmaGob (v2)           │
│                                │            │                                │
│ 1. Genera PDF / Hash           │            │                                │
│ 2. Calcula Checksum SHA256     │            │                                │
│ 3. Genera JWT (HS256 con Secret)            │                                │
│ 4. Envía POST a /files/tickets ├───────────►│ 1. Valida JWT y Secret         │
│                                │            │ 2. Valida Permisos en RA       │
│                                │            │ 3. Valida OTP (si es Atendida) │
│                                │            │ 4. Estampa Firma Criptográfica │
│ 5. Recibe PDF Firmado en B64   │◄───────────┤ 5. Retorna HTTP 200 OK         │
└────────────────────────────────┘            └────────────────────────────────┘
```

---

## 2. Requisitos y Configuración Previa

### 2.1. Registro en la RA (Autoridad de Registro)
Para que el sistema pueda conectarse, el **Coordinador Institucional de Firma Digital** debe registrar:
1. **La Aplicación:** Nombre del sistema (ej: `Sistema OPI`).
2. **Obtención de Credenciales:**
   * `api_token_key`: Identificador público único de la aplicación (formato UUID, ej: `0ebefd75-ce25-48c2-ba39-7802fe78065c`).
   * `Secret`: Clave criptográfica simétrica asignada a la aplicación para firmar los JWT.
3. **Asignación de Certificados:**
   * En la RA se deben vincular los RUNs de los funcionarios autorizados para firmar con la aplicación registrada.

---

## 3. Modalidades de Firma: Atendida vs Desatendida

FirmaGob soporta dos modalidades operativas con diferencias técnicas críticas:

| Parámetro | Firma Atendida (Con Intervención) | Firma Desatendida (1-Clic Automática) |
| :--- | :--- | :--- |
| **Uso Típico** | Firmas individuales de jefaturas, decretos, resoluciones. | Procesos masivos, interoperabilidad, flujos automáticos sin fricción. |
| **Autenticación** | Requiere código OTP de 6 dígitos (Google Authenticator / FreeOTP). | No requiere OTP. Firma instantánea. |
| **Cabecera HTTP** | `OTP: 123456` (Obligatoria). | **NO** se debe enviar la cabecera `OTP`. |
| **Claim `purpose` en JWT** | `"Propósito General"` (o el asignado en la RA). | **`"Desatendido"`** (Obligatorio). |
| **Habilitación en RA** | Certificado emitido como propósito general con enrolamiento de dispositivo. | Aplicación autorizada explícitamente para el certificado desatendido. |

---

## 4. Construcción del Token JWT (HS256)

El token JWT autentica la petición ante FirmaGob. **No se envían datos en texto plano fuera del JWT.**

### 4.1. Estructura del Header
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

### 4.2. Estructura del Payload (Claims)
```json
{
  "entity": "Ilustre Municipalidad de Lebu",
  "run": "17439829",
  "expiration": "2026-09-21T11:45:00",
  "purpose": "Desatendido"
}
```
* **`entity`**: Nombre institucional exacto registrado en la RA (sensible a mayúsculas, minúsculas y tildes).
* **`run`**: RUN del firmante **solo números, sin puntos, sin guion y sin dígito verificador**.
* **`expiration`**: Fecha/hora en zona de Chile (`America/Santiago`), formato ISO `YYYY-MM-DDTHH:MM:SS`. No puede superar los 30 minutos a futuro ni estar en el pasado.
* **`purpose`**: `"Desatendido"` para modo desatendido; `"Propósito General"` para modo atendido.

---

## 5. Trampas Críticas y Lecciones Aprendidas en PHP

### ⚠️ Trampa 1: El Formato del Secret en `hash_hmac()`
* **El error común:** Se suele sugerir decodificar el Secret con `base64_decode($secret)`. Si el Secret fue emitido como un string alfanumérico directo, decodificarlo corrompe la clave y FirmaGob arrojará:  
  `HTTP 400: "El token no está correctamente firmado"`.
* **La regla de oro:** Utilizar el Secret directamente como **string UTF-8**:
  ```php
  $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
  ```

### ⚠️ Trampa 2: El Tag `<BASE64VALUE>` en el Layout XML
* **El error común:** Copiar el ejemplo del Manual oficial de Gobierno Digital donde `<BASE64VALUE></BASE64VALUE>` viene vacío. Al recibir `<image>BASE64</image>` con un nodo vacío, el parser de FirmaGob falla arrojando:  
  `HTTP 404: "Formato de contenido o layout incorrecto"` o `HTTP 400: "Cuerpo de la solicitud mal formada"`.
* **La regla de oro:**
  1. Si solo se desea **firma digital pura (invisible)**, **omitir completamente la propiedad `layout`** del JSON.
  2. Si se desea **estampa visual**, `<BASE64VALUE>` **debe contener una imagen real en Base64** (ej. `logo.png` o timbre institucional):
  ```xml
  <AgileSignerConfig>
      <Application id="THIS-CONFIG">
          <pdfPassword/>
          <Signature>
              <Visible active="true" layer2="false" label="true" pos="1">
                  <llx>40</llx>
                  <lly>50</lly>
                  <urx>210</urx>
                  <ury>130</ury>
                  <page>LAST</page>
                  <image>BASE64</image>
                  <BASE64VALUE>iVBORw0KGgoAAAANSUhEUgAA...</BASE64VALUE>
              </Visible>
          </Signature>
      </Application>
  </AgileSignerConfig>
  ```

### ⚠️ Trampa 3: Coordenadas del PDF en AgileSigner
* Las coordenadas `(llx, lly)` y `(urx, ury)` utilizan el sistema cartesiano estándar de PDF:
  * Origen `(0, 0)` en la **esquina inferior izquierda**.
  * `page`: Puede ser el número de página (`1`, `2`, ...) o la palabra clave `LAST` (última hoja).

---

## 6. Endpoints y Variables de Entorno

### 6.1. URLs de Conexión
* **Producción:** `https://api.firma.digital.gob.cl/firma/v2/files/tickets`
* **Certificación / Sandbox:** `https://api.firma.cert.digital.gob.cl/firma/v2/files/tickets`

### 6.2. Variables recomendadas en `.env` / Dokploy
```ini
FIRMAGOB_MODO=DESATENDIDA
FIRMAGOB_AMBIENTE=PRODUCCION
FIRMAGOB_API_URL=https://api.firma.digital.gob.cl/firma/v2/files/tickets
FIRMAGOB_ENTITY="Ilustre Municipalidad de Lebu"
FIRMAGOB_PURPOSE="Desatendido"
FIRMAGOB_API_TOKEN_KEY="0ebefd75-ce25-48c2-ba39-7802fe78065c"
FIRMAGOB_SECRET="tu_secret_aqui"
```

---

## 7. Módulo de Código PHP Reutilizable

### 7.1. Helper de Firma (`firmagob_helper.php`)

```php
<?php
/**
 * Helper de Integración Oficial con FirmaGob v2 (Chile)
 */

function firmagob_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function firmagob_limpiar_run($run) {
    $run_limpio = preg_replace('/[^0-9kK]/', '', (string)$run);
    if (strlen($run_limpio) > 1 && strpos((string)$run, '-') !== false) {
        $run_limpio = substr($run_limpio, 0, -1);
    }
    return preg_replace('/[^0-9]/', '', $run_limpio);
}

function firmagob_generar_jwt($run, $entity, $purpose, $secret, $minutos_expiracion = 15) {
    $run_limpio = firmagob_limpiar_run($run);
    
    $fecha_exp = new DateTime('now', new DateTimeZone('America/Santiago'));
    $fecha_exp->modify("+{$minutos_expiracion} minutes");
    $expiration = $fecha_exp->format('Y-m-d\TH:i:s');

    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
        'entity'     => $entity,
        'run'        => (string)$run_limpio,
        'expiration' => $expiration,
        'purpose'    => $purpose
    ];

    $header_encoded  = firmagob_base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $payload_encoded = firmagob_base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", trim($secret), true);
    $signature_encoded = firmagob_base64url_encode($signature);

    return "$header_encoded.$payload_encoded.$signature_encoded";
}

function firmagob_formatear_run($run) {
    $run_limpio = preg_replace('/[^0-9kK]/', '', (string)$run);
    if (strlen($run_limpio) < 2) return (string)$run;
    $dv = strtoupper(substr($run_limpio, -1));
    $cuerpo = substr($run_limpio, 0, -1);
    return number_format((int)$cuerpo, 0, '', '.') . '-' . $dv;
}

function firmagob_generar_estampa_dinamica_base64($nombre, $run, $cargo = '', $fecha_hora = null, $entidad = 'Ilustre Municipalidad de Lebu') {
    if (!extension_loaded('gd')) {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    $width = 380;
    $height = 110;
    $im = imagecreatetruecolor($width, $height);

    $bg_color     = imagecolorallocate($im, 255, 255, 255);
    $border_color = imagecolorallocate($im, 30, 64, 175);
    $hdr_bg       = imagecolorallocate($im, 239, 246, 255);
    $text_blue    = imagecolorallocate($im, 29, 78, 216);
    $text_dark    = imagecolorallocate($im, 15, 23, 42);
    $text_muted   = imagecolorallocate($im, 100, 116, 139);

    imagefilledrectangle($im, 0, 0, $width, $height, $bg_color);
    imagefilledrectangle($im, 2, 2, $width - 3, 22, $hdr_bg);
    imagerectangle($im, 0, 0, $width - 1, $height - 1, $border_color);
    imagerectangle($im, 1, 1, $width - 2, $height - 2, $border_color);

    $run_formateado = firmagob_formatear_run($run);
    if (empty($fecha_hora)) {
        $dt = new DateTime('now', new DateTimeZone('America/Santiago'));
        $fecha_hora = $dt->format('d/m/Y H:i:s') . ' CLT';
    }

    $nombre_limpio = mb_strtoupper(trim((string)$nombre), 'UTF-8');
    if (empty($nombre_limpio)) $nombre_limpio = 'FUNCIONARIO AUTORIZADO';
    if (mb_strlen($nombre_limpio) > 36) $nombre_limpio = mb_substr($nombre_limpio, 0, 33) . '...';

    $cargo_limpio = trim((string)$cargo) ?: 'Funcionario Autorizado';
    if (mb_strlen($cargo_limpio) > 36) $cargo_limpio = mb_substr($cargo_limpio, 0, 33) . '...';

    imagestring($im, 3, 10, 4, 'FIRMADO ELECTRONICAMENTE (FEA)', $text_blue);
    imagestring($im, 2, 10, 28, 'Firmante: ' . $nombre_limpio, $text_dark);
    imagestring($im, 2, 10, 46, 'RUN: ' . $run_formateado . ' | Cargo: ' . $cargo_limpio, $text_dark);
    imagestring($im, 2, 10, 64, 'Fecha: ' . $fecha_hora, $text_muted);
    imagestring($im, 2, 10, 80, 'Entidad: ' . $entidad, $text_muted);
    imagestring($im, 1, 10, 96, 'Validez Legal: Ley No 19.799 sobre Firma Electronica', $text_muted);

    ob_start();
    imagepng($im);
    $raw_png = ob_get_clean();
    imagedestroy($im);

    return base64_encode($raw_png);
}

function firmagob_obtener_layout_xml($etapa = 'DEFAULT', $imagen_base64 = null, $nombre = '', $run = '', $cargo = '') {
    if (empty($imagen_base64)) {
        $imagen_base64 = firmagob_generar_estampa_dinamica_base64($nombre, $run, $cargo);
    }

    // Coordenadas cuadrante inferior derecho (ejemplo)
    $llx = 350; $lly = 60; $urx = 550; $ury = 140;

    return '<AgileSignerConfig>' .
           '<Application id="THIS-CONFIG">' .
           '<pdfPassword/>' .
           '<Signature>' .
           '<Visible active="true" layer2="false" label="true" pos="1">' .
           "<llx>{$llx}</llx><lly>{$lly}</lly><urx>{$urx}</urx><ury>{$ury}</ury>" .
           '<page>LAST</page>' .
           '<image>BASE64</image>' .
           "<BASE64VALUE>{$imagen_base64}</BASE64VALUE>" .
           '</Visible>' .
           '</Signature>' .
           '</Application>' .
           '</AgileSignerConfig>';
}

function firmagob_firmar_pdf($ruta_pdf, $run, $entity, $purpose, $api_token_key, $secret, $api_url, $otp = null, $nombre_firmante = '', $cargo_firmante = '') {
    if (!file_exists($ruta_pdf)) {
        throw new Exception("El archivo PDF a firmar no existe: $ruta_pdf");
    }

    $pdf_content = file_get_contents($ruta_pdf);
    $pdf_base64  = base64_encode($pdf_content);
    $checksum    = hash('sha256', $pdf_content);

    $jwt = firmagob_generar_jwt($run, $entity, $purpose, $secret);
    $layout_xml = firmagob_obtener_layout_xml('DEFAULT', null, $nombre_firmante, $run, $cargo_firmante);

    $file_item = [
        'content-type' => 'application/pdf',
        'content'      => $pdf_base64,
        'description'  => basename($ruta_pdf),
        'checksum'     => $checksum
    ];
    if ($layout_xml !== null) {
        $file_item['layout'] = $layout_xml;
    }

    $payload = [
        'token'         => $jwt,
        'api_token_key' => $api_token_key,
        'files'         => [$file_item]
    ];

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    if (!empty($otp) && strtoupper($purpose) !== 'DESATENDIDO') {
        $headers[] = 'OTP: ' . trim($otp);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) throw new Exception("Error cURL de red con FirmaGob: $curl_err");

    $data = json_decode($response_body, true);

    if ($http_code === 200 && isset($data['files'][0]['content'])) {
        return [
            'success'        => true,
            'pdf_firmado'    => base64_decode($data['files'][0]['content']),
            'checksum_firma' => $data['files'][0]['checksum_signed'] ?? null,
            'id_solicitud'   => $data['idSolicitud'] ?? null
        ];
    }

    $msg = $data['error'] ?? ($data['message'] ?? "Error HTTP $http_code");
    throw new Exception("FirmaGob rechazó la firma (HTTP $http_code): $msg");
}
```

---

## 8. Tabla de Diagnóstico y Resolución Rápida de Errores

| Código HTTP | Mensaje de Error | Causa Real | Solución |
| :--- | :--- | :--- | :--- |
| **400** | *El token no está correctamente firmado* | Se intentó hacer `base64_decode` al Secret o la clave no coincide. | Usar el string UTF-8 directo del Secret en `hash_hmac`. |
| **400** | *El certificado no es válido, se encuentra expirado o no tiene permisos* | El RUN, Entity o Purpose no coinciden con la autorización en la RA. | Si la app es desatendida, usar `purpose: "Desatendido"`. Verificar que el RUN pertenezca a la institución. |
| **404** | *Formato de contenido o layout incorrecto* | El XML de layout contiene `<BASE64VALUE></BASE64VALUE>` vacío. | Omitir `layout` o inyectar una imagen Base64 válida. |
| **404** | *Aplicación no existe en la RA* | Se está consultando el endpoint de Certificación con credenciales de Producción (o viceversa). | Usar `api.firma.digital.gob.cl` para credenciales de Producción. |
| **412** | *Verificación de OTP fallida* | Código OTP de 6 dígitos ingresado incorrectamente o reloj del teléfono desfasado. | Sincronizar la hora del dispositivo móvil y reintentar. |
| **429** | *Ha excedido el número máximo de intentos de OTP* | 5 intentos erróneos consecutivos de OTP. | Esperar el tiempo de desbloqueo de seguridad impuesto por FirmaGob. |

---

## 9. Recomendación de Arquitectura para Nuevos Proyectos

1. **Crear siempre una consola de diagnóstico (`diagnostico_firmagob.php`):** Permite aislar problemas de red, JWT, permisos y certificados sin depender del flujo transaccional del sistema.
2. **Permitir alternancia dinámica:** Disponer de configuración en Base de Datos para conmutar entre Atendida y Desatendida sin necesidad de tocar código ni reiniciar servidores.
3. **Validación Visual vs Validación Digital:** Recordar que la estampa visual es solo un adorno gráfico; la verdadera validez jurídica la otorga el diccionario criptográfico PAdES del PDF firmado.

<?php
// mailer_helper.php - Motor de Envíos por Correo Institucional vía SMTP / Dockploy

/**
 * Obtiene la configuración de SMTP desde variables de entorno Dockploy
 */
function obtener_config_smtp() {
    return [
        'host'   => getenv('SMTP_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com'),
        'user'   => getenv('SMTP_USER') ?: (defined('SMTP_USER') ? SMTP_USER : 'informatica@lebu.cl'),
        'pass'   => getenv('SMTP_PASS') ?: (defined('SMTP_PASS') ? SMTP_PASS : ''),
        'port'   => intval(getenv('SMTP_PORT') ?: (defined('SMTP_PORT') ? SMTP_PORT : 587)),
        'secure' => getenv('SMTP_SECURE') ?: (defined('SMTP_SECURE') ? SMTP_SECURE : 'tls'),
        'from'   => getenv('SMTP_FROM') ?: (defined('SMTP_FROM') ? SMTP_FROM : 'informatica@lebu.cl'),
        'name'   => 'Municipalidad de Lebu - Gestión OPI'
    ];
}

/**
 * Envía un correo electrónico institucional HTML seguro.
 * 
 * @param string $para_email Email del destinatario
 * @param string $para_nombre Nombre del destinatario
 * @param string $asunto Asunto del correo
 * @param string $cuerpo_html Contenido HTML del mensaje
 * @return bool True si se envió correctamente, False en caso de error
 */
function enviar_correo_institucional($para_email, $para_nombre, $asunto, $cuerpo_html) {
    $cfg = obtener_config_smtp();

    // Estructura HTML Institucional Responsive
    $html_completo = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
            .header { background-color: #1e293b; color: #ffffff; padding: 25px; text-align: center; }
            .header h2 { margin: 0; font-size: 20px; font-weight: bold; }
            .header p { margin: 5px 0 0 0; font-size: 12px; opacity: 0.8; }
            .content { padding: 30px; font-size: 14px; line-height: 1.6; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #0d6efd; color: #ffffff !important; text-decoration: none; font-weight: bold; border-radius: 6px; margin: 20px 0; }
            .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 15px; text-align: center; font-size: 11px; color: #64748b; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>MUNICIPALIDAD DE LEBU</h2>
                <p>Sistema de Gestión de Compras OPI</p>
            </div>
            <div class="content">
                ' . $cuerpo_html . '
            </div>
            <div class="footer">
                Este es un mensaje automático generado por el Sistema de Gestión OPI.<br>
                Departamento de Informática - Municipalidad de Lebu.
            </div>
        </div>
    </body>
    </html>';

    // 1. Intento por Socket SMTP directo (RFC 822 / TLS)
    if (!empty($cfg['host']) && !empty($cfg['pass'])) {
        try {
            $enviado_socket = enviar_via_smtp_socket($cfg, $para_email, $para_nombre, $asunto, $html_completo);
            if ($enviado_socket) return true;
        } catch (Exception $e) {
            error_log("Error SMTP Socket: " . $e->getMessage());
        }
    }

    // 2. Fallback a mail() de PHP
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . mb_encode_mimeheader($cfg['name'], "UTF-8") . " <" . $cfg['from'] . ">" . "\r\n";
    $headers .= "Reply-To: " . $cfg['from'] . "\r\n";

    return @mail($para_email, mb_encode_mimeheader($asunto, "UTF-8"), $html_completo, $headers);
}

/**
 * Cliente SMTP Socket ligero en PHP puro sobre puerto 587 (STARTTLS)
 */
function enviar_via_smtp_socket($cfg, $para_email, $para_nombre, $asunto, $html_body) {
    $socket = fsockopen($cfg['host'], $cfg['port'], $errno, $errstr, 10);
    if (!$socket) throw new Exception("No se pudo conectar a {$cfg['host']}:{$cfg['port']}");

    $res = fgets($socket, 512);

    fputs($socket, "EHLO " . gethostname() . "\r\n");
    while ($line = fgets($socket, 512)) {
        if (substr($line, 3, 1) == " ") break;
    }

    if ($cfg['secure'] === 'tls' || $cfg['port'] === 587) {
        fputs($socket, "STARTTLS\r\n");
        $res = fgets($socket, 512);
        if (substr($res, 0, 3) != "220") throw new Exception("TLS error: $res");

        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);

        fputs($socket, "EHLO " . gethostname() . "\r\n");
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) == " ") break;
        }
    }

    fputs($socket, "AUTH LOGIN\r\n");
    $res = fgets($socket, 512);
    
    fputs($socket, base64_encode($cfg['user']) . "\r\n");
    $res = fgets($socket, 512);

    fputs($socket, base64_encode($cfg['pass']) . "\r\n");
    $res = fgets($socket, 512);
    if (substr($res, 0, 3) != "235") throw new Exception("AUTH ERROR: $res");

    fputs($socket, "MAIL FROM: <{$cfg['from']}>\r\n");
    $res = fgets($socket, 512);

    fputs($socket, "RCPT TO: <{$para_email}>\r\n");
    $res = fgets($socket, 512);

    fputs($socket, "DATA\r\n");
    $res = fgets($socket, 512);

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . mb_encode_mimeheader($cfg['name'], "UTF-8") . " <{$cfg['from']}>\r\n";
    $headers .= "To: " . mb_encode_mimeheader($para_nombre, "UTF-8") . " <{$para_email}>\r\n";
    $headers .= "Subject: " . mb_encode_mimeheader($asunto, "UTF-8") . "\r\n";

    fputs($socket, $headers . "\r\n" . $html_body . "\r\n.\r\n");
    $res = fgets($socket, 512);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return (substr($res, 0, 3) == "250");
}

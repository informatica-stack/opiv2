<?php
// rut_helper.php - Helper de Validación y Formateo de RUT Chileno (Algoritmo Módulo 11)

/**
 * Valida un RUT chileno según el algoritmo oficial Módulo 11.
 * Acepa formatos: "12345678-9", "12.345.678-k", "123456789", etc.
 * 
 * @param string $rut
 * @return bool
 */
function validar_rut_chileno($rut) {
    if (empty($rut) || !is_string($rut)) {
        return false;
    }

    // 1. Limpieza de caracteres no alfanuméricos
    $rut_limpio = preg_replace('/[^0-9kK]/', '', $rut);
    
    // Un RUT válido debe tener entre 7 y 9 caracteres (6 a 8 dígitos + 1 DV)
    if (strlen($rut_limpio) < 7 || strlen($rut_limpio) > 9) {
        return false;
    }

    // 2. Separa cuerpo numérico y Dígito Verificador (DV)
    $dv = strtoupper(substr($rut_limpio, -1));
    $cuerpo = substr($rut_limpio, 0, -1);

    if (!ctype_digit($cuerpo)) {
        return false;
    }

    // 3. Algoritmo Módulo 11
    $suma = 0;
    $multiplicador = 2;

    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $suma += intval($cuerpo[$i]) * $multiplicador;
        $multiplicador = ($multiplicador === 7) ? 2 : $multiplicador + 1;
    }

    $resto = $suma % 11;
    $dv_calculado = 11 - $resto;

    if ($dv_calculado === 11) {
        $dv_esperado = '0';
    } elseif ($dv_calculado === 10) {
        $dv_esperado = 'K';
    } else {
        $dv_esperado = strval($dv_calculado);
    }

    return ($dv === $dv_esperado);
}

/**
 * Formatea un RUT a su representación estándar chilena (ej: "12.345.678-K").
 * 
 * @param string $rut
 * @return string
 */
function formatear_rut_chileno($rut) {
    $rut_limpio = preg_replace('/[^0-9kK]/', '', $rut);
    if (strlen($rut_limpio) < 2) return $rut;

    $dv = strtoupper(substr($rut_limpio, -1));
    $cuerpo = substr($rut_limpio, 0, -1);

    return number_format(intval($cuerpo), 0, '', '.') . '-' . $dv;
}

# Guía de Subsanación y Evidencias para Certificación ClaveÚnica
**Plataforma:** SOPI Municipalidad de Lebu  
**Institución:** Municipalidad de Lebu  

Esta guía contiene las instrucciones exactas y el material necesario para responder a las observaciones de la Secretaría de Gobierno Digital y obtener la aprobación definitiva de la certificación.

---

## 📌 Resumen de Subsanaciones Realizadas

| # | Observación de Gobierno Digital | Estado Técnico | Archivo / Componente Modificado |
|---|---|---|---|
| **1** | **El Botón CU no cumple los lineamientos oficiales** |  **Corregido** | [login.php](file:///c:/Users/Informática/Documents/GitHub/opiv2/login.php), [head.php](file:///c:/Users/Informática/Documents/GitHub/opiv2/head.php), [css/style.css](file:///c:/Users/Informática/Documents/GitHub/opiv2/css/style.css) |
| **2** | **Evidencia de credenciales ocultas (`Client_Id` y `Client_Secret`)** |  **Listo para capturar** | [claveunica_helper.php](file:///c:/Users/Informática/Documents/GitHub/opiv2/claveunica_helper.php) y Panel de Variables de Entorno |

---

## 🖼️ Capturas de Pantalla Requeridas (Evidencias)

Debe tomar las siguientes **4 capturas de pantalla** para adjuntar a la respuesta del ticket:

---

### Captura 1: Panel de Variables de Entorno del Servidor (Dockploy / Docker)
* **Dónde tomarla:** En el panel de su servidor (**Dockploy** > Aplicación SOPI > Pestaña **Environment Variables** / Variables de Entorno) o en su archivo de configuración de producción.
* **Qué debe mostrar la captura:**
  * La variable `CLAVEUNICA_CLIENT_ID` con su valor alfanumérico visible asignado por Gobierno Digital.
  * La variable `CLAVEUNICA_CLIENT_SECRET` (puede estar parcialmente oculta o visible según lo que permita su panel).
  * La variable `CLAVEUNICA_REDIRECT_URI` apuntando a `https://opi.munilebu.gob.cl/login.php`.
* **Propósito:** Demuestra que las credenciales no están escritas a mano en el código, sino gestionadas a nivel de infraestructura segura de servidor.

---

### Captura 2: Lectura de Variables en el Código Backend
* **Dónde tomarla:** En su editor de código (VS Code u otro) abriendo el archivo `claveunica_helper.php` entre las líneas 9 y 37.
* **Qué debe mostrar la captura:**
```php
function obtener_configuracion_claveunica() {
    $client_id = getenv('CLAVEUNICA_CLIENT_ID') ?: ($_ENV['CLAVEUNICA_CLIENT_ID'] ?? ($_SERVER['CLAVEUNICA_CLIENT_ID'] ?? ''));
    $client_secret = getenv('CLAVEUNICA_CLIENT_SECRET') ?: ($_ENV['CLAVEUNICA_CLIENT_SECRET'] ?? ($_SERVER['CLAVEUNICA_CLIENT_SECRET'] ?? ''));
    ...
```
* **Propósito:** Demuestra que el código PHP consume dinámicamente las credenciales desde el entorno (`getenv`).

---

### Captura 3: Uso Seguro de Credenciales en Petición Backend (cURL)
* **Dónde tomarla:** En su editor de código en el archivo `claveunica_helper.php` entre las líneas 78 y 103 (función `intercambiar_code_por_token`).
* **Qué debe mostrar la captura:**
```php
    $post_fields = http_build_query([
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri'  => $config['redirect_uri'],
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'state'         => $state,
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.claveunica.gob.cl/openid/token/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
```
* **Propósito:** Evidencia que la comunicación con el endpoint de tokens de ClaveÚnica se ejecuta estrictamente de servidor a servidor vía POST seguro (cURL), sin exponer nunca el `client_secret` en el navegador del usuario.

---

### Captura 4: Botón Oficial de ClaveÚnica en Pantalla de Login
* **Dónde tomarla:** En el navegador web ingresando a `https://opi.munilebu.gob.cl/login.php` (o en local).
* **Qué debe mostrar la captura:**
  * La pantalla de acceso limpia con el botón oficial y su texto superior:
    * Texto previo oficial: `Ingresa con tu ClaveÚnica` (respetando la mayúscula en C y U, y tilde en la Ú, tal como lo indica la pág. 6 de la guía oficial).
    * Isotipo vectorial de ClaveÚnica (24x24 px).
    * Texto del botón: `Iniciar sesión` (evitando redundancia conforme al estándar).
    * Tipografía oficial `Roboto Bold`.
    * Altura exacta de 48px y color institucional `#0F69C4`.
    * Sin sombras externas ni alteraciones visuales.

---

## ✉️ Plantilla de Respuesta Formal para Gobierno Digital

Copie y pegue el siguiente texto en el ticket o correo de respuesta a la Secretaría de Gobierno Digital, adjuntando las 4 imágenes:

```text
Estimado Equipo de Certificación ClaveÚnica
Secretaría de Gobierno Digital

Junto con saludarles, en respuesta a las observaciones remitidas para la certificación de la aplicación "SOPI Municipalidad de Lebu", informamos que se han subsanado satisfactoriamente los puntos indicados:

1. Botón ClaveÚnica (Lineamientos Oficiales):
Se actualizó la implementación del botón en la plataforma de acceso conforme a la Guía Oficial de Botón ClaveÚnica:
- Dimensiones normalizadas a 48px de altura con espaciados reglamentarios (padding 8px vertical, 14px horizontal, 4px entre isotipo y texto).
- Isotipo vectorial oficial de 24x24 px.
- Tipografía oficial Roboto (Bold 700) importada y renderizada.
- Colores estándar aplicados (#0F69C4 base, #0B4E91 hover, #07305A active, #FFBE5C foco accesible).
- Eliminación de sombras, redundancias de texto y clases ajenas.
(Se adjunta Evidencia_01_Boton_CU.png).

2. Evidencia de Credenciales Ocultas (Client_Id y Client_Secret):
Se confirma y adjunta la evidencia técnica que certifica el manejo seguro y aislado de las credenciales de producción:
- Evidencia_02_Variables_Servidor.png: Captura del panel de infraestructura/variables de entorno donde se configuran CLAVEUNICA_CLIENT_ID y CLAVEUNICA_CLIENT_SECRET.
- Evidencia_03_Lectura_Variables_Backend.png: Captura del código fuente (claveunica_helper.php) donde se obtienen las variables mediante getenv() de forma segura en el backend.
- Evidencia_04_Consumo_cURL_Token.png: Captura de la función backend donde se transmiten el Client_Id y Client_Secret exclusivamente en la llamada segura de servidor a servidor (cURL POST al endpoint /openid/token/).

Quedamos a su entera disposición para cualquier requerimiento adicional y solicitamos continuar con la etapa final de aprobación y paso a producción.

Atentamente,
Juan Carlos Arriagada Lincura
Departamento de Informática
Municipalidad de Lebu
```

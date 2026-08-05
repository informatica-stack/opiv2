<?php
session_start();

require_once 'config.php';

// Si el usuario ya está logueado, lo enviamos directo a trámites
if (isset($_SESSION['user_rut'])) {
    header("Location: tramites.php");
    exit();
}

// Configuración de Producción
$client_id     = CU_CLIENT_ID;
$redirect_uri  = "https://tramites.munilebu.gob.cl/login.php";

// Generación de tokens de seguridad (CSRF y Replay Attacks)
$state = bin2hex(random_bytes(16));
$nonce = bin2hex(random_bytes(16));

$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_nonce'] = $nonce;

// Construcción de la URL oficial de ClaveÚnica
$url_base = "https://accounts.claveunica.gob.cl/openid/authorize/";
$query_params = http_build_query([
    'client_id'     => $client_id,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'openid run name',
    'state'         => $state,
    'nonce'         => $nonce
]);

$url_login = $url_base . "?" . $query_params;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Trámites - Municipalidad de Lebu</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configuración de Tailwind para usar la paleta exacta de ClaveÚnica
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'gob-blue': '#004c97', 
                        'cu-blue': '#0f69c4',  
                        'gob-red': '#f13340',  
                        'gob-bg': '#f0f3f6'    
                    }
                }
            }
        }
    </script>
    <style>
        /* Botón estilo de base */
        .btn-cu {
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: "Roboto", sans-serif;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border-radius: 0;
            border: 0;
            transition: background-color 0.2s;
            cursor: pointer;
        }
        .btn-cu:hover {
            text-decoration: none;
        }
        /* Icono ClaveÚnica */
        .btn-cu .cl-claveunica-svg {
            width: 24px;
            height: 24px;
            margin: auto 4px auto 0px;
            flex-shrink: 0;
        }
        /* Texto ClaveÚnica */
        .btn-cu .texto {
            padding-left: 4px;
            text-decoration: none;
            font-size: 16px;
            line-height: 2rem;
            text-rendering: geometricPrecision;
        }
        /* Color Estándar */
        .btn-cu.btn-color-estandar {
            background-color: #0F69C4;
            color: #FFF;
        }
        .btn-cu.btn-color-estandar:hover {
            background-color: #0B4E91;
            color: #FFF;
        }
        .btn-cu.btn-color-estandar:active {
            background-color: #07305A;
            color: #FFF;
        }
        .btn-cu.btn-color-estandar:focus {
            background-color: #0B4E91;
            color: #FFF;
            outline: 4px solid #FFBE5C;
            outline-offset: 0;
        }
        /* Tamaño M */
        .btn-cu.btn-m {
            width: fit-content;
            min-height: 48px;
            padding: 8px 14px 8px 14px !important;
            font-size: 16px;
        }
        /* Bordes redondeados adicionales (según guía) */
        .btn-cu.rounded-none {
            border-radius: 0%;
        }
        .btn-cu.rounded-middle {
            border-radius: 4px 4px;
        }
        .btn-cu.rounded-full {
            border-radius: 99px 99px;
        }
        body { font-family: 'Roboto', sans-serif; color: #333; }
    </style>
</head>
<body class="bg-gob-bg antialiased min-h-screen flex flex-col">

    <header class="bg-white text-gob-blue shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-3 flex items-center gap-4">
            <img src="images/logo.png" alt="Escudo Lebu" class="h-12 w-auto">
            <div class="border-l border-gray-200 pl-4">
                <h1 class="text-base font-bold uppercase tracking-tight leading-tight">Ilustre Municipalidad de Lebu</h1>
                <p class="text-[11px] text-gray-500 uppercase font-medium">Portal de Trámites Digitales</p>
            </div>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-6 py-12 w-full">
        
        <div class="w-full max-w-3xl bg-white shadow-sm rounded-sm overflow-hidden border border-gray-200 border-t-4 border-t-gob-red">
            <div class="p-8 md:p-12 text-center">
                <h2 class="text-2xl font-bold text-gob-blue mb-4">Portal de Trámites Municipales</h2>
                <p class="text-base text-gray-600 mb-8 max-w-xl mx-auto">
                    Bienvenido. Para acceder a sus trámites y solicitudes, por favor identifíquese de forma segura utilizando su ClaveÚnica del Estado.
                </p>

                <div class="flex flex-col items-center justify-center py-10 bg-gray-50 border border-gray-200 rounded-sm">
                    <p class="text-gob-blue font-bold mb-5 text-sm uppercase tracking-wide">Ingresa con tu ClaveUnica</p>

                    <a class="btn-cu btn-m btn-color-estandar" href="<?php echo htmlspecialchars($url_login); ?>"
                        aria-label="Iniciar sesión con ClaveÚnica">
                        <svg class="cl-claveunica-svg" width="24" height="24" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12.4998 13.8956C12.9835 13.8956 13.3756 14.2878 13.3756 14.7715C13.3756 15.2552 12.9835 15.6473 12.4998 15.6473C12.0161 15.6473 11.6239 15.2552 11.6239 14.7715C11.6239 14.2878 12.0161 13.8956 12.4998 13.8956Z" fill="white"></path>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.631 1.70078C11.631 1.21768 12.0227 0.82605 12.5058 0.82605H15.9585C16.4416 0.82605 16.8333 1.21768 16.8333 1.70078C16.8333 2.18387 16.4416 2.5755 15.9585 2.5755H13.3805V9.35701C15.9909 9.77835 17.9845 12.0421 17.9845 14.7714C17.9845 17.8006 15.5289 20.2562 12.4998 20.2562C9.47065 20.2562 7.01505 17.8006 7.01505 14.7714C7.01505 12.0379 9.01473 9.77145 11.631 9.35509V1.70078ZM8.7645 14.7714C8.7645 12.7085 10.4368 11.0361 12.4998 11.0361C14.5627 11.0361 16.2351 12.7085 16.2351 14.7714C16.2351 16.8344 14.5627 18.5067 12.4998 18.5067C10.4368 18.5067 8.7645 16.8344 8.7645 14.7714Z" fill="white"></path>
                            <path d="M16.7507 5.65748C16.313 5.45302 15.7924 5.64209 15.5879 6.07979C15.3835 6.51748 15.5725 7.03806 16.0102 7.24252C18.8442 8.56635 20.8048 11.4409 20.8048 14.7716C20.8048 19.3583 17.0865 23.0766 12.4998 23.0766C7.91305 23.0766 4.19477 19.3583 4.19477 14.7716C4.19477 11.4517 6.14272 8.58499 8.96185 7.25542C9.39879 7.04935 9.58595 6.52809 9.37988 6.09115C9.17381 5.6542 8.65254 5.46705 8.2156 5.67312C4.80707 7.28066 2.44531 10.7494 2.44531 14.7716C2.44531 20.3245 6.94686 24.826 12.4998 24.826C18.0527 24.826 22.5543 20.3245 22.5543 14.7716C22.5543 10.7363 20.1771 7.25811 16.7507 5.65748Z" fill="white"></path>
                        </svg>
                        <span class="texto" aria-hidden="true">Iniciar sesión</span>
                    </a>
                    
                    <p class="mt-4 text-[11px] text-gray-500">Usted será redirigido al sitio seguro del Gobierno de Chile.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-auto text-center text-gray-600 text-xs shadow-inner">
        <div class="h-1 bg-gob-red w-full"></div> 
        <div class="max-w-7xl mx-auto px-6 py-8">
            <p class="font-bold mb-1 uppercase tracking-widest text-gob-blue">Departamento de Informática</p>
            <p>I. Municipalidad de Lebu - Región del Biobío, Chile</p>
            <p class="text-[10px] text-gray-400 mt-2">Plataforma de Trámites Digitales</p>
        </div>
    </footer>
</body>
</html>
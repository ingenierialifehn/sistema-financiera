/**
 * Helper para construir URLs dinámicas
 * Compatible con acceso móvil vía IP de red local
 */

// Función para obtener la URL base dinámicamente
function getBaseUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;

    // Extraer el path base del proyecto (hasta 'sistema-financiera')
    let basePath = pathname.substring(0, pathname.indexOf('/public'));
    if (!basePath) {
        // Fallback: buscar 'sistema-financiera' en el path
        const projectIndex = pathname.indexOf('sistema-financiera');
        if (projectIndex !== -1) {
            basePath = pathname.substring(0, projectIndex + 'sistema-financiera'.length);
        } else {
            basePath = '';
        }
    }

    return protocol + '//' + host + basePath;
}

// Sobrescribir BASE_URL si existe
if (typeof BASE_URL !== 'undefined') {
    console.log('BASE_URL original:', BASE_URL);
}

// Definir BASE_URL dinámicamente
const BASE_URL = getBaseUrl();
console.log('BASE_URL dinámico:', BASE_URL);

// Función helper para construir URLs de API
function apiUrl(endpoint) {
    // Asegurar que el endpoint comience con /
    if (!endpoint.startsWith('/')) {
        endpoint = '/' + endpoint;
    }
    return BASE_URL + endpoint;
}

// Función helper para construir URLs de assets
function assetUrl(path) {
    // Asegurar que el path comience con /
    if (!path.startsWith('/')) {
        path = '/' + path;
    }
    return BASE_URL + path;
}

// Exportar para uso global
window.getBaseUrl = getBaseUrl;
window.BASE_URL = BASE_URL;
window.apiUrl = apiUrl;
window.assetUrl = assetUrl;

console.log('URL Helpers cargados correctamente');
console.log('Ejemplo API URL:', apiUrl('/app/api/clientes/list.php'));

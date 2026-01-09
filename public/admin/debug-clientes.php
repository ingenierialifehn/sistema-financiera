<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Clientes</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #0f0;
        }

        .box {
            background: #000;
            border: 1px solid #0f0;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .error {
            color: #f00;
        }

        .success {
            color: #0f0;
        }

        .info {
            color: #ff0;
        }

        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <h1>🔍 Debug - Módulo Clientes</h1>

    <div class="box">
        <h3>1. Información Básica</h3>
        <div id="info"></div>
    </div>

    <div class="box">
        <h3>2. Test de APIs</h3>
        <button onclick="testAPI()">Probar API de Clientes</button>
        <button onclick="testAgencias()">Probar API de Agencias</button>
        <div id="apiResult"></div>
    </div>

    <div class="box">
        <h3>3. Consola de Errores</h3>
        <div id="console"></div>
    </div>

    <script>
        // Construir BASE_URL
        function getBaseUrl() {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;
            let basePath = pathname.substring(0, pathname.indexOf('/public'));
            if (!basePath) {
                const projectIndex = pathname.indexOf('sistema-financiera');
                if (projectIndex !== -1) {
                    basePath = pathname.substring(0, projectIndex + 'sistema-financiera'.length);
                }
            }
            return protocol + '//' + host + basePath;
        }

        const BASE_URL = getBaseUrl();

        // Mostrar info
        document.getElementById('info').innerHTML = `
            <div class="success">✓ URL Actual: ${window.location.href}</div>
            <div class="success">✓ BASE_URL: ${BASE_URL}</div>
            <div class="success">✓ Host: ${window.location.host}</div>
        `;

        // Capturar errores de consola
        window.onerror = function (msg, url, line, col, error) {
            const consoleDiv = document.getElementById('console');
            consoleDiv.innerHTML += `<div class="error">❌ ${msg} (Línea: ${line})</div>`;
        };

        // Test API de Clientes
        async function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<div class="info">⏳ Probando API de clientes...</div>';

            const url = BASE_URL + '/app/api/clientes/list.php';
            console.log('Llamando a:', url);

            try {
                const response = await fetch(url);
                const data = await response.json();

                console.log('Respuesta:', data);

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">✓ API Funciona Correctamente</div>
                        <div class="success">✓ Total de clientes: ${data.data.clientes.length}</div>
                        <div class="info">Datos recibidos:</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">❌ API respondió con error</div>
                        <div class="error">Mensaje: ${data.message || 'Sin mensaje'}</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">❌ Error de conexión</div>
                    <div class="error">Error: ${error.message}</div>
                    <div class="info">URL intentada: ${url}</div>
                    <div class="info">Posibles causas:</div>
                    <div>- El servidor no está corriendo</div>
                    <div>- La ruta de la API es incorrecta</div>
                    <div>- Problema de permisos/autenticación</div>
                    <div>- CORS bloqueado</div>
                `;
                console.error('Error completo:', error);
            }
        }

        // Test API de Agencias
        async function testAgencias() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<div class="info">⏳ Probando API de agencias...</div>';

            const url = BASE_URL + '/app/api/agencias/list.php';
            console.log('Llamando a:', url);

            try {
                const response = await fetch(url);
                const data = await response.json();

                console.log('Respuesta:', data);

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">✓ API de Agencias Funciona</div>
                        <div class="success">✓ Total de agencias: ${data.data.length}</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">❌ API de Agencias respondió con error</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">❌ Error en API de Agencias</div>
                    <div class="error">${error.message}</div>
                `;
                console.error('Error:', error);
            }
        }

        // Auto-test al cargar
        window.addEventListener('load', function () {
            console.log('Página cargada, ejecutando auto-test...');
            setTimeout(testAPI, 1000);
        });
    </script>
</body>

</html>
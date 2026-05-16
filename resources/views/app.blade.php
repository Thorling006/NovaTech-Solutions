<body class="font-sans antialiased">
    @inertia

    <div id="debug-js-error" style="position:fixed;bottom:10px;left:10px;right:10px;background:#111;color:#fff;padding:15px;z-index:9999;font-size:14px;">
        Laravel cargó. Si ves esto, el problema es Vue/Inertia.
    </div>

    <script>
        window.onerror = function(message, source, lineno, colno, error) {
            document.getElementById('debug-js-error').innerHTML =
                'ERROR JS: ' + message + '<br>Archivo: ' + source + '<br>Línea: ' + lineno;
        };
    </script>
</body>
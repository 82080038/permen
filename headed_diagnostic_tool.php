<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Headed Diagnostic Tool - SKD CAT-BKN</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .diagnostic-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; background: #007bff; color: white; }
        button:hover { background: #0056b3; }
        .log-output { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; }
        .console-error { color: #dc3545; font-weight: bold; }
        .console-warn { color: #ffc107; font-weight: bold; }
        .console-info { color: #17a2b8; }
        .network-request { margin: 5px 0; padding: 5px; background: #e9ecef; border-radius: 3px; }
        .network-success { border-left: 4px solid #28a745; }
        .network-error { border-left: 4px solid #dc3545; }
        .network-pending { border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <h1>🔍 Headed Diagnostic Tool - SKD CAT-BKN</h1>
    <p>Tool ini untuk mendiagnosis masalah yang hanya terlihat di browser (headed testing)</p>
    
    <div class="diagnostic-section">
        <h2>📋 Browser Information</h2>
        <div id="browser-info"></div>
    </div>

    <div class="diagnostic-section">
        <h2>🔴 Console Error Monitoring</h2>
        <button onclick="startConsoleMonitoring()">Start Console Monitoring</button>
        <button onclick="clearConsoleLog()">Clear Log</button>
        <div id="console-log" class="log-output"></div>
    </div>

    <div class="diagnostic-section">
        <h2>🌐 Network Request Monitoring</h2>
        <button onclick="startNetworkMonitoring()">Start Network Monitoring</button>
        <button onclick="clearNetworkLog()">Clear Log</button>
        <div id="network-log" class="log-output"></div>
    </div>

    <div class="diagnostic-section">
        <h2>🧪 API Testing</h2>
        <button onclick="testAPI('health')">Test Health API</button>
        <button onclick="testAPI('stats')">Test Stats API</button>
        <button onclick="testAPI('questions')">Test Questions API</button>
        <button onclick="testAPI('generate')">Test Generate API</button>
        <div id="api-results" class="log-output"></div>
    </div>

    <div class="diagnostic-section">
        <h2>🔧 Form Testing</h2>
        <button onclick="testLoginForm()">Test Login Form</button>
        <button onclick="testRegistrationForm()">Test Registration Form</button>
        <div id="form-results" class="log-output"></div>
    </div>

    <div class="diagnostic-section">
        <h2>📱 Responsive Testing</h2>
        <button onclick="testResponsive()">Test Responsive Design</button>
        <div id="responsive-results" class="log-output"></div>
    </div>

    <script>
        // Global variables
        let consoleLog = [];
        let networkLog = [];
        let originalConsole = {};
        let originalFetch = window.fetch;

        // Browser information
        function showBrowserInfo() {
            const info = {
                'User Agent': navigator.userAgent,
                'Language': navigator.language,
                'Platform': navigator.platform,
                'Cookie Enabled': navigator.cookieEnabled,
                'On-line': navigator.onLine,
                'Screen Width': screen.width,
                'Screen Height': screen.height,
                'Window Width': window.innerWidth,
                'Window Height': window.innerHeight,
                'Pixel Ratio': window.devicePixelRatio,
                'Touch Support': 'ontouchstart' in window,
                'Local Storage': typeof(Storage) !== "undefined",
                'Session Storage': typeof(Storage) !== "undefined",
                'WebSocket': 'WebSocket' in window,
                'Service Worker': 'serviceWorker' in navigator
            };

            let html = '<table style="width: 100%; border-collapse: collapse;">';
            for (const [key, value] of Object.entries(info)) {
                html += `<tr style="border-bottom: 1px solid #ddd;"><td style="padding: 5px; font-weight: bold;">${key}:</td><td style="padding: 5px;">${value}</td></tr>`;
            }
            html += '</table>';
            
            document.getElementById('browser-info').innerHTML = html;
        }

        // Console monitoring
        function startConsoleMonitoring() {
            // Override console methods
            originalConsole.error = console.error;
            originalConsole.warn = console.warn;
            originalConsole.log = console.log;
            originalConsole.info = console.info;

            console.error = function(...args) {
                addConsoleLog('error', args);
                originalConsole.error.apply(console, args);
            };

            console.warn = function(...args) {
                addConsoleLog('warn', args);
                originalConsole.warn.apply(console, args);
            };

            console.log = function(...args) {
                addConsoleLog('info', args);
                originalConsole.log.apply(console, args);
            };

            console.info = function(...args) {
                addConsoleLog('info', args);
                originalConsole.info.apply(console, args);
            };

            // Global error handlers
            window.addEventListener('error', function(event) {
                addConsoleLog('error', [`Global Error: ${event.message} at ${event.filename}:${event.lineno}`]);
            });

            window.addEventListener('unhandledrejection', function(event) {
                addConsoleLog('error', [`Unhandled Promise Rejection: ${event.reason}`]);
            });

            addConsoleLog('info', ['Console monitoring started']);
        }

        function addConsoleLog(type, args) {
            const timestamp = new Date().toLocaleTimeString();
            const message = args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
            ).join(' ');
            
            consoleLog.push({ timestamp, type, message });
            updateConsoleDisplay();
        }

        function updateConsoleDisplay() {
            const logDiv = document.getElementById('console-log');
            const html = consoleLog.slice(-50).map(log => {
                const className = log.type === 'error' ? 'console-error' : 
                                 log.type === 'warn' ? 'console-warn' : 'console-info';
                return `<div class="${className}">[${log.timestamp}] ${log.type.toUpperCase()}: ${log.message}</div>`;
            }).join('');
            logDiv.innerHTML = html;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function clearConsoleLog() {
            consoleLog = [];
            updateConsoleDisplay();
        }

        // Network monitoring
        function startNetworkMonitoring() {
            // Override fetch
            window.fetch = function(...args) {
                const url = args[0];
                const options = args[1] || {};
                const method = options.method || 'GET';
                const startTime = Date.now();

                addNetworkLog('pending', method, url, 'Request sent');

                return originalFetch.apply(this, args)
                    .then(response => {
                        const endTime = Date.now();
                        const duration = endTime - startTime;
                        const status = response.status;
                        const statusText = response.statusText;
                        
                        addNetworkLog(status >= 200 && status < 300 ? 'success' : 'error', 
                                     method, url, `${status} ${statusText} (${duration}ms)`);
                        
                        return response;
                    })
                    .catch(error => {
                        const endTime = Date.now();
                        const duration = endTime - startTime;
                        addNetworkLog('error', method, url, `Failed: ${error.message} (${duration}ms)`);
                        throw error;
                    });
            };

            addNetworkLog('info', 'SYSTEM', 'Network monitoring started', '');
        }

        function addNetworkLog(status, method, url, details) {
            const timestamp = new Date().toLocaleTimeString();
            networkLog.push({ timestamp, status, method, url, details });
            updateNetworkDisplay();
        }

        function updateNetworkDisplay() {
            const logDiv = document.getElementById('network-log');
            const html = networkLog.slice(-50).map(log => {
                const className = log.status === 'success' ? 'network-success' : 
                                 log.status === 'error' ? 'network-error' : 'network-pending';
                return `<div class="${className}">[${log.timestamp}] ${log.method} ${log.url} - ${log.details}</div>`;
            }).join('');
            logDiv.innerHTML = html;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function clearNetworkLog() {
            networkLog = [];
            updateNetworkDisplay();
        }

        // API Testing
        async function testAPI(apiType) {
            const resultsDiv = document.getElementById('api-results');
            const timestamp = new Date().toLocaleTimeString();
            
            try {
                let response;
                let url;
                
                switch(apiType) {
                    case 'health':
                        url = '/api/health.php';
                        break;
                    case 'stats':
                        url = '/api/get_landing_stats.php';
                        break;
                    case 'questions':
                        url = '/api/get_questions_final.php?subtes=TWK&limit=1';
                        break;
                    case 'generate':
                        url = '/api/generate_user_soal.php?subtes=TWK&jumlah=1';
                        break;
                }
                
                response = await fetch(url);
                const data = await response.json();
                
                if (response.ok) {
                    resultsDiv.innerHTML += `<div class="success">[${timestamp}] ${apiType.toUpperCase()} API: SUCCESS - ${JSON.stringify(data).substring(0, 200)}...</div>`;
                } else {
                    resultsDiv.innerHTML += `<div class="error">[${timestamp}] ${apiType.toUpperCase()} API: HTTP ${response.status} - ${JSON.stringify(data)}</div>`;
                }
            } catch (error) {
                resultsDiv.innerHTML += `<div class="error">[${timestamp}] ${apiType.toUpperCase()} API: FAILED - ${error.message}</div>`;
            }
            
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }

        // Form Testing
        async function testLoginForm() {
            const resultsDiv = document.getElementById('form-results');
            const timestamp = new Date().toLocaleTimeString();
            
            try {
                // Get login page
                const response = await fetch('/pages/login.php');
                const html = await response.text();
                
                if (html.includes('csrf_token')) {
                    resultsDiv.innerHTML += `<div class="success">[${timestamp}] Login Form: CSRF token found</div>`;
                } else {
                    resultsDiv.innerHTML += `<div class="error">[${timestamp}] Login Form: CSRF token missing</div>`;
                }
                
                if (html.includes('name="no_hp"') && html.includes('name="password"')) {
                    resultsDiv.innerHTML += `<div class="success">[${timestamp}] Login Form: Form fields present</div>`;
                } else {
                    resultsDiv.innerHTML += `<div class="error">[${timestamp}] Login Form: Form fields missing</div>`;
                }
                
            } catch (error) {
                resultsDiv.innerHTML += `<div class="error">[${timestamp}] Login Form: FAILED - ${error.message}</div>`;
            }
            
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }

        async function testRegistrationForm() {
            const resultsDiv = document.getElementById('form-results');
            const timestamp = new Date().toLocaleTimeString();
            
            try {
                const response = await fetch('/pages/register.php');
                const html = await response.text();
                
                if (html.includes('csrf_token')) {
                    resultsDiv.innerHTML += `<div class="success">[${timestamp}] Registration Form: CSRF token found</div>`;
                } else {
                    resultsDiv.innerHTML += `<div class="error">[${timestamp}] Registration Form: CSRF token missing</div>`;
                }
                
                if (html.includes('name="no_hp"') && html.includes('name="password"')) {
                    resultsDiv.innerHTML += `<div class="success">[${timestamp}] Registration Form: Form fields present</div>`;
                } else {
                    resultsDiv.innerHTML += `<div class="error">[${timestamp}] Registration Form: Form fields missing</div>`;
                }
                
            } catch (error) {
                resultsDiv.innerHTML += `<div class="error">[${timestamp}] Registration Form: FAILED - ${error.message}</div>`;
            }
            
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }

        // Responsive Testing
        function testResponsive() {
            const resultsDiv = document.getElementById('responsive-results');
            const timestamp = new Date().toLocaleTimeString();
            
            const widths = [320, 768, 1024, 1920];
            
            widths.forEach(width => {
                const isMobile = width <= 768;
                resultsDiv.innerHTML += `<div class="info">[${timestamp}] ${width}px: ${isMobile ? 'Mobile' : 'Desktop'} view</div>`;
            });
            
            // Check viewport meta tag
            const viewport = document.querySelector('meta[name="viewport"]');
            if (viewport) {
                resultsDiv.innerHTML += `<div class="success">[${timestamp}] Viewport meta tag: ${viewport.getAttribute('content')}</div>`;
            } else {
                resultsDiv.innerHTML += `<div class="error">[${timestamp}] Viewport meta tag: Missing</div>`;
            }
            
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }

        // Initialize on page load
        window.addEventListener('load', function() {
            showBrowserInfo();
            addConsoleLog('info', ['Diagnostic tool loaded']);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            // Restore original console and fetch
            if (originalConsole.error) console.error = originalConsole.error;
            if (originalConsole.warn) console.warn = originalConsole.warn;
            if (originalConsole.log) console.log = originalConsole.log;
            if (originalConsole.info) console.info = originalConsole.info;
            if (originalFetch) window.fetch = originalFetch;
        });
    </script>
</body>
</html>

#!/bin/bash
# Genera config.php en tiempo de arranque con las variables de entorno de Railway
cat > config.php <<EOF
<?php
define('AI_API_KEY',    '${AI_API_KEY}');
define('AI_MODEL',      '${AI_MODEL:-gemini-1.5-flash}');
define('AI_MAX_TOKENS', ${AI_MAX_TOKENS:-1024});
EOF

php -S 0.0.0.0:$PORT

<?php
// Copia este archivo como config.php y rellena tus valores.
// NUNCA subas config.php a un repositorio.

define('AI_API_KEY',    getenv('AI_API_KEY')    ?: 'tu-api-key-aqui');
define('AI_MODEL',      getenv('AI_MODEL')      ?: 'gemini-1.5-flash');
define('AI_MAX_TOKENS', (int)(getenv('AI_MAX_TOKENS') ?: 1024));

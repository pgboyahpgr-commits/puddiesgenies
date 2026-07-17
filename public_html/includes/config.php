<?php
define('GIST_URL', 'https://gist.githubusercontent.com/pgboyahpgr-commits/52f32ffc786ed004a6cbdb5ad04193cd/raw/menu.json');
define('GIST_API', 'https://api.github.com/gists/52f32ffc786ed004a6cbdb5ad04193cd');
$envToken = getenv('GH_TOKEN');
if (!$envToken) {
  $keyFile = __DIR__ . '/../data/.gh_token';
  $envToken = file_exists($keyFile) ? trim(@file_get_contents($keyFile)) : '';
}
define('GH_TOKEN', $envToken);

$orKey = getenv('OPENROUTER_KEY');
if (!$orKey) {
  $orFile = __DIR__ . '/../data/.openrouter_key';
  $orKey = file_exists($orFile) ? trim(@file_get_contents($orFile)) : '';
}
define('OPENROUTER_KEY', $orKey);

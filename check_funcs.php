<?php
$funcs = ['exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen'];
$results = [];
foreach ($funcs as $f) {
    $results[$f] = function_exists($f) && !in_array($f, array_map('trim', explode(',', ini_get('disable_functions'))));
}
header('Content-Type: application/json');
echo json_encode($results);

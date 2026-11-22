<?php
$path = '/usr/local/etc/edoc-db.php';
$config = include $path;
if (!is_array($config)) {
    fwrite(STDERR, "bad env file\n");
    exit(1);
}
$name = $argv[1] ?? '';
if ($name === '') {
    fwrite(STDERR, "missing db name\n");
    exit(1);
}
$config['DB_NAME'] = $name;
$out = "<?php\nreturn array(\n";
foreach ($config as $key => $value) {
    $out .= '    ' . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
}
$out .= ");\n";
file_put_contents($path, $out);
echo $config['DB_NAME'], "\n";

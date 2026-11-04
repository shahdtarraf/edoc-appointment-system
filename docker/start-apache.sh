#!/bin/bash
set -euo pipefail

PORT="${PORT:-80}"

sed -i "s/^Listen .*/Listen 0.0.0.0:${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true

# Apache workers often do not inherit container env vars. Pass them explicitly
# and also write a PHP array outside the web root for connection.php.
: > /etc/apache2/conf-available/edoc-passenv.conf
for env_name in DB_HOST DB_PORT DB_NAME DB_USER DB_PASSWORD DB_SSL DB_SSL_CA; do
    if [ -n "${!env_name:-}" ]; then
        printf 'PassEnv %s\n' "$env_name" >> /etc/apache2/conf-available/edoc-passenv.conf
    fi
done
a2enconf edoc-passenv >/dev/null

php -r '
$keys = array("DB_HOST", "DB_PORT", "DB_NAME", "DB_USER", "DB_PASSWORD", "DB_SSL", "DB_SSL_CA");
$out = "<?php\nreturn array(\n";
foreach ($keys as $key) {
    $value = getenv($key);
    if ($value === false) {
        $value = "";
    }
    $out .= "    " . var_export($key, true) . " => " . var_export($value, true) . ",\n";
}
$out .= ");\n";
file_put_contents("/usr/local/etc/edoc-db.php", $out);
chmod("/usr/local/etc/edoc-db.php", 0640);
'

if id www-data >/dev/null 2>&1; then
    chown root:www-data /usr/local/etc/edoc-db.php
fi

ca_path="/usr/local/etc/edoc-ca.pem"
if [ -n "${DB_SSL_CA:-}" ]; then
    printf '%s\n' "$DB_SSL_CA" > "$ca_path"
elif [ -n "${DB_HOST:-}" ] && [ -n "${DB_PORT:-}" ] && [ "${DB_HOST}" != "localhost" ]; then
    echo | openssl s_client -starttls mysql -connect "${DB_HOST}:${DB_PORT}" -showcerts 2>/dev/null \
        | awk 'BEGIN {c=0} /BEGIN CERTIFICATE/{c++} {if(c>0) print} /END CERTIFICATE/{print ""}' \
        > "${ca_path}.chain" || true
    if [ -s "${ca_path}.chain" ]; then
        mv "${ca_path}.chain" "$ca_path"
    else
        rm -f "${ca_path}.chain"
    fi
fi
if [ -f "$ca_path" ]; then
    chmod 0640 "$ca_path"
    if id www-data >/dev/null 2>&1; then
        chown root:www-data "$ca_path"
    fi
fi

exec apache2-foreground

#!/bin/bash
set -euo pipefail

PORT="${PORT:-80}"

sed -i "s/^Listen .*/Listen 0.0.0.0:${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true

exec apache2-foreground

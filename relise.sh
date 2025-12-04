#
zip -r prom-import.zip . -x ".*" \
 -x "composer.phar" \
 -x "phpstan.neon" \
 -x "relise.sh"
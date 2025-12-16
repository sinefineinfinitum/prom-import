#
zip -r spss12-import-prom-woo.zip . -x ".*" \
 -x "composer.phar" \
 -x "phpstan.neon" \
 -x "relise.sh" \
 -x "*/\.*"
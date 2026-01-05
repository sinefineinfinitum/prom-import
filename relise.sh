#
apt update
apt -y install zip
php composer.phar install --no-dev
zip -r spss12-import-prom-woo.zip . -x ".*" \
 -x "composer.phar" \
 -x "composer.lock" \
 -x "composer.json" \
 -x "phpstan.neon" \
 -x "phpunit.xml.dist" \
 -x "tests/*" \
 -x "relise.sh" \
 -x "*/\.*"
 ## php composer.phar install
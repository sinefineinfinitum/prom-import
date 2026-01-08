#
apt update
apt -y install zip
apt -y install wget
wget -qO- https://get.pnpm.io/install.sh | ENV="$HOME/.bashrc" SHELL="$(which bash)" bash -
source /root/.bashrc
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion
nvm install 24.12
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
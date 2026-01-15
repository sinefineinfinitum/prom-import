#bash
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
php composer.phar self-update
php composer.phar clear-cache
php composer.phar install --no-dev
find . -type d \( -path ./cache -o -path ./node_modules -o -path ./tests -o -path ./docs -o -path ./.pnpm-store -o -path ./.git  -o -path ./.github  \) -prune \
 -o -type f \( -name '*.php' -o -name '*.json' -o -name '*.js' -o -name '*.md' \
  -o -name 'LICENSE' -o -name '*.txt' \) -print \
| zip spss12-import-prom-woo.zip -@
 ## php composer.phar install
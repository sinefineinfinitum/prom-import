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
pnpm install
pnpm run dev
php composer.phar self-update
php composer.phar clear-cache
php composer.phar install --no-dev
zip -r spss12-import-prom-woo.zip . -x "cache/*" "node_modules/*" "tests/*" "docs/*" ".pnpm-store/*" ".git/*" ".github/*" "build/*" ".idea/*" ".gitignore" "composer.json" "composer.lock" "phpunit.xml.dist" "phpstan.neon" "rector.php" "phpcs.xml.dist" "psalm.xml" "infection.json5" "webpack.config.js" "package.json" "pnpm-lock.yaml" "pnpm-workspace.yaml" "*.zip" "*.phar" "*.sh"
 ## php composer.phar install
 # for mutation test need to install pcov
 # pecl install pcov
 ## run wp jobs
 # wp action-scheduler run --batches=1 --batch-size=50
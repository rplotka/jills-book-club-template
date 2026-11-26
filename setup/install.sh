#!/bin/bash
# install.sh - put Jill's Book Club on your Azure VM, next to your iit site.
#
#   sudo bash setup/install.sh git@github.com:YOU/jills-book-club.git
#
# Run it from the folder you unpacked (or cloned). It is safe to run again: it never reloads
# setup.sql over your data, and never overwrites your config file or deploy-bookclub.php.
#
# What it does, in order (each step says what it did, or that it was already done):
#   1. checks your VM has what Lab 9 and Lab 10 set up (Apache, MariaDB, PHP with mysqli)
#   2. copies the site to /var/www/html/bookclub, owned by www-data (the user Apache runs as)
#   3. creates the bookclub_jill database (setup.sql) and its own user, jill_app, with a random
#      password that goes only into includes/config.inc.php (in .gitignore: never committed)
#   4. puts the server on Eastern time, so Jill's meeting times are right
#   5. connects the folder to YOUR GitHub repo with a read-only deploy key just for it
#   6. writes deploy-bookclub.php (Lab 10's webhook, for this repo) and its secret file
#   7. tells you the two things to paste into GitHub (the deploy key, the webhook)
#
# Instructor testing only: SITE=name puts everything under a different name (folder, database,
# user, key, webhook) so it can't touch the real one.

set -u
SITE=${SITE:-bookclub}
TARGET=/var/www/html/$SITE
DB=$([ "$SITE" = bookclub ] && echo bookclub_jill || echo "${SITE}_jill")
DBUSER=$([ "$SITE" = bookclub ] && echo jill_app || echo "${SITE}_app")
KEY=/var/www/.ssh/id_ed25519_$SITE
ALIAS=github.com-$SITE
SECRET_FILE=/var/www/deploy-secret-$SITE
DEPLOY=/var/www/html/deploy-$SITE.php
SRC=$(cd "$(dirname "$0")/.." && pwd)
REPO=${1:-}
cd / || exit 1   # git runs as www-data, which can't enter your home folder

ok()   { printf '  \033[32mok\033[0m   %s\n' "$1"; }
note() { printf '  \033[33mnote\033[0m %s\n' "$1"; }
fail() { printf '  \033[31mFAIL\033[0m %s\n' "$1"; exit 1; }
step() { printf '\n\033[36m== %s\033[0m\n' "$1"; }

[ "$(id -u)" = 0 ] || fail "run it with sudo:  sudo bash setup/install.sh git@github.com:YOU/jills-book-club.git"
[ -f "$SRC/setup.sql" ] && [ -f "$SRC/books.php" ] || fail "run it from the unpacked jills-book-club folder (setup.sql and books.php must be there)"

step "1. What your VM needs (Labs 9 and 10)"
command -v apache2ctl >/dev/null || fail "Apache is not installed"
command -v mariadb >/dev/null || fail "MariaDB is not installed"
php -m 2>/dev/null | grep -qi '^mysqli$' || fail "PHP's mysqli is missing (sudo apt install php-mysql, then sudo systemctl restart apache2)"
systemctl is-active --quiet mariadb || fail "MariaDB is not running (sudo systemctl start mariadb)"
FQDN=$(grep -h '^ServerName' /etc/apache2/apache2.conf 2>/dev/null | tail -1 | awk '{print $2}')
[ -n "$FQDN" ] || FQDN=$(hostname -f)
ok "Apache, MariaDB, PHP with mysqli; your site is https://$FQDN/"

step "2. The site at $TARGET"
if [ -f "$TARGET/books.php" ]; then
  ok "already there (left as it is)"
else
  mkdir -p "$TARGET"
  (cd "$SRC" && tar --exclude=./.git --exclude=./includes/config.inc.php -cf - .) | (cd "$TARGET" && tar -xf -)
  chown -R www-data:www-data "$TARGET"
  ok "copied, owned by www-data"
fi

step "3. The database and its user"
if mariadb -N -e "SELECT 1 FROM information_schema.tables WHERE table_schema='$DB' AND table_name='books'" | grep -q 1; then
  ok "$DB already has its tables: your data is kept (setup.sql was NOT run again)"
else
  sed "s/bookclub_jill/$DB/g" "$TARGET/setup.sql" | mariadb || fail "setup.sql failed"
  ok "$DB created from setup.sql"
fi
CONF=$TARGET/includes/config.inc.php
if [ -f "$CONF" ]; then
  ok "includes/config.inc.php already exists (left as it is)"
else
  PW=$(openssl rand -hex 16)
  mariadb -e "CREATE USER IF NOT EXISTS '$DBUSER'@'localhost'; ALTER USER '$DBUSER'@'localhost' IDENTIFIED BY '$PW';
              GRANT SELECT, INSERT, UPDATE, DELETE ON \`$DB\`.* TO '$DBUSER'@'localhost';" || fail "could not create $DBUSER"
  sed -e "s/'jill_app'/'$DBUSER'/" -e "s/'change-me'/'$PW'/" -e "s/'bookclub_jill'/'$DB'/" \
      "$TARGET/includes/config.example.inc.php" > "$CONF"
  unset PW
  chown www-data:www-data "$CONF"; chmod 640 "$CONF"
  ok "user $DBUSER with a random password, saved only in includes/config.inc.php"
fi

step "4. Eastern time"
if [ "$(timedatectl show -p Timezone --value 2>/dev/null)" = America/New_York ]; then
  ok "already on America/New_York"
else
  timedatectl set-timezone America/New_York && systemctl restart mariadb && ok "set to America/New_York (MariaDB restarted)"
fi

HTTP=$(curl -s -o /dev/null -w '%{http_code}' "https://$FQDN/$SITE/books.php")
[ "$HTTP" = 200 ] && ok "https://$FQDN/$SITE/ is up" || note "https://$FQDN/$SITE/books.php answered $HTTP (run setup/check.sh)"

step "5. Connect to your GitHub repo"
if [ -z "$REPO" ]; then
  note "no repo given: the site runs, but merges won't deploy. Run again with your repo:"
  note "  sudo bash setup/install.sh git@github.com:YOU/jills-book-club.git"
  exit 0
fi
OWNER_REPO=$(printf '%s' "$REPO" | sed -E 's#^(git@github\.com:|https://github\.com/)##; s#\.git$##; s#/$##')
printf '%s' "$OWNER_REPO" | grep -Eq '^[A-Za-z0-9-]+/[A-Za-z0-9._-]+$' || fail "that doesn't look like a GitHub repo: $REPO"
URL=git@$ALIAS:$OWNER_REPO.git
install -d -o www-data -g www-data -m 700 /var/www/.ssh
if [ ! -f "$KEY" ]; then
  sudo -u www-data ssh-keygen -q -t ed25519 -N "" -C "$SITE@$FQDN" -f "$KEY"
  ok "made a key just for this repo: $KEY"
fi
if ! grep -q "^Host $ALIAS\$" /var/www/.ssh/config 2>/dev/null; then
  printf 'Host %s\n  HostName github.com\n  User git\n  IdentityFile %s\n  IdentitiesOnly yes\n  StrictHostKeyChecking accept-new\n' \
    "$ALIAS" "$KEY" | sudo -u www-data tee -a /var/www/.ssh/config >/dev/null
  chmod 600 /var/www/.ssh/config
fi
# GitHub can take a few seconds to accept a key that was just added, so each check retries for ~15 s
can_read() { for w in 1 2 3 4 5; do sudo -u www-data git ls-remote "$URL" >/dev/null 2>&1 && return 0; sleep 3; done; return 1; }
for try in 1 2 3; do
  can_read && break
  echo
  echo "  GitHub doesn't know this server yet. Add its key to YOUR repo (read-only):"
  echo "    https://github.com/$OWNER_REPO/settings/keys/new"
  echo "    Title: $SITE on $FQDN     Key: the line below     Leave 'Allow write access' OFF"
  echo
  echo "    $(cat "$KEY.pub")"
  echo
  read -r -p "  Press Enter after you click 'Add key' (try $try of 3) " _
done
can_read || fail "the server still can't read $OWNER_REPO (check the deploy key, and that the repo name is right)"
ok "the server can read $OWNER_REPO"
cd "$TARGET" || exit 1
if [ ! -d .git ]; then
  sudo -u www-data git init -q -b main
  sudo -u www-data git remote add origin "$URL"
fi
sudo -u www-data git remote set-url origin "$URL"
sudo -u www-data git fetch -q origin main || fail "git fetch failed"
sudo -u www-data git reset -q --hard origin/main
sudo -u www-data git branch -q --set-upstream-to=origin/main main
ok "$TARGET now matches your repo ($(sudo -u www-data git log --oneline -1))"

step "6. The webhook script"
if [ ! -f "$SECRET_FILE" ]; then
  openssl rand -hex 32 > "$SECRET_FILE"; chown www-data:www-data "$SECRET_FILE"; chmod 600 "$SECRET_FILE"
  ok "secret made: $SECRET_FILE"
fi
touch /var/log/deploy.log; chown www-data:www-data /var/log/deploy.log
if [ -f "$DEPLOY" ]; then
  ok "$DEPLOY already exists (left as it is)"
else
  cat > "$DEPLOY" <<EOF
<?php
// GitHub Webhook Auto-Deploy for $SITE (Lab 10's deploy.php, for a second repo; written by setup/install.sh)
\$repoPath = '$TARGET';
\$logFile  = '/var/log/deploy.log';
// The shared secret lives outside the website and outside git
\$secret = trim((string) @file_get_contents('$SECRET_FILE'));
if (\$secret === '') {
    http_response_code(500);
    file_put_contents(\$logFile, date('Y-m-d H:i:s') . " [$SITE] ERROR: no secret in $SECRET_FILE\n", FILE_APPEND);
    die('Server not configured');
}
// Verify GitHub's HMAC-SHA256 signature
\$signature = \$_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
\$payload   = file_get_contents('php://input');
\$expected  = 'sha256=' . hash_hmac('sha256', \$payload, \$secret);
if (!hash_equals(\$expected, \$signature)) {
    http_response_code(403);
    file_put_contents(\$logFile, date('Y-m-d H:i:s') . " [$SITE] REJECTED: invalid signature\n", FILE_APPEND);
    die('Invalid signature');
}
// Only deploy pushes to main
\$data   = json_decode(\$payload, true);
\$branch = \$data['ref'] ?? '';
if (\$branch !== 'refs/heads/main') {
    file_put_contents(\$logFile, date('Y-m-d H:i:s') . " [$SITE] SKIPPED: push to \$branch\n", FILE_APPEND);
    die('Not main branch');
}
// Deploy: Apache runs as www-data, which owns the clone and has its deploy key
\$output = [];
\$code   = 0;
exec('cd ' . escapeshellarg(\$repoPath) . ' && git pull origin main 2>&1', \$output, \$code);
\$result = implode("\n", \$output);
\$status = \$code === 0 ? 'SUCCESS' : 'FAILED';
file_put_contents(\$logFile, date('Y-m-d H:i:s') . " [$SITE] \$status: \$result\n", FILE_APPEND);
http_response_code(\$code === 0 ? 200 : 500);
echo \$status;
EOF
  chown www-data:www-data "$DEPLOY"; chmod 640 "$DEPLOY"
  ok "wrote $DEPLOY"
fi

step "7. Last step, on GitHub: the webhook"
echo "  https://github.com/$OWNER_REPO/settings/hooks/new"
echo "    Payload URL:   https://$FQDN/deploy-$SITE.php"
echo "    Content type:  application/json"
echo "    Secret:        the output of   sudo cat $SECRET_FILE"
echo "    Events:        Just the push event"
echo
echo "  Then check everything:   sudo bash setup/check.sh"

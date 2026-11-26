#!/bin/bash
# check.sh - is Jill's Book Club set up right on this VM? Changes nothing; run it any time.
#
#   sudo bash setup/check.sh
#
# Every line is PASS or FAIL; each FAIL says what to do. Run it before asking for help.

set -u
SITE=${SITE:-bookclub}
TARGET=/var/www/html/$SITE
DB=$([ "$SITE" = bookclub ] && echo bookclub_jill || echo "${SITE}_jill")
SECRET_FILE=/var/www/deploy-secret-$SITE
DEPLOY=/var/www/html/deploy-$SITE.php
FQDN=$(grep -h '^ServerName' /etc/apache2/apache2.conf 2>/dev/null | tail -1 | awk '{print $2}')
[ -n "$FQDN" ] || FQDN=$(hostname -f)
BASE=https://$FQDN/$SITE
FAILS=0
cd / || exit 1   # git runs as www-data, which can't enter your home folder

pass() { printf '  \033[32mPASS\033[0m %s\n' "$1"; }
bad()  { printf '  \033[31mFAIL\033[0m %s\n         -> %s\n' "$1" "$2"; FAILS=$((FAILS + 1)); }
check() { if eval "$2" >/dev/null 2>&1; then pass "$1"; else bad "$1" "$3"; fi; }

[ "$(id -u)" = 0 ] || { echo "Run it with sudo:  sudo bash setup/check.sh"; exit 1; }
echo "Jill's Book Club on $FQDN"

check "the site's files are in $TARGET" "[ -f $TARGET/books.php ]" \
  "run setup/install.sh from the unpacked folder"
check "they belong to www-data" "[ \"\$(stat -c %U $TARGET/books.php)\" = www-data ]" \
  "sudo chown -R www-data:www-data $TARGET"
check "includes/config.inc.php exists" "[ -f $TARGET/includes/config.inc.php ]" \
  "run setup/install.sh again: it makes the file and its database user"
check "the database $DB has its tables" "mariadb -N -e \"SELECT 1 FROM information_schema.tables WHERE table_schema='$DB' AND table_name='books'\" | grep -q 1" \
  "run setup/install.sh again"
check "the home page loads ($BASE/)" "[ \"\$(curl -s -o /dev/null -w '%{http_code}' $BASE/)\" = 200 ]" \
  "sudo tail /var/log/apache2/error.log shows why"
check "books.php lists books" "curl -s $BASE/books.php | grep -q 'reviews.php?book='" \
  "a database error? check includes/config.inc.php matches the database user"
check ".git is hidden from the web" "[ \"\$(curl -s -o /dev/null -w '%{http_code}' $BASE/.git/HEAD)\" = 404 ]" \
  "Lab 10's SSL tutorial, Step 1, commands 2 and 3 (RedirectMatch 404 /\\.git)"
check "config.inc.php is not in git" "! sudo -u www-data git -C $TARGET ls-files --error-unmatch includes/config.inc.php" \
  "it must never be committed: git rm --cached includes/config.inc.php, commit, push"
check "the server is on Eastern time" "[ \"\$(timedatectl show -p Timezone --value)\" = America/New_York ]" \
  "sudo timedatectl set-timezone America/New_York && sudo systemctl restart mariadb"
check "$TARGET is a git clone of your repo" "sudo -u www-data git -C $TARGET remote get-url origin" \
  "run setup/install.sh again with your repo: sudo bash setup/install.sh git@github.com:YOU/jills-book-club.git"
check "the server can read your repo (deploy key)" "sudo -u www-data git -C $TARGET ls-remote origin" \
  "add the key from setup/install.sh as a read-only Deploy key on your repo"
check "no edits made on the server (they block deploys)" "[ -z \"\$(sudo -u www-data git -C $TARGET status --porcelain --untracked-files=no)\" ]" \
  "sudo -u www-data git -C $TARGET checkout -- .   (Part D4 is this exact problem)"
check "the webhook script exists" "[ -f $DEPLOY ]" \
  "run setup/install.sh again with your repo"
check "its secret file exists and only www-data can read it" "[ -f $SECRET_FILE ] && [ \"\$(stat -c '%U %a' $SECRET_FILE)\" = 'www-data 600' ]" \
  "run setup/install.sh again"
check "the webhook script answers (403 to an unsigned request)" "[ \"\$(curl -s -o /dev/null -w '%{http_code}' -X POST https://$FQDN/deploy-$SITE.php)\" = 403 ]" \
  "a 500 means no secret file; a 404 means no deploy-$SITE.php"
if grep -q "\[$SITE\] SUCCESS" /var/log/deploy.log 2>/dev/null; then
  pass "GitHub has deployed at least once (deploy.log)"
else
  bad "no deploy from GitHub yet" "add the webhook (setup/install.sh, step 7), then merge a pull request (a ping alone does not deploy)"
fi

echo
[ $FAILS = 0 ] && echo "All good." || echo "$FAILS to fix. Fix the first one, then run this again."
exit $FAILS

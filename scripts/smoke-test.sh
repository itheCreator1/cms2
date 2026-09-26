#!/usr/bin/env bash
# End-to-end checks against a running stack (used by CI, safe locally):
#   docker compose up -d && scripts/setup.sh && scripts/seed-users.sh && scripts/smoke-test.sh
source "$(dirname "$0")/lib.sh"

failures=0
check() { # label, expected, actual
  if [[ "$2" == "$3" ]]; then
    echo "  ok    $1"
  else
    echo "  FAIL  $1 (expected '$2', got '$3')"
    failures=$((failures + 1))
  fi
}
status() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
login() { # username, password -> response body
  curl -s -b 'wordpress_test_cookie=WP%20Cookie%20check' \
    --data-urlencode "log=$1" --data-urlencode "pwd=$2" "$WP_URL/wp-login.php"
}

echo "WordPress checks:"
EARES_PASSWORD="$SEED_PASSWORD" wp eval-file - < scripts/smoke-test.php || failures=$((failures + 1))

echo "HTTP checks:"
check "XML-RPC is refused" 403 "$(status -X POST "$WP_URL/xmlrpc.php")"
check "Anonymous REST /users is gone" 404 "$(status "$WP_URL/wp-json/wp/v2/users")"
check "?author=1 redirects home (302)" "302 $WP_URL/" \
  "$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$WP_URL/?author=1")"

echo "Login limit:"
wp transient delete --all >/dev/null
for _ in 1 2 3 4 5; do login test-editor wrong-password >/dev/null; done
if login test-editor "$SEED_PASSWORD" | grep -q 'Πάρα πολλές αποτυχημένες'; then
  check "Right password is refused after 5 failures" locked locked
else
  check "Right password is refused after 5 failures" locked "not locked"
fi
wp transient delete --all >/dev/null

if (( failures )); then
  echo "$failures check group(s) failed." >&2
  exit 1
fi
echo "All checks passed."

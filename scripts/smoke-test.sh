#!/usr/bin/env bash
# End-to-end checks against a running stack (used by CI, safe locally).
# Needs seed-users.sh and seed-content.sh data:
#   docker compose up -d && scripts/setup.sh && scripts/seed-users.sh && scripts/seed-content.sh && scripts/smoke-test.sh
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
jar="$(mktemp)"
trap 'rm -f "$jar"' EXIT
login_jar() { # username, password -> redirect URL; session cookies in $jar
  curl -s -o /dev/null -w '%{redirect_url}' -c "$jar" -b 'wordpress_test_cookie=WP%20Cookie%20check' \
    --data-urlencode "log=$1" --data-urlencode "pwd=$2" "$WP_URL/wp-login.php"
}
register() { # extra curl args -> HTTP status of the sign-up form
  curl -s -o /dev/null -w '%{http_code}' "$WP_URL/wp-login.php?action=register" "$@"
}
role_of() { # login -> role, or "none"
  wp eval "\$u = get_user_by( 'login', '$1' ); echo \$u ? implode( ',', \$u->roles ) : 'none';"
}

echo "WordPress checks:"
EARES_PASSWORD="$SEED_PASSWORD" wp eval-file - < scripts/smoke-test.php || failures=$((failures + 1))

echo "HTTP checks:"
check "XML-RPC is refused" 403 "$(status -X POST "$WP_URL/xmlrpc.php")"
check "Anonymous REST /users is gone" 404 "$(status "$WP_URL/wp-json/wp/v2/users")"
check "?author=1 redirects home (302)" "302 $WP_URL/" \
  "$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$WP_URL/?author=1")"

echo "Theme:"
front="$(curl -s "$WP_URL/")"
check "Front page uses the ΕΑΡΕΣ theme" yes \
  "$(grep -q 'eares-theme-css' <<<"$front" && grep -q 'eares-hero' <<<"$front" && echo yes || echo no)"
check "Front page shows the sticky notice" yes \
  "$(grep -q 'Έκτακτες εκλογές' <<<"$front" && echo yes || echo no)"
check "News page /nea/ answers" 200 "$(status "$WP_URL/nea/")"
check "Ριζαρείτης archive answers" 200 "$(status "$WP_URL/category/rizareitis/")"
check "No third-party requests (emoji CDN)" no \
  "$(grep -q 's.w.org' <<<"$front" && echo yes || echo no)"

echo "Members' area:"
wp transient delete --all >/dev/null
check "Anonymous: members-only post is hidden" 404 "$(status "$WP_URL/praktika-ds-2026-09/")"
# shellcheck disable=SC2016 # PHP code, not shell.
pdf_id="$(wp eval '$p = get_page_by_path( "praktika-ds-2026-09", OBJECT, "post" ); $c = $p ? get_children( array( "post_parent" => $p->ID, "post_type" => "attachment", "fields" => "ids" ) ) : array(); echo (int) reset( $c );')"
# shellcheck disable=SC2016
raw_pdf="$(wp eval '$u = wp_get_upload_dir(); echo $u["baseurl"] . "/" . get_post_meta( '"$pdf_id"', "_wp_attached_file", true );')"
check "Anonymous: members-only file sends to login" 302 "$(status "$WP_URL/?eares_file=$pdf_id")"
check "Anonymous: direct uploads URL is refused" 403 "$(status "$raw_pdf")"
check "Member login lands on the home page" "$WP_URL/" "$(login_jar test-member "$SEED_PASSWORD")"
check "Member: members-only post is readable" 200 "$(status -b "$jar" "$WP_URL/praktika-ds-2026-09/")"
check "Member: members-only file is served" "200 application/pdf" \
  "$(curl -s -o /dev/null -w '%{http_code} %{content_type}' -b "$jar" "$WP_URL/?eares_file=$pdf_id")"
check "Member: wp-admin sends to the profile" "$WP_URL/wp-admin/profile.php" \
  "$(curl -s -o /dev/null -w '%{redirect_url}' -b "$jar" "$WP_URL/wp-admin/")"
check "Member: REST /users is gone" 404 "$(status -b "$jar" "$WP_URL/wp-json/wp/v2/users")"

echo "Registration:"
wp transient delete --all >/dev/null
signup=(--data-urlencode first_name=Δοκιμή --data-urlencode last_name=Εγγραφής --data-urlencode eares_grad_year=1998)
register "${signup[@]}" --data-urlencode user_login=smoke-signup --data-urlencode user_email=smoke-signup@eares.local \
  --data-urlencode role=administrator >/dev/null
check "Sign-up creates a Member, even when asking for administrator" eares_member "$(role_of smoke-signup)"
register "${signup[@]}" --data-urlencode user_login=smoke-bot --data-urlencode user_email=smoke-bot@eares.local \
  --data-urlencode eares_website=http://spam.example >/dev/null
check "Honeypot rejects a bot" none "$(role_of smoke-bot)"
register --data-urlencode user_login=smoke-noyear --data-urlencode user_email=smoke-noyear@eares.local \
  --data-urlencode first_name=Α --data-urlencode last_name=Β >/dev/null
check "Graduation year is required" none "$(role_of smoke-noyear)"
for n in 2 3; do
  register "${signup[@]}" --data-urlencode "user_login=smoke-signup$n" --data-urlencode "user_email=smoke-signup$n@eares.local" >/dev/null
done
register "${signup[@]}" --data-urlencode user_login=smoke-signup4 --data-urlencode user_email=smoke-signup4@eares.local >/dev/null
check "Fourth sign-up from one IP within the hour is refused" none "$(role_of smoke-signup4)"
# shellcheck disable=SC2016
wp eval 'require_once ABSPATH . "wp-admin/includes/user.php"; foreach ( get_users( array( "search" => "smoke-*", "search_columns" => array( "user_login" ) ) ) as $u ) { wp_delete_user( $u->ID ); }'
wp transient delete --all >/dev/null

echo "Login limit:"
wp transient delete --all >/dev/null
for _ in 1 2 3 4 5; do login test-member wrong-password >/dev/null; done
# Capture first: with pipefail, `curl | grep -q` fails when grep exits early
# and curl dies of SIGPIPE on a large page, even though grep matched.
body="$(login test-member "$SEED_PASSWORD")"
if grep -q 'Πάρα πολλές αποτυχημένες' <<<"$body"; then
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

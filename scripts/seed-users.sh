#!/usr/bin/env bash
# Create one FAKE test user per role (local development only).
# All share the password SEED_PASSWORD from .env. Safe to re-run.
source "$(dirname "$0")/lib.sh"

if [[ "$(wp eval 'echo wp_get_environment_type();')" == "production" ]]; then
  echo "Refusing to seed test users in production." >&2
  exit 1
fi

# login | email | role | display name | phone
users=(
  "test-usermanager|usermanager@eares.local|eares_user_manager|Γραμματεία (δοκιμή)|210 000 0001"
  "test-editor|editor@eares.local|editor|Μέλος ΔΣ (δοκιμή)|210 000 0002"
  "test-author|author@eares.local|author|Απόφοιτος Author (δοκιμή)|210 000 0003"
  "test-contributor|contributor@eares.local|contributor|Απόφοιτος Contributor (δοκιμή)|210 000 0004"
  "test-inactive|inactive@eares.local|eares_inactive|Πρώην μέλος ΔΣ (δοκιμή)|210 000 0005"
)

for row in "${users[@]}"; do
  IFS='|' read -r login email role name phone <<<"$row"
  if id=$(wp user get "$login" --field=ID 2>/dev/null); then
    wp user update "$id" --role="$role" --display_name="$name" --user_pass="$SEED_PASSWORD" --skip-email >/dev/null
  else
    id=$(wp user create "$login" "$email" --role="$role" --display_name="$name" \
          --user_pass="$SEED_PASSWORD" --porcelain)
  fi
  wp user meta update "$id" eares_phone "$phone" >/dev/null
  echo "  $login ($role) id=$id"
done

# A post by the soon-to-be inactive user, to check it stays published.
if [[ -z "$(wp post list --author="$(wp user get test-inactive --field=ID)" --format=ids)" ]]; then
  wp post create --post_author="$(wp user get test-inactive --field=ID)" --post_status=publish \
    --post_title="Δοκιμαστική ανακοίνωση πρώην μέλους ΔΣ" \
    --post_content="Αυτό το άρθρο πρέπει να παραμένει δημοσιευμένο ενώ ο λογαριασμός είναι ανενεργός." >/dev/null
fi

echo "Password for all test users: $SEED_PASSWORD"

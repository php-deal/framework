#!/bin/bash
# Installs the Composer dependencies at the start of a Claude Code cloud session.
#
# In cloud sessions, anonymous requests to api.github.com hit the rate limit (HTTP 403). Composer then falls back
# to slow full git clones for every package, and phpstan/phpstan (distributed without a git source) cannot be
# installed at all. Git fetches through the session proxy are not limited, so this script builds the dist zips with
# `git fetch --depth=1` + `git archive` and puts them into Composer's cache before `composer install` runs.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-.}"

# The sandbox runs as root: Composer skips plugins (phpstan/extension-installer) without this variable
if [ "$(id -u)" = "0" ]; then
  export COMPOSER_ALLOW_SUPERUSER=1
  if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
    echo 'export COMPOSER_ALLOW_SUPERUSER=1' >> "$CLAUDE_ENV_FILE"
  fi
fi
export COMPOSER_PROCESS_TIMEOUT=1800

# Writes the dist zip of one locked package into Composer's cache, as Composer would after downloading it
seed_package() {
  local cache_file=$1 repo=$2 ref=$3 work
  if [ -s "$cache_file" ]; then
    return 0
  fi
  work=$(mktemp -d)
  if git -C "$work" init -q \
    && git -C "$work" fetch -q --depth=1 "https://github.com/$repo.git" "$ref" \
    && mkdir -p "$(dirname "$cache_file")" \
    && git -C "$work" archive --format=zip --prefix=package/ -o "$cache_file.tmp" FETCH_HEAD; then
    mv "$cache_file.tmp" "$cache_file"
  else
    # Composer downloads this package itself
    rm -f "$cache_file.tmp"
    echo "session-start: could not prefetch $repo@$ref" >&2
  fi
  rm -rf "$work"
}
export -f seed_package

# Run the install only if this script or composer.json changed since the install last succeeded
hashes=vendor/.session-start.sha256
if ! sha256sum --check --status "$hashes" 2>/dev/null; then
  # Delete the old hashes first, so a failed install runs again next time
  rm -f "$hashes"

  # composer.lock is not committed: resolve it from the Packagist metadata, which is not rate limited
  composer update --no-install --no-interaction --no-progress

  cache_dir=$(composer config cache-files-dir)
  php -r '
    $lock = json_decode(file_get_contents("composer.lock"), true, 512, JSON_THROW_ON_ERROR);
    foreach ([...$lock["packages"], ...$lock["packages-dev"]] as $package) {
        $url = $package["dist"]["url"] ?? "";
        if (preg_match("~^https://api\.github\.com/repos/([^/]+/[^/]+)/zipball/([0-9a-f]{40})$~", $url, $m) === 1) {
            // Cache file name used by Composer: <package>/sha1(<dist url>).zip
            echo $argv[1], "/", $package["name"], "/", sha1($url), ".zip ", $m[1], " ", $m[2], "\n";
        }
    }
  ' "$cache_dir" | xargs -P 8 -L 1 bash -c 'seed_package "$@"' _

  composer install --no-interaction --no-progress

  sha256sum .claude/hooks/session-start.sh composer.json > "$hashes"
fi

#!/bin/sh
# The site's CSS and JS: esbuild (pinned) bundles assets/site.css and assets/site.js, with everything
# they import, into one file each, minified, named by its contents: site.<hash>.css and
# site.<hash>.js. A release that changes them gets new names, one that does not keeps the old ones,
# so .htaccess lets browsers keep them a year. The program links them by the names it was built
# with: this script's last line is the options for that build, `--config siteCss=... --config siteJs=...`.
#
#   sh assets/build.sh [OUT]          into OUT (default build/assets), names with the hash
#   sh assets/build.sh --plain [OUT]  site.css and site.js, the names a build without the options
#                                     links (build.kpp): for a local run, which serves them from
#                                     build/assets (Main.kpp, serveStatic)
#
# Run in the repository's root. Needs Node.js (npx), which fetches the pinned esbuild from npm.
set -e
esbuild=esbuild@0.28.2
names='[name].[hash]'
if [ "$1" = --plain ]; then names='[name]'; shift; fi
out=${1:-build/assets}
rm -rf "$out"
mkdir -p "$out"
npx --yes "$esbuild" assets/site.css assets/site.js --bundle --minify --format=iife --target=es2017 \
  --entry-names="$names" --outdir="$out" --log-level=warning
css=$(cd "$out" && ls site*.css)
js=$(cd "$out" && ls site*.js)
[ -s "$out/$css" ] && [ -s "$out/$js" ] || { echo "assets/build.sh: an empty bundle in $out" >&2; exit 1; }
echo "--config siteCss=$css --config siteJs=$js"

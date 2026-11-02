#!/bin/bash
# Compares what the EGD reader reads from saved EGD pages (testdata/egdextract) with what PHP's
# DOMDocument reads (testdata/egd_extract.php): egd-compare.sh page.html... (with php on PATH, or PHP
# naming it, and the kpp build in build/debug).
here=$(dirname "$0")
php=${PHP:-php}
same=0; differ=0
for page in "$@"; do
  if cmp -s <("$php" "$here/egd_extract.php" "$page" | tr -d '\r') <("$here/../build/debug/egdextract" "$page" | tr -d '\r'); then
    same=$((same+1))
  else
    differ=$((differ+1)); echo "DIFF $page"
    diff <("$php" "$here/egd_extract.php" "$page" | tr -d '\r') <("$here/../build/debug/egdextract" "$page" | tr -d '\r') | head -6
  fi
done
echo "$same pages read the same, $differ differ"

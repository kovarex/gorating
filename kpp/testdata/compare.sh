#!/bin/bash
# Fetches each path (one per line on stdin) from PHP (8081) and kpp (8082) and compares the HTML
# after masking the differences made on purpose: escaping, absolute links, quoted attributes, an
# empty value attribute, the generation time.
norm() {
  sed -e 's/Page generated in [0-9.]* seconds//' \
      -e 's/href="\//href="/g' \
      -e 's/action="\/login"/action="login"/' \
      -e 's/&amp;/\&/g' -e "s/&#39;/'/g" -e 's/&quot;/"/g' -e 's/&lt;/</g' -e 's/&gt;/>/g' \
      -e 's/colspan=\([0-9]\)/colspan="\1"/g' \
      -e 's/action="\//action="/g' \
      -e 's/text-align: right;/text-align:right;/g' \
      -e 's#</br>#<br/>#g' \
      -e 's# value=""/>#/>#g' \
      -e 's/"value=/" value=/g' | sed -e 's#</tr>#</tr>\n#g' |
    perl -0pe 's#<script>\n.*?</script>\n##sg'
}
same=0; ties=0; differ=0
while read -r path; do
  curl -s "http://127.0.0.1:8081$path" | norm > /tmp/php.html
  curl -s "http://127.0.0.1:8082$path" | norm > /tmp/kpp.html
  if cmp -s /tmp/php.html /tmp/kpp.html; then
    same=$((same+1))
  elif cmp -s <(sort /tmp/php.html) <(sort /tmp/kpp.html); then
    # The same rows: PHP's query left rows with equal sort values in no particular order.
    ties=$((ties+1))
  else
    differ=$((differ+1)); echo "DIFF $path"; diff /tmp/php.html /tmp/kpp.html | cut -c1-${WIDTH:-300} | head -${LINES_PER_DIFF:-6}
  fi
done
echo "$same same, $ties same rows with ties in another order, $differ differ"

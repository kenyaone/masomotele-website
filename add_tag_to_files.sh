#!/bin/bash

GTAG='<!-- Google tag (gtag.js) - Google Ads Conversion Tracking -->\n<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17161883901"><\/script>\n<script>\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag("js", new Date());\n  gtag("config", "AW-17161883901");\n<\/script>\n'

files=(
  "cctv-installation.html"
  "computer-applications.html"
  "computer-essentials.html"
  "computer-networking.html"
  "computer-repair.html"
  "cybersecurity.html"
  "digital-marketing.html"
  "german-language.html"
  "graphic-design.html"
  "mobile-phone-repair.html"
  "mtti-ielts.html"
  "programming.html"
  "video-editing.html"
  "web-design.html"
)

for file in "${files[@]}"; do
  if [ -f "$file" ] && ! grep -q "AW-17161883901" "$file"; then
    sed -i "s/<script async src=\"https:\/\/www.googletagmanager.com\/gtag\/js?id=G-8ZEH42CBVL\"><\/script>/$GTAG<script async src=\"https:\/\/www.googletagmanager.com\/gtag\/js?id=G-8ZEH42CBVL\"><\/script>/" "$file"
    if grep -q "AW-17161883901" "$file"; then
      echo "✓ $file"
    else
      echo "✗ $file (failed)"
    fi
  else
    echo "⊘ $file (already has tag or not found)"
  fi
done

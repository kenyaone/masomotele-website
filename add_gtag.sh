#!/bin/bash

# The Google Ads tag to insert
GTAG_CODE="<!-- Google tag (gtag.js) - Google Ads Conversion Tracking -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=AW-17161883901\"><\/script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17161883901');
<\/script>
"

# Course pages to update
COURSE_FILES=(
  "artificial-intelligence.html"
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

count=0

for file in "${COURSE_FILES[@]}"; do
  if [ -f "$file" ]; then
    # Check if file already has the tag
    if ! grep -q "AW-17161883901" "$file"; then
      echo "Adding tag to $file..."
      # Check if file has <head> tag
      if grep -q "<head>" "$file"; then
        # Insert after <head> tag
        sed -i "/<head>/a\\$GTAG_CODE" "$file"
        count=$((count + 1))
        echo "✓ Updated $file"
      else
        echo "✗ $file doesn't have <head> tag, skipping"
      fi
    else
      echo "⊘ $file already has the tag, skipping"
    fi
  else
    echo "✗ $file not found"
  fi
done

echo ""
echo "✓ Added tag to $count files"

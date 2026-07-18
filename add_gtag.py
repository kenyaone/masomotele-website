#!/usr/bin/env python
import os
import sys

gtag_code = """    <!-- Google tag (gtag.js) - Google Ads Conversion Tracking -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17161883901"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'AW-17161883901');
    </script>
"""

course_files = [
    "artificial-intelligence.html",
    "cctv-installation.html",
    "computer-applications.html",
    "computer-essentials.html",
    "computer-networking.html",
    "computer-repair.html",
    "cybersecurity.html",
    "digital-marketing.html",
    "german-language.html",
    "graphic-design.html",
    "mobile-phone-repair.html",
    "mtti-ielts.html",
    "programming.html",
    "video-editing.html",
    "web-design.html",
]

count = 0
for filename in course_files:
    if not os.path.exists(filename):
        print(f"✗ {filename} not found")
        continue
    
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Check if already has the tag
    if "AW-17161883901" in content:
        print(f"⊘ {filename} already has the tag, skipping")
        continue
    
    # Find <head> tag and insert after it
    if "<head>" not in content:
        print(f"✗ {filename} doesn't have <head> tag, skipping")
        continue
    
    # Insert the tag right after <head>
    new_content = content.replace("<head>", "<head>\n" + gtag_code, 1)
    
    with open(filename, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    count += 1
    print(f"✓ Updated {filename}")

print(f"\n✓ Added tag to {count} files")

#!/usr/bin/env python3
"""
Script to improve image alt text in MTTI website
Replaces generic alt text with SEO-optimized, descriptive alternatives
"""

import re

# Mapping of old alt text to new, SEO-optimized alt text
ALT_TEXT_MAP = {
    'MTTI Class': 'MTTI students in computer applications class learning professional skills at Eldoret technical training institute',
    'CNA Students': 'Healthcare students training in certified nursing assistant program with hands-on patient care practice at MTTI Eldoret',
    'Essentials Training': 'Computer essentials practical training session at MTTI Eldoret with industry-standard equipment and expert instructors',
    'First Aid Training': 'First aid and emergency response training at MTTI showing CPR techniques and patient care procedures',
    'First Aid Training 2': 'Hands-on first aid training demonstrating emergency medical procedures taught at Masomotele Technical Training Institute Eldoret',
    'First Aid Training 3': 'Advanced first aid training techniques and emergency response skills taught by certified healthcare professionals at MTTI',
    'Computer Lab': 'MTTI computer laboratory with modern desktop computers and networking equipment for ICT hands-on training in Eldoret Kenya',
    'MTTI Lab': 'State-of-the-art MTTI computer lab with industry-standard computers for technical training in Eldoret',
    'MTTI Students': 'MTTI vocational training students in classroom at Sagaas Centre Eldoret receiving TVETA-accredited technical education',
    'Computer Class': 'MTTI computer applications class with students learning office software and IT skills in Eldoret training center',
    'MTTI Computer Lab': 'Professional computer laboratory at MTTI Eldoret with modern equipment for ICT and web development training',
    'MTTI Logo': 'Masomotele Technical Training Institute logo - TVETA accredited vocational training center in Eldoret Kenya',
}

def improve_alt_text(filename):
    """Improve alt text in HTML file"""
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()

    original_content = content
    replacements = 0

    # Replace each old alt text with new SEO-optimized version
    for old_alt, new_alt in ALT_TEXT_MAP.items():
        # Match pattern: alt="old_alt"
        pattern = f'alt="{re.escape(old_alt)}"'
        if pattern in content:
            content = content.replace(f'alt="{old_alt}"', f'alt="{new_alt}"')
            print(f"✓ Updated: '{old_alt}' → '{new_alt[:60]}...'")
            replacements += 1

    # Write updated content
    if replacements > 0:
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"\n✅ Completed! Updated {replacements} image alt texts in {filename}")
        return True
    else:
        print(f"❌ No alt text updates found for {filename}")
        return False

if __name__ == '__main__':
    # Update main homepage
    improve_alt_text('/home/uvyzhdzt/public_html/index.html')

    # Update CNA page
    improve_alt_text('/home/uvyzhdzt/public_html/cna.html')

    # Update German page
    improve_alt_text('/home/uvyzhdzt/public_html/german-language.html')

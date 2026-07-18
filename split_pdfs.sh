#!/bin/bash

# PDF pages mapping
declare -A courses=(
    ["1"]="Web-Design-Development"
    ["2"]="Video-Editing"
    ["3"]="IELTS-Preparation"
    ["4"]="Graphic-Design"
    ["5"]="Programming-Coding"
    ["6"]="CCTV-Installation"
    ["7"]="Computer-Networking"
    ["8"]="Computer-Repair"
    ["9"]="Computer-Essentials"
    ["10"]="Computer-Applications"
    ["11"]="Printer-Photocopier-Repair"
    ["12"]="Social-Media-Marketing"
    ["13"]="Artificial-Intelligence"
    ["14"]="Cyber-Security"
    ["15"]="Security-Operations"
    ["16"]="Baking"
    ["17"]="Food-Production-Cookery"
    ["18"]="Baking-German-A1-B2"
    ["19"]="Baking-Technology-German"
    ["20"]="Catering-Accommodation-German"
    ["21"]="Food-Technology-German"
    ["22"]="Food-Beverage-Operations-German"
)

INPUT_PDF="ALL_COURSES_FEE_STRUCTURES-MTTI.pdf"

for page in "${!courses[@]}"; do
    course_name="${courses[$page]}"
    output_file="${course_name}-Fees-MTTI.pdf"
    
    echo "Creating: $output_file (page $page)"
    
    gs -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite \
       -dFirstPage=$page -dLastPage=$page \
       -sOutputFile="$output_file" "$INPUT_PDF"
    
    if [ -f "$output_file" ]; then
        echo "✓ Created $output_file"
    else
        echo "✗ Failed to create $output_file"
    fi
done

echo "Done! All course PDFs created."
ls -lh *Fees-MTTI.pdf | wc -l
echo "files created"

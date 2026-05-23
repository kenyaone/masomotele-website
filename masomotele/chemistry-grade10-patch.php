<?php
/**
 * MTTI LMS - Chemistry Grade 10 Template Patch
 * 
 * INSTRUCTIONS:
 * 1. Open /var/www/html/mtti-lms/includes/curriculum-templates.php
 * 2. Find the entry: 'Chemistry (Grade 10-12 STEM)' => [...]
 * 3. DELETE that entire block (from the key line to its closing ],)
 * 4. PASTE the new block below in its place
 * 
 * Or run: php /path/to/this/chemistry-grade10-patch.php
 * 
 * Source: KICD Senior School Curriculum Design - Grade 10 Chemistry, June 2024 (Draft)
 * Total: 180 lessons across 3 strands, 8 sub-strands
 */

// ============================================================
// OPTION A: Manual copy-paste — use this block
// ============================================================

/*
FIND AND DELETE THIS:
---------------------
        'Chemistry (Grade 10-12 STEM)' => [
            'Structure and Bonding' => ['Atomic Structure', 'Periodic Table', 'Chemical Bonding', 'Intermolecular Forces'],
            'Chemical Reactions' => ['Reaction Rates', 'Equilibrium', 'Energetics', 'Electrochemistry'],
            'Organic Chemistry' => ['Hydrocarbons', 'Alcohols', 'Carboxylic Acids', 'Esters', 'Polymers'],
            'Analytical Chemistry' => ['Separation Techniques', 'Qualitative Analysis', 'Quantitative Analysis'],
            'Industrial Chemistry' => ['Manufacturing Processes', 'Environmental Chemistry', 'Green Chemistry'],
        ],

REPLACE WITH THIS:
------------------
*/

$chemistry_grade10 = [
        // ═══════════════════════════════════════════════════════════
        // CHEMISTRY — GRADE 10 (KICD June 2024 Curriculum Design)
        // CBE Senior School — STEM Pathway
        // 3 Strands, 8 Sub-strands, 180 Lessons
        // ═══════════════════════════════════════════════════════════
        'Chemistry (Grade 10 STEM)' => [

            // ── Strand 1.0: INORGANIC CHEMISTRY (114 lessons) ──

            // Sub-strand 1.1: Introduction to Chemistry (6 lessons)
            'Introduction to Chemistry' => [
                'Definition of Chemistry',
                'Branches of Chemistry',
                'Careers in Chemistry',
                'Chemistry in Daily Life',
                'Drug Prescription and Dosage',
                'Drug and Substance Use',
            ],

            // Sub-strand 1.2: The Atom (24 lessons)
            'The Atom' => [
                'Structure of the Atom',
                'Atomic Number and Mass Number',
                'Dalton Model',
                'Rutherford Model',
                'Rutherford Gold Foil Experiment',
                'Isotopes',
                'Relative Atomic Mass',
                'Isotopic Abundance Calculations',
                'Energy Levels and Orbitals',
                's and p Orbitals',
                'Order of Filling Electrons in Orbitals',
                'Electron Arrangement of First 20 Elements',
            ],

            // Sub-strand 1.3: The Periodic Table (28 lessons)
            'The Periodic Table' => [
                'Development of the Periodic Table',
                'Groups and Periods',
                'Alkali Metals',
                'Alkaline Earth Metals',
                'Halogens',
                'Noble Gases',
                'Transition Elements',
                'Stability of Atoms',
                'Ion Formation - Cations and Anions',
                'Valency and Oxidation Number',
                'Electron Arrangement of Ions Using s and p Notation',
                'Variable Oxidation Numbers',
                'Radicals',
                'Formulae of Compounds',
                'Balanced Chemical Equations',
            ],

            // Sub-strand 1.4: Chemical Bonding (24 lessons)
            'Chemical Bonding' => [
                'Octet and Duplet Rule',
                'Ionic Bonding',
                'Covalent Bonding',
                'Dative Covalent Bonding',
                'Hydrogen Bonding',
                'Van der Waals Forces',
                'Metallic Bonding',
                'Lewis Dot and Cross Diagrams',
                'Giant Ionic Structures',
                'Simple Molecular Structures',
                'Giant Atomic Covalent Structures',
                'Giant Metallic Structures',
                'Physical Properties and Bond Types',
                'Solubility and Bond Types',
                'Electrical Conductivity and Bond Types',
                'Melting and Boiling Points and Bond Types',
                'Diamond and Graphite Structures',
                'Sodium Chloride Structure',
                'Uses Based on Bond Types and Structures',
            ],

            // Sub-strand 1.5: Periodicity (32 lessons)
            'Periodicity' => [
                'Trends in Group I Physical Properties',
                'Trends in Group II Physical Properties',
                'Trends in Group VII Physical Properties',
                'Trends in Group VIII Physical Properties',
                'Group I Reactions with Oxygen',
                'Group I Reactions with Chlorine',
                'Group I Reactions with Cold Water',
                'Group I Reactions with Steam',
                'Group I Reactions with Dilute Acids',
                'Group II Reactions with Oxygen',
                'Group II Reactions with Chlorine',
                'Group II Reactions with Cold Water',
                'Group II Reactions with Steam',
                'Group II Reactions with Dilute Acids',
                'Chlorine Physical Properties',
                'Chlorine Reaction with Water',
                'Chlorine Reaction with Metals',
                'Displacement Reactions of Halogens',
                'Bleaching Action of Chlorine',
                'Bromine and Iodine Properties',
                'Period 3 Atomic Size Trends',
                'Period 3 Ionisation Energy Trends',
                'Period 3 Electron Affinity Trends',
                'Period 3 Electronegativity Trends',
                'Period 3 Melting and Boiling Point Trends',
                'Period 3 Reactions with Oxygen',
                'Period 3 Reactions with Water',
                'Period 3 Reactions with Chlorine',
                'Period 3 Reactions with Dilute Acids',
                'Uses of Group I and II Elements',
                'Uses of Group VII Elements',
                'Uses of Group VIII Elements',
            ],

            // ── Strand 2.0: PHYSICAL CHEMISTRY (58 lessons) ──

            // Sub-strand 2.1: Acids and Bases (28 lessons)
            'Acids and Bases' => [
                'Dissociation of Acids in Water',
                'Dissociation of Bases in Water',
                'Chemical Properties of Acids',
                'Reactions of Acids with Metals',
                'Reactions of Acids with Carbonates',
                'Reactions of Acids with Hydrogen Carbonates',
                'Reactions of Acids with Metal Oxides',
                'Reactions of Acids with Hydroxides',
                'Amphoteric Oxides and Hydroxides',
                'Indicators',
                'Universal Indicator and pH Scale',
                'Strong and Weak Acids',
                'Strong and Weak Bases',
                'Electrical Conductivity of Acids and Bases',
                'Applications of Acids and Bases',
            ],

            // Sub-strand 2.2: Introduction to Salts (30 lessons)
            'Introduction to Salts' => [
                'Meaning of Salt',
                'Normal Salts',
                'Acidic Salts',
                'Basic Salts',
                'Double Salts',
                'Solubility of Chlorides',
                'Solubility of Carbonates',
                'Solubility of Nitrates',
                'Solubility of Sulphates',
                'Preparation by Direct Synthesis',
                'Preparation from Acid and Metal',
                'Preparation from Acid and Base',
                'Preparation from Acid and Carbonate',
                'Preparation from Acid and Hydrogen Carbonate',
                'Precipitation Reactions',
                'Ionic Equations for Precipitation',
                'Hygroscopic Salts',
                'Deliquescent Salts',
                'Efflorescent Salts',
                'Applications of Salts in Agriculture',
                'Applications of Salts in Food Industry',
                'Applications of Salts in Medicine',
                'Applications of Salts in Industry',
                'Inorganic Fertilisers and Environmental Impact',
                'Eutrophication and Water Pollution',
                'Mitigation of Fertiliser Challenges',
            ],

            // ── Strand 3.0: ORGANIC CHEMISTRY (8 lessons) ──

            // Introduction only at Grade 10 level
            'Introduction to Organic Chemistry' => [
                'Introduction to Organic Compounds',
                'Carbon and Its Unique Properties',
                'Classification of Organic Compounds',
                'Importance of Organic Chemistry',
            ],
        ],
];

// ============================================================
// OPTION B: Auto-patcher — run this script to patch the file
// ============================================================

$templateFile = '/var/www/html/mtti-lms/includes/curriculum-templates.php';

if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === '--apply') {
    if (!file_exists($templateFile)) {
        echo "ERROR: Template file not found at $templateFile\n";
        echo "Usage: php chemistry-grade10-patch.php --apply\n";
        exit(1);
    }

    $content = file_get_contents($templateFile);

    // Find and remove old Chemistry (Grade 10-12 STEM) block
    $oldPattern = "/\s*'Chemistry \(Grade 10-12 STEM\)'\s*=>\s*\[.*?\],/s";

    if (!preg_match($oldPattern, $content)) {
        echo "WARNING: Could not find 'Chemistry (Grade 10-12 STEM)' block.\n";
        echo "The template may have already been updated or has a different format.\n";
        echo "Please manually replace the Chemistry block.\n";
        exit(1);
    }

    // Build the replacement string
    $newBlock = "\n        // ═══════════════════════════════════════════════════════════\n";
    $newBlock .= "        // CHEMISTRY — GRADE 10 (KICD June 2024 Curriculum Design)\n";
    $newBlock .= "        // CBE Senior School — STEM Pathway\n";
    $newBlock .= "        // 3 Strands, 8 Sub-strands, 180 Lessons\n";
    $newBlock .= "        // ═══════════════════════════════════════════════════════════\n";

    // Export the array as PHP code
    $newBlock .= "        'Chemistry (Grade 10 STEM)' => [\n";
    foreach ($chemistry_grade10['Chemistry (Grade 10 STEM)'] as $substrand => $topics) {
        $newBlock .= "            '$substrand' => ['" . implode("', '", $topics) . "'],\n";
    }
    $newBlock .= "        ],";

    $content = preg_replace($oldPattern, $newBlock, $content, 1);

    // Backup original
    $backupFile = $templateFile . '.bak.' . date('Ymd_His');
    copy($templateFile, $backupFile);
    echo "Backup created: $backupFile\n";

    // Write updated file
    file_put_contents($templateFile, $content);
    echo "SUCCESS: Chemistry template updated to Grade 10 KICD (June 2024).\n";
    echo "Old: 'Chemistry (Grade 10-12 STEM)' with 5 generic strands\n";
    echo "New: 'Chemistry (Grade 10 STEM)' with 8 KICD sub-strands (180 lessons)\n\n";

    // Summary
    echo "Sub-strands seeded:\n";
    foreach ($chemistry_grade10['Chemistry (Grade 10 STEM)'] as $substrand => $topics) {
        echo "  ● $substrand (" . count($topics) . " topics)\n";
    }
    echo "\nTotal topics: " . array_sum(array_map('count', $chemistry_grade10['Chemistry (Grade 10 STEM)'])) . "\n";

} else if (php_sapi_name() === 'cli') {
    echo "MTTI LMS — Chemistry Grade 10 Curriculum Patch\n";
    echo "================================================\n";
    echo "Source: KICD Senior School Curriculum Design, June 2024\n\n";

    echo "This will replace:\n";
    echo "  OLD: 'Chemistry (Grade 10-12 STEM)' — 5 generic strands\n";
    echo "  NEW: 'Chemistry (Grade 10 STEM)' — 8 KICD sub-strands, 180 lessons\n\n";

    echo "Sub-strands:\n";
    foreach ($chemistry_grade10['Chemistry (Grade 10 STEM)'] as $substrand => $topics) {
        echo "  ● $substrand (" . count($topics) . " topics)\n";
    }
    echo "\nTotal topics: " . array_sum(array_map('count', $chemistry_grade10['Chemistry (Grade 10 STEM)'])) . "\n";

    echo "\nTo apply: php chemistry-grade10-patch.php --apply\n";
    echo "To manual: Copy the array from this file into curriculum-templates.php\n";
}

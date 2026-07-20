<?php
/**
 * PRP-01 (Programming/Coding Principles) curriculum rebuild — Unit 6:
 * Final Project and Assessment. Different shape from Units 1-5: one
 * capstone project (Overview/Video/Brief/Practical) that combines every
 * topic from the course, plus a single comprehensive Final Course
 * Assessment quiz spanning all 5 prior units — matching the WDD-01
 * Final Assessment precedent (one larger review quiz, not per-topic).
 *
 * Usage: wp eval-file build-prp01-unit6.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

require_once __DIR__ . '/build-prp01-unit5.php'; // cascades: mtti_lesson_theory_html(), mtti_python_lab_html(), mtti_objectives_fragment(), mtti_build_final_quiz_html()

// ---------------------------------------------------------------------
// Final Project: Student Grade Report System
// ---------------------------------------------------------------------

$final_project_overview = array(
    'intro' => "This is it — the final project brings together everything from Units 1 through 5 into one working program: a student grade report system.",
    'points' => array("Combining functions, loops, conditionals, lists/dictionaries, and error handling in one program", "Reading messy real-world-style data safely, without crashing", "Saving a summary report to a file"),
);

$final_project_lesson_sections = array(
    array(
        'heading' => 'Welcome to Your Final Project',
        'body_html' => "<p>Every skill from this course comes together here. You'll build a small <strong>Student Grade Report System</strong> that takes a list of student records, safely handles bad data, calculates grades and a class average, and saves a summary report to a file — a realistic, working program, not just an isolated exercise.</p>",
    ),
    array(
        'heading' => 'Project Requirements',
        'body_html' => "<ol>"
            . "<li>Define a function <code>get_grade(score)</code> that returns \"A\" for 80 and above, \"B\" for 60-79, and \"C\" for below 60 (from Unit 3).</li>"
            . "<li>Loop through a list of student dictionaries, each with a <code>name</code> and a <code>score</code> stored as text — one entry is deliberately invalid (from Unit 4).</li>"
            . "<li>Use try/except to safely convert each score to a number, skipping and reporting any invalid entries instead of crashing (from Unit 5).</li>"
            . "<li>For valid students, calculate their grade and print a summary line, while building up a running total for the class average (from Units 2 and 3).</li>"
            . "<li>After processing everyone, calculate and print the class average, then save a report to a file (from Unit 5).</li>"
            . "</ol>",
    ),
    array(
        'heading' => 'A Similar Worked Example',
        'body_html' => "<p>Here's the same overall pattern applied to a simpler pass/fail check, to illustrate the shape of the solution without giving away the actual project:</p>",
        'code' => "attendance = [\n    {\"name\": \"Zawadi\", \"days\": \"18\"},\n    {\"name\": \"Otieno\", \"days\": \"N/A\"},\n]\n\ndef get_status(days):\n    return \"Good\" if days >= 15 else \"Low\"\n\nfor record in attendance:\n    try:\n        days = int(record[\"days\"])\n    except ValueError:\n        print(\"Skipping\", record[\"name\"], \": invalid days\")\n        continue\n    print(record[\"name\"], \"-\", get_status(days))",
    ),
);

$final_project_practical = array(
    'brief' => "Build a student grade report combining everything from this course. Given the students list (each with a name and a score stored as text — one entry, \"N/A\", is deliberately invalid): (1) define get_grade(score) returning \"A\" for 80+, \"B\" for 60-79, \"C\" for below 60; (2) loop through students, using try/except to safely convert each score to int, skipping invalid ones with \"Skipping <name>: invalid score\"; (3) for valid students print \"<name>: <score> (<grade>)\" and track a running total; (4) print the class average (rounded to 1 decimal) as \"Class average: X\"; (5) write each valid student's \"name,score,grade\" to a file called grade_report.txt using with open().",
    'starter' => "students = [\n    {\"name\": \"Amina\", \"score\": \"82\"},\n    {\"name\": \"Brian\", \"score\": \"55\"},\n    {\"name\": \"Cynthia\", \"score\": \"N/A\"},\n    {\"name\": \"David\", \"score\": \"91\"},\n]\n\ndef get_grade(score):\n    # return \"A\" for 80+, \"B\" for 60-79, \"C\" for below 60\n\n\ntotal = 0\nvalid_count = 0\nreport_lines = []\n\nfor student in students:\n    name = student[\"name\"]\n    try:\n        score = int(student[\"score\"])\n    except ValueError:\n        print(\"Skipping \" + name + \": invalid score\")\n        continue\n    grade = get_grade(score)\n    print(name + \": \" + str(score) + \" (\" + grade + \")\")\n    total += score\n    valid_count += 1\n    report_lines.append(name + \",\" + str(score) + \",\" + grade)\n\n# calculate and print the class average, rounded to 1 decimal place\n# format: \"Class average: X\"\n\n\n# write each line in report_lines to grade_report.txt, one per line, using with open()\n",
    'checks' => array(
        array('type' => 'output_contains', 'value' => 'Skipping Cynthia: invalid score', 'label' => 'Safely skips the invalid entry'),
        array('type' => 'output_contains', 'value' => 'Amina: 82 (A)', 'label' => 'Correctly grades a valid student'),
        array('type' => 'output_contains', 'value' => 'Class average: 76.0', 'label' => 'Correctly calculates the class average'),
        array('type' => 'source_contains', 'value' => 'def get_grade', 'label' => 'Defines get_grade() correctly'),
        array('type' => 'source_contains', 'value' => 'with open', 'label' => 'Saves the report to a file'),
        array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without crashing'),
    ),
);

// ---------------------------------------------------------------------
// Final Course Assessment — 18 questions spanning all 5 units, matching
// the WDD-01 Final Assessment precedent (one larger review quiz).
// ---------------------------------------------------------------------

$final_assessment_questions = array(
    // Unit 1 — Python Fundamentals
    array('id' => 'q1', 'text' => 'What does type(5.5) return?', 'options' => array("<class 'int'>", "<class 'float'>", "<class 'str'>", "<class 'bool'>"), 'answer' => 1, 'explanation' => '5.5 has a decimal point, so it is a float.'),
    array('id' => 'q2', 'text' => 'Which of these correctly assigns the value 10 to a variable x?', 'options' => array('x == 10', 'x = 10', '10 = x', 'x => 10'), 'answer' => 1, 'explanation' => 'A single = is the assignment operator in Python.'),
    array('id' => 'q3', 'text' => 'What data type does input() always return, no matter what the user types?', 'options' => array('int', 'float', 'str', 'bool'), 'answer' => 2, 'explanation' => 'input() always returns a string, even if the text looks like a number.'),
    // Unit 2 — Control Flow
    array('id' => 'q4', 'text' => 'What numbers does range(1, 5) produce?', 'options' => array('1, 2, 3, 4', '1, 2, 3, 4, 5', '0, 1, 2, 3, 4', '2, 3, 4, 5'), 'answer' => 0, 'explanation' => 'range(1, 5) starts at 1 and stops before 5.'),
    array('id' => 'q5', 'text' => 'What is the key difference between break and continue in a loop?', 'options' => array('They do the same thing', 'break exits the loop entirely; continue skips to the next iteration', 'break skips an iteration; continue exits the loop', 'Neither can be used inside a for loop'), 'answer' => 1, 'explanation' => 'break stops the loop completely; continue just moves on to the next pass.'),
    array('id' => 'q6', 'text' => 'In an if/elif/else chain, how many branches can run for one execution?', 'options' => array('All of them', 'At most one — the first matching condition', 'Exactly two', 'None, ever'), 'answer' => 1, 'explanation' => 'Python runs only the first branch whose condition is True (or else if none match).'),
    // Unit 3 — Functions and Modularity
    array('id' => 'q7', 'text' => 'Which keyword defines a function in Python?', 'options' => array('function', 'def', 'func', 'lambda'), 'answer' => 1, 'explanation' => 'Functions are defined with the def keyword.'),
    array('id' => 'q8', 'text' => 'What does a function return if it has no return statement?', 'options' => array('0', '"" (empty string)', 'None', 'An error, always'), 'answer' => 2, 'explanation' => 'A function without a return statement implicitly returns None.'),
    array('id' => 'q9', 'text' => 'What is the difference between a parameter and an argument?', 'options' => array('There is no difference', 'A parameter is the placeholder in the definition; an argument is the actual value passed in the call', 'An argument is the placeholder; a parameter is the actual value', 'Parameters are only used in loops'), 'answer' => 1, 'explanation' => 'Parameter = definition-side name; argument = the actual value supplied when calling.'),
    array('id' => 'q10', 'text' => 'What is a local variable?', 'options' => array('A variable available everywhere in the program', 'A variable that only exists inside the function where it was created', 'A variable that can never change', 'A variable created before the program starts'), 'answer' => 1, 'explanation' => 'Local variables are scoped to the function they were created in.'),
    // Unit 4 — Data Structures
    array('id' => 'q11', 'text' => 'How do you access the first item of a list called items?', 'options' => array('items[1]', 'items[0]', 'items.first()', 'items(0)'), 'answer' => 1, 'explanation' => 'List indexing is 0-based, so the first item is items[0].'),
    array('id' => 'q12', 'text' => 'What does a negative index like -1 refer to in a list?', 'options' => array('The first item', 'An invalid index — negative numbers are not allowed', 'The last item', 'A random item'), 'answer' => 2, 'explanation' => '-1 counts from the end of the list, referring to the last item.'),
    array('id' => 'q13', 'text' => 'What is the main difference between a list and a dictionary?', 'options' => array('There is no real difference', 'A list is accessed by numeric index; a dictionary is accessed by named key', 'A dictionary can only store numbers', 'A list cannot be looped over'), 'answer' => 1, 'explanation' => 'Lists use position/index; dictionaries use keys (labels) to access values.'),
    array('id' => 'q14', 'text' => 'Which list method adds an item to the end of a list?', 'options' => array('add()', 'insert()', 'append()', 'push()'), 'answer' => 2, 'explanation' => 'append() adds a single item to the end of a list.'),
    // Unit 5 — Error Handling and File I/O
    array('id' => 'q15', 'text' => 'What does a try/except block let you do?', 'options' => array('Prevent every possible error from ever occurring', 'Handle an error gracefully instead of letting the program crash', 'Skip writing any error-prone code entirely', 'Automatically fix bugs in your logic'), 'answer' => 1, 'explanation' => 'try/except catches an exception when it happens, letting your program respond instead of crashing.'),
    array('id' => 'q16', 'text' => 'Which mode opens a file for writing (creating or overwriting it)?', 'options' => array('"r"', '"w"', '"a"', '"x"'), 'answer' => 1, 'explanation' => '"w" opens a file for writing, creating it if needed or overwriting existing content.'),
    array('id' => 'q17', 'text' => 'What does the raise keyword do?', 'options' => array('Catches an exception', 'Deliberately triggers an exception based on your own program logic', 'Prevents all exceptions from occurring', 'Ends the program immediately without an error'), 'answer' => 1, 'explanation' => 'raise lets you trigger an exception intentionally, for cases Python itself would not catch.'),
    array('id' => 'q18', 'text' => 'What is the benefit of with open("file.txt") as f: over calling open() directly?', 'options' => array('It opens the file faster', 'It automatically closes the file when the block finishes, even if an error occurs', 'It is the only way to read a file in Python', 'It prevents the file from ever being edited'), 'answer' => 1, 'explanation' => 'The with statement guarantees the file is properly closed, which is safer than remembering to call close() manually.'),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PRP01_UNIT6_BUILD') && MTTI_RUN_PRP01_UNIT6_BUILD) {
    global $wpdb;
    $lessons_table = $wpdb->prefix . 'mtti_lessons';
    $unit6_id = 153; // Final Project and Assessment, created during the Unit 1 pass
    $tag = 'Final Project and Assessment';
    $order = 500;

    $wpdb->insert($lessons_table, array(
        'course_id' => 9, 'unit_id' => $unit6_id,
        'title' => 'Final Project: Student Grade Report System — Overview',
        'content' => mtti_objectives_fragment($final_project_overview['intro'], $final_project_overview['points']),
        'content_type' => 'objectives', 'interactive_role' => null,
        'order_number' => $order, 'release_week' => 6,
        'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
    ));
    echo "Overview lesson_id={$wpdb->insert_id}\n";
    $order++;

    $wpdb->insert($lessons_table, array(
        'course_id' => 9, 'unit_id' => $unit6_id,
        'title' => 'Final Project: Student Grade Report System — Video',
        'content' => null, 'content_type' => 'video', 'content_url' => null,
        'interactive_role' => null,
        'order_number' => $order, 'release_week' => 6,
        'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
    ));
    $order++;

    $wpdb->insert($lessons_table, array(
        'course_id' => 9, 'unit_id' => $unit6_id,
        'title' => 'Final Project: Student Grade Report System — Project Brief',
        'content' => mtti_lesson_theory_html(array('title' => 'Final Project: Student Grade Report System', 'lesson_tag' => $tag, 'sections' => $final_project_lesson_sections)),
        'content_type' => 'html_interactive', 'interactive_role' => null,
        'order_number' => $order, 'release_week' => 6,
        'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
    ));
    $order++;

    $practical_config = array(
        'title' => 'Final Project: Student Grade Report System — Practical',
        'lesson_tag' => $tag,
        'brief' => $final_project_practical['brief'],
        'starter' => $final_project_practical['starter'],
        'checks' => $final_project_practical['checks'],
        'pyodide_version' => 'v0.26.4',
    );
    $wpdb->insert($lessons_table, array(
        'course_id' => 9, 'unit_id' => $unit6_id,
        'title' => 'Final Project: Student Grade Report System — Practical',
        'content' => mtti_python_lab_html($practical_config),
        'content_type' => 'html_interactive', 'interactive_role' => null,
        'order_number' => $order, 'release_week' => 6,
        'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
    ));
    echo "Practical lesson_id={$wpdb->insert_id}\n";
    $order++;

    $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$lessons_table} WHERE lesson_id = %d", 460));
    $quiz_html = mtti_build_final_quiz_html($template, 'Final Course Assessment', $final_assessment_questions);
    $wpdb->insert($lessons_table, array(
        'course_id' => 9, 'unit_id' => $unit6_id,
        'title' => 'Final Course Assessment',
        'content' => $quiz_html,
        'content_type' => 'html_interactive', 'interactive_role' => null,
        'order_number' => $order, 'release_week' => 6,
        'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
    ));
    echo "Final Assessment lesson_id={$wpdb->insert_id}\n";

    echo "Unit 6 build complete.\n";
}

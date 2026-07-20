<?php
/**
 * PRP-01 (Programming/Coding Principles) curriculum rebuild — Unit 1:
 * Python Fundamentals. Also creates the empty shells for units 2-6 and
 * archives the 8 old broad lessons whose real content was mined as
 * source material for this unit.
 *
 * Usage: wp eval-file build-prp01-unit1.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

require_once __DIR__ . '/generate-python-labs.php';       // mtti_python_lab_html()
require_once __DIR__ . '/complete-wdd01-overviews.php';   // mtti_objectives_fragment(), mtti_build_final_quiz_html()

/**
 * Theory-content generator — matches the shared visual shell used across
 * every lesson in this codebase (Space Mono/Fredoka fonts, green->blue
 * gradient, numbered .task cards), but renders plain prose + code blocks
 * rather than an interactive widget (that's what Practical is for).
 *
 * $config: ['title', 'lesson_tag', 'sections' => [['heading', 'body_html', 'code'?], ...]]
 */
function mtti_lesson_theory_html($config) {
    $title      = $config['title'] ?? 'Lesson';
    $lesson_tag = $config['lesson_tag'] ?? 'Programming Principles';
    $sections   = $config['sections'] ?? array();

    $sections_html = '';
    foreach ($sections as $i => $sec) {
        $heading = esc_html($sec['heading'] ?? '');
        $body    = $sec['body_html'] ?? '';
        $code    = isset($sec['code']) ? '<pre class="code-block"><code>' . esc_html($sec['code']) . '</code></pre>' : '';
        $num     = $i + 1;
        $sections_html .= '<div class="task"><h2><span class="task-num">' . $num . '</span>' . $heading . '</h2><div class="section-body">' . $body . $code . '</div></div>';
    }

    $title_esc = esc_html($title);
    $tag_esc   = esc_html($lesson_tag);

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title_esc} | MTTI Programming Principles</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Fredoka:wght@500;600&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Space Mono', monospace;
    background: linear-gradient(135deg, #2E7D32 0%, #1565C0 100%);
    min-height: 100vh; padding: 20px; color: #fff;
}
.container { max-width: 900px; margin: 0 auto; }
.header { text-align: center; margin-bottom: 26px; }
.lesson-tag { display: inline-block; background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 999px; font-size: 0.85em; margin-bottom: 12px; }
.header h1 { font-family: 'Fredoka', sans-serif; font-size: 2.1em; margin-bottom: 8px; text-shadow: 3px 3px 0 rgba(0,0,0,0.2); }
.task {
    background: white; color: #333; border-radius: 22px;
    padding: 26px; margin-bottom: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.task h2 { font-family: 'Fredoka', sans-serif; font-size: 1.3em; margin-bottom: 14px; display: flex; align-items: center; }
.task-num {
    display: inline-block; width: 30px; height: 30px;
    background: linear-gradient(135deg, #2E7D32 0%, #1565C0 100%);
    color: white; border-radius: 50%; text-align: center; line-height: 30px;
    font-family: 'Fredoka', sans-serif; font-weight: 600; margin-right: 10px; flex-shrink: 0; font-size: 0.75em;
}
.section-body { color: #444; font-size: 0.98em; line-height: 1.7; }
.section-body p { margin-bottom: 12px; }
.section-body strong { color: #1B5E20; }
.section-body ul, .section-body ol { margin: 10px 0 14px 22px; }
.section-body li { margin-bottom: 6px; }
.code-block {
    background: #1e1e2e; color: #cdd6f4; border-radius: 10px;
    padding: 16px 18px; margin-top: 10px; overflow-x: auto;
    font-family: 'Courier New', monospace; font-size: 0.88em; line-height: 1.6;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="lesson-tag">{$tag_esc}</div>
        <h1>{$title_esc}</h1>
    </div>
    {$sections_html}
</div>
</body>
</html>
HTML;
}

// ---------------------------------------------------------------------
// Unit skeleton — 6 units, only Unit 1 gets real lessons this pass
// ---------------------------------------------------------------------

$new_units = array(
    array('code' => 'PRP-01-001', 'name' => 'Python Fundamentals', 'order' => 1),
    array('code' => 'PRP-01-002', 'name' => 'Control Flow', 'order' => 2),
    array('code' => 'PRP-01-003', 'name' => 'Functions and Modularity', 'order' => 3),
    array('code' => 'PRP-01-004', 'name' => 'Data Structures', 'order' => 4),
    array('code' => 'PRP-01-005', 'name' => 'Error Handling and File I/O', 'order' => 5),
    array('code' => 'PRP-01-006', 'name' => 'Final Project and Assessment', 'order' => 6),
);

// ---------------------------------------------------------------------
// Unit 1 — 7 topics, each: Overview / Video / Lesson / Practical / Quiz
// ---------------------------------------------------------------------

$topics = array();

// Topic 1 — What is Programming? Getting to Know Python
$topics[] = array(
    'title' => 'What is Programming? Getting to Know Python',
    'overview' => array(
        'intro' => "Before writing any code, it helps to know what programming actually is and why this course uses Python — this lesson covers both.",
        'points' => array("What programming means and why it's useful", "Why Python is a great first language", "A quick look at where this course is headed"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'What is Programming?', 'body_html' => "<p>Programming is the process of giving a computer clear, step-by-step instructions to solve a problem or complete a task. A computer only does exactly what it's told — so a big part of programming is learning to break a task down into precise, unambiguous steps a computer can follow.</p><p>Those instructions are written in a <strong>programming language</strong> — a set of rules the computer understands. This course uses <strong>Python</strong>.</p>"),
            array('heading' => 'Why Python?', 'body_html' => "<p>Python is a powerful yet beginner-friendly programming language used by companies like Google, Netflix, and NASA. It reads almost like plain English, which makes it one of the easiest languages to learn first — and it's used everywhere: web development, data science, automation, and more.</p><p><strong>Fun fact:</strong> Python was created in 1991 by Guido van Rossum, and it's named after the British comedy show <em>Monty Python's Flying Circus</em> — not the snake!</p>", 'code' => "print(\"Hello, World!\")\nprint(\"I am learning Python at MTTI!\")"),
            array('heading' => 'What This Unit Covers', 'body_html' => "<p>By the end of this unit you'll be able to write and run real Python programs, store information in variables, work with Python's basic data types, use arithmetic and comparison operators, and get input from a user.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "Using print(), display two lines: your name, and the message \"I am learning Python at MTTI!\" exactly as written.",
        'starter' => "# Line 1: print your name\n\n# Line 2: print \"I am learning Python at MTTI!\"\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'I am learning Python at MTTI!', 'label' => 'Prints the exact MTTI message'),
            array('type' => 'source_contains', 'value' => 'print', 'label' => 'Uses the print() function'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'Who created Python, and in what year?', 'options' => array('Guido van Rossum, 1991', 'Guido van Rossum, 2005', 'Dennis Ritchie, 1991', 'Mark Zuckerberg, 2004'), 'answer' => 0, 'explanation' => 'Python was created by Guido van Rossum and first released in 1991.'),
        array('id' => 'q2', 'text' => 'What is Python named after?', 'options' => array('The snake species', "Monty Python's Flying Circus", 'A Greek myth', "Guido's dog"), 'answer' => 1, 'explanation' => 'Despite the logo, Python is named after the British comedy show Monty Python\'s Flying Circus, not the snake.'),
        array('id' => 'q3', 'text' => 'What best describes what programming is?', 'options' => array('Typing random text into a computer', 'Giving a computer clear, step-by-step instructions to solve a problem', 'Only fixing broken software', 'Designing computer hardware'), 'answer' => 1, 'explanation' => 'Programming is about writing precise, step-by-step instructions for a computer to follow.'),
        array('id' => 'q4', 'text' => 'Why is Python often recommended as a first programming language?', 'options' => array('It only works on one operating system', 'Its syntax reads similarly to plain English, making it easier to learn', 'It cannot be used for real projects', 'It requires no computer to run'), 'answer' => 1, 'explanation' => "Python's readable, English-like syntax is one of the main reasons it's popular for beginners."),
        array('id' => 'q5', 'text' => 'Which of these is a real-world use of Python?', 'options' => array('Web development', 'Data science and automation', 'Both of the above', 'Neither — Python is only used for learning'), 'answer' => 2, 'explanation' => 'Python is widely used in both web development and data science/automation, among many other fields.'),
        array('id' => 'q6', 'text' => 'In Python, what does the print() function do?', 'options' => array('Sends a document to a physical printer', 'Displays output to the screen', 'Deletes a variable', 'Asks the user for input'), 'answer' => 1, 'explanation' => 'print() displays (outputs) whatever is inside its parentheses to the screen.'),
    ),
);

// Topic 2 — Your First Python Program
$topics[] = array(
    'title' => 'Your First Python Program',
    'overview' => array(
        'intro' => "Time to write real Python — this lesson covers the print() function, how quotes work, and how comments help you (and others) understand your code.",
        'points' => array("Displaying output with print()", "Using single and double quotes for text", "Writing comments with #"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The print() Function', 'body_html' => "<p>print() is how a Python program shows something on the screen. Whatever you put inside the parentheses gets displayed. Text must be wrapped in quotes — either single (<code>'</code>) or double (<code>\"</code>), it doesn't matter which, as long as you're consistent.</p>", 'code' => "print(\"Hello, World!\")\nprint('Eldoret, Kenya')"),
            array('heading' => 'Running Your Code', 'body_html' => "<p>Python reads your code from top to bottom, one line at a time, and runs each instruction in order. Each print() statement produces its own line of output.</p>", 'code' => "print(\"Line one\")\nprint(\"Line two\")\n# Output:\n# Line one\n# Line two"),
            array('heading' => 'Comments', 'body_html' => "<p>A comment starts with <code>#</code> and is ignored by Python entirely — it's just a note for humans reading the code, explaining what a piece of code does or why.</p>", 'code' => "# This program greets the user\nprint(\"Welcome!\")  # this line displays a greeting"),
        ),
    ),
    'practical' => array(
        'brief' => "Write a program that prints three lines: a welcome message, the course name \"Programming Principles\", and the motto \"Start Learning, Start Earning\".",
        'starter' => "# Print a welcome message\n\n# Print the course name: Programming Principles\n\n# Print the motto: Start Learning, Start Earning\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Programming Principles', 'label' => 'Prints the course name'),
            array('type' => 'output_contains', 'value' => 'Start Learning, Start Earning', 'label' => 'Prints the exact motto'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'Which of these correctly prints text to the screen in Python?', 'options' => array('print("Hello")', 'echo "Hello"', 'display("Hello")', 'output("Hello")'), 'answer' => 0, 'explanation' => 'print() is the built-in Python function for displaying output.'),
        array('id' => 'q2', 'text' => 'Which of these is a valid way to write text in Python?', 'options' => array("Only with double quotes", "Only with single quotes", "Either single or double quotes, as long as they match", "No quotes are needed"), 'answer' => 2, 'explanation' => "Python accepts both 'single' and \"double\" quotes for text — you just need the opening and closing quote to match."),
        array('id' => 'q3', 'text' => 'What symbol starts a comment in Python?', 'options' => array('//', '#', '<!--', '**'), 'answer' => 1, 'explanation' => 'Python comments start with the # symbol and are ignored when the code runs.'),
        array('id' => 'q4', 'text' => 'In what order does Python run the lines in a program by default?', 'options' => array('Bottom to top', 'Randomly', 'Top to bottom, one line at a time', 'All at once, simultaneously'), 'answer' => 2, 'explanation' => 'Python executes code sequentially, from the top of the file to the bottom.'),
        array('id' => 'q5', 'text' => 'What will print("Line one")\\nprint("Line two") output?', 'options' => array('"Line one" and "Line two" on the same line', '"Line one" then "Line two" on separate lines', 'Just "Line two"', 'A syntax error'), 'answer' => 1, 'explanation' => 'Each print() call outputs its own line, so the two statements produce two separate lines of output.'),
        array('id' => 'q6', 'text' => 'Why would you add a comment to your code?', 'options' => array('To make the program run faster', 'To explain what the code does, for yourself or others reading it', 'Comments are required for the code to work', 'To hide code from Python entirely (comments still run)'), 'answer' => 1, 'explanation' => "Comments don't affect how the program runs — they exist purely to explain the code to humans."),
    ),
);

// Topic 3 — Variables and Assignment
$topics[] = array(
    'title' => 'Variables and Assignment',
    'overview' => array(
        'intro' => "Programs need to remember information — this lesson covers variables, the labeled boxes that store values as your program runs.",
        'points' => array("What a variable is and why they're useful", "The rules for naming a variable", "Assigning and re-assigning values with ="),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'What is a Variable?', 'body_html' => "<p>A variable is like a labeled box that stores information. You give it a name, and you put a value inside using the <strong>assignment operator</strong>, <code>=</code>. Python figures out what kind of value it is automatically.</p>", 'code' => "name = \"Aisha\"\nage = 22\nheight = 1.65\nis_student = True\nprint(\"Name:\", name)"),
            array('heading' => 'Naming Rules', 'body_html' => "<p>A variable name must start with a letter or underscore (not a number), can only contain letters, numbers, and underscores, and is case-sensitive (<code>age</code> and <code>Age</code> are different variables). The convention in Python is <strong>snake_case</strong> — lowercase words separated by underscores, like <code>student_name</code>.</p>", 'code' => "student_name = \"Kofi\"   # good\n2ndPlace = \"Silver\"      # invalid — can't start with a number"),
            array('heading' => 'Re-assigning a Variable', 'body_html' => "<p>A variable's value can change as the program runs — that's why it's called a <em>variable</em>. Assigning a new value simply overwrites the old one.</p>", 'code' => "score = 10\nprint(score)   # 10\nscore = 15\nprint(score)   # 15"),
        ),
    ),
    'practical' => array(
        'brief' => "Create three variables: student_name, student_age, and favourite_language (set it to \"Python\"). Print all three.",
        'starter' => "student_name = \"\"\nstudent_age = 0\nfavourite_language = \"\"\n\n# Fill in real values above, then print all three below\n",
        'checks' => array(
            array('type' => 'source_contains', 'value' => 'student_name', 'label' => 'Defines a student_name variable'),
            array('type' => 'output_contains', 'value' => 'Python', 'label' => 'Output mentions Python'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What symbol is used to assign a value to a variable in Python?', 'options' => array('==', '=', '=>', '->'), 'answer' => 1, 'explanation' => 'A single equals sign (=) assigns a value to a variable. == is used for comparison, covered in a later lesson.'),
        array('id' => 'q2', 'text' => 'Which of these is a valid Python variable name?', 'options' => array('2ndPlace', 'student-name', 'student_age', 'student age'), 'answer' => 2, 'explanation' => "Variable names can't start with a number, and can't contain hyphens or spaces — only letters, numbers, and underscores, not starting with a number."),
        array('id' => 'q3', 'text' => 'Are age and Age the same variable in Python?', 'options' => array('Yes, Python ignores letter case', 'No, Python variable names are case-sensitive', 'Only if they hold the same value', 'It depends on the operating system'), 'answer' => 1, 'explanation' => 'Python is case-sensitive, so age and Age are two completely different variables.'),
        array('id' => 'q4', 'text' => 'What naming style does Python convention recommend for variables (e.g. studentName vs student_name)?', 'options' => array('camelCase', 'snake_case', 'PascalCase', 'ALL_CAPS'), 'answer' => 1, 'explanation' => 'Python convention favours snake_case (lowercase words separated by underscores) for variable names.'),
        array('id' => 'q5', 'text' => 'After running score = 10 then score = 15, what does print(score) output?', 'options' => array('10', '15', '10 then 15', 'An error'), 'answer' => 1, 'explanation' => 'The second assignment overwrites the first — score now holds 15.'),
        array('id' => 'q6', 'text' => 'What is the best way to describe a variable?', 'options' => array('A fixed value that never changes', 'A named storage location whose value can change while the program runs', 'A type of loop', 'A function that prints text'), 'answer' => 1, 'explanation' => 'A variable is a named container for a value, and that value can be reassigned as the program runs.'),
    ),
);

// Topic 4 — Data Types
$topics[] = array(
    'title' => 'Data Types',
    'overview' => array(
        'intro' => "Every value in Python has a type — this lesson covers the four basic types you'll use constantly, and how to check a value's type.",
        'points' => array("The four basic Python data types: str, int, float, bool", "How to check a value's type with type()", "Why a value's type affects what you can do with it"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The Four Basic Data Types', 'body_html' => "<p>Python has several built-in data types. The four you'll use constantly are:</p><ul><li><strong>str</strong> (string) — text, always in quotes, e.g. <code>\"Eldoret\"</code></li><li><strong>int</strong> (integer) — a whole number, e.g. <code>25</code></li><li><strong>float</strong> — a decimal number, e.g. <code>49.99</code></li><li><strong>bool</strong> (Boolean) — either <code>True</code> or <code>False</code></li></ul>", 'code' => "name = \"Kipchoge\"   # str\nage = 25             # int\nprice = 49.99         # float\nis_student = True     # bool"),
            array('heading' => 'Checking a Value\'s Type', 'body_html' => "<p>The built-in <code>type()</code> function tells you what type a value or variable is — useful when you're not sure, or when debugging.</p>", 'code' => "age = 25\nprint(type(age))\n# Output: <class 'int'>"),
            array('heading' => 'Why Type Matters', 'body_html' => "<p>Python only lets you do certain operations on certain types — you can add two numbers, but adding a number to text directly causes an error. Knowing a value's type helps you predict how your code will behave.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "Create one variable of each type: a string, an integer, a float, and a boolean. Print each variable together with its type using type() — e.g. print(my_string, type(my_string)).",
        'starter' => "my_string = \"hello\"\nmy_int = 5\nmy_float = 5.5\nmy_bool = True\n\n# print each variable and its type below\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => "<class 'str'>", 'label' => "Shows the string's type (str)"),
            array('type' => 'output_contains', 'value' => "<class 'int'>", 'label' => "Shows the integer's type (int)"),
            array('type' => 'output_contains', 'value' => "<class 'float'>", 'label' => "Shows the float's type (float)"),
            array('type' => 'output_contains', 'value' => "<class 'bool'>", 'label' => "Shows the boolean's type (bool)"),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'Which data type would 49.99 be in Python?', 'options' => array('int', 'float', 'str', 'bool'), 'answer' => 1, 'explanation' => '49.99 has a decimal point, so it is a float.'),
        array('id' => 'q2', 'text' => 'What does type("Eldoret") return?', 'options' => array("<class 'int'>", "<class 'str'>", "<class 'float'>", "<class 'bool'>"), 'answer' => 1, 'explanation' => 'Text in quotes is always a str (string) in Python.'),
        array('id' => 'q3', 'text' => 'Which of these is a valid bool value in Python?', 'options' => array('"True"', 'true', 'True', '1.0'), 'answer' => 2, 'explanation' => "Python's Boolean values are written True and False, capitalized and without quotes."),
        array('id' => 'q4', 'text' => 'What is 25 (with no decimal point) as a Python data type?', 'options' => array('int', 'float', 'str', 'bool'), 'answer' => 0, 'explanation' => 'A whole number with no decimal point is an int.'),
        array('id' => 'q5', 'text' => 'Which built-in function tells you the data type of a value?', 'options' => array('typeof()', 'type()', 'kind()', 'dtype()'), 'answer' => 1, 'explanation' => 'type() is the built-in Python function for checking a value\'s data type.'),
        array('id' => 'q6', 'text' => 'Why does a value\'s type matter in a program?', 'options' => array('It never matters, Python treats all values the same', 'It determines what operations are valid on that value', 'It only affects how the code looks, not how it runs', 'Types only matter for variable names, not values'), 'answer' => 1, 'explanation' => "A value's type determines which operations are valid — e.g. you can add two ints, but adding an int directly to a str causes an error."),
    ),
);

// Topic 5 — Basic Operators
$topics[] = array(
    'title' => 'Basic Operators',
    'overview' => array(
        'intro' => "Programs calculate and compare values constantly — this lesson covers arithmetic, precedence, and comparison/logical operators.",
        'points' => array("Arithmetic operators and the order Python evaluates them in", "Comparison operators for checking relationships between values", "Logical operators for combining conditions"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Arithmetic Operators', 'body_html' => "<p>Python supports the standard arithmetic operators: <code>+</code> add, <code>-</code> subtract, <code>*</code> multiply, <code>/</code> divide, <code>**</code> exponent (power), and <code>%</code> modulus (remainder after division).</p>", 'code' => "print(10 + 3)   # 13\nprint(10 % 3)   # 1  (remainder of 10 / 3)\nprint(2 ** 3)   # 8  (2 to the power of 3)"),
            array('heading' => 'Order of Operations (Precedence)', 'body_html' => "<p>Python follows the same order of operations as maths (BODMAS/PEMDAS) — multiplication and division happen before addition and subtraction. Use parentheses <code>()</code> to control the order explicitly.</p>", 'code' => "result1 = 2 + 3 * 4     # 14 — (3*4) evaluated first, then +2\nresult2 = (2 + 3) * 4   # 20 — parentheses force (2+3) first"),
            array('heading' => 'Comparison and Logical Operators', 'body_html' => "<p>Comparison operators check the relationship between two values and produce a bool result: <code>==</code> equal to, <code>!=</code> not equal to, <code>&gt;</code> / <code>&lt;</code> greater/less than, <code>&gt;=</code> / <code>&lt;=</code> greater/less than or equal to. Logical operators combine conditions: <code>and</code> (both must be True), <code>or</code> (at least one must be True), <code>not</code> (inverts a Boolean).</p>", 'code' => "age = 20\nprint(age == 20)   # True\nprint(age > 18 and age < 65)  # True"),
        ),
    ),
    'practical' => array(
        'brief' => "A shop sells an item at KES 1500. A customer buys 4. Apply a 10% discount. Print the total before discount, then the final total after discount.",
        'starter' => "price = 1500\nquantity = 4\n\n# calculate total (price * quantity)\n\n# calculate discount (10% of total)\n\n# calculate final total (total - discount)\n\n# print both the total and the final total\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '6000', 'label' => 'Prints the total before discount (6000)'),
            array('type' => 'output_contains', 'value' => '5400', 'label' => 'Prints the final total after discount (5400)'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does 10 % 3 evaluate to in Python?', 'options' => array('3', '1', '3.33', '30'), 'answer' => 1, 'explanation' => '% is the modulus operator — it gives the remainder after division. 10 divided by 3 is 3 remainder 1.'),
        array('id' => 'q2', 'text' => 'What does 2 ** 3 evaluate to?', 'options' => array('6', '8', '9', '5'), 'answer' => 1, 'explanation' => '** is the exponent operator — 2 to the power of 3 is 8.'),
        array('id' => 'q3', 'text' => 'What does 2 + 3 * 4 evaluate to, following Python\'s order of operations?', 'options' => array('20', '14', '24', '9'), 'answer' => 1, 'explanation' => 'Multiplication happens before addition: 3*4=12, then 2+12=14.'),
        array('id' => 'q4', 'text' => 'What does (2 + 3) * 4 evaluate to?', 'options' => array('14', '20', '9', '24'), 'answer' => 1, 'explanation' => 'Parentheses force (2+3)=5 to be evaluated first, then 5*4=20.'),
        array('id' => 'q5', 'text' => 'Which operator checks if two values are equal in Python?', 'options' => array('=', '==', '!=', '==='), 'answer' => 1, 'explanation' => '== compares two values for equality. A single = is assignment, not comparison.'),
        array('id' => 'q6', 'text' => 'For age > 18 and age < 65 to be True, what must be true?', 'options' => array('Only one of the two conditions', 'Both conditions must be True', 'Neither condition needs to be True', 'The age must be exactly 18 or 65'), 'answer' => 1, 'explanation' => 'and requires both conditions to be True for the whole expression to be True.'),
    ),
);

// Topic 6 — Getting User Input
$topics[] = array(
    'title' => 'Getting User Input',
    'overview' => array(
        'intro' => "Interactive programs respond to what a user types — this lesson covers the input() function and a key detail about what it returns.",
        'points' => array("How input() works", "Why input() always returns a string", "Writing a program that greets the user by name"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The input() Function', 'body_html' => "<p>The <code>input()</code> function makes a program interactive — it displays a message (the prompt), pauses and waits for the user to type something, then stores whatever they typed in a variable.</p>", 'code' => "name = input(\"What is your name? \")\nprint(\"Hello, \" + name + \"!\")"),
            array('heading' => 'input() Always Returns a String', 'body_html' => "<p>No matter what the user types — even if it looks like a number — <code>input()</code> always gives you back a <strong>string</strong> (str). This matters a lot once you want to do maths with what the user typed — you'll see how to fix that in the next lesson.</p>", 'code' => "age = input(\"How old are you? \")\nprint(type(age))\n# Output: <class 'str'> — even if the user typed 20"),
        ),
    ),
    'practical' => array(
        'brief' => "Ask the user for their name using input(), then print a greeting that uses it.",
        'starter' => "name = input(\"What is your name? \")\n\n# print a greeting using the name\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Brian', 'label' => 'Greets the simulated name correctly'),
            array('type' => 'source_contains', 'value' => 'input(', 'label' => 'Uses the input() function'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
        'simulated_inputs' => array('Brian'),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does the input() function do?', 'options' => array('Prints text to the screen', 'Pauses the program and waits for the user to type something', 'Deletes a variable', 'Converts a string to a number'), 'answer' => 1, 'explanation' => 'input() displays a prompt, waits for the user to type a response, and returns what they typed.'),
        array('id' => 'q2', 'text' => 'If a user types 25 in response to input(), what data type is actually stored?', 'options' => array('int', 'float', 'str', 'bool'), 'answer' => 2, 'explanation' => 'input() always returns a string, regardless of what the user typed — even digits.'),
        array('id' => 'q3', 'text' => "What does type(input(\"Age: \")) return if the user types 20?", 'options' => array("<class 'int'>", "<class 'str'>", "<class 'float'>", "<class 'bool'>"), 'answer' => 1, 'explanation' => "input() always returns a str, so even '20' comes back as text, not a number."),
        array('id' => 'q4', 'text' => 'What goes inside the parentheses of input()?', 'options' => array('Nothing, it must always be empty', 'An optional message (prompt) shown to the user', 'The variable name to store the result in', 'The expected data type'), 'answer' => 1, 'explanation' => "The text inside input()'s parentheses is the prompt message shown to the user before they type."),
        array('id' => 'q5', 'text' => 'Why is it useful for a program to use input()?', 'options' => array('It makes the program run faster', 'It lets the program respond to what the user types, making it interactive', 'It is required for every Python program to work', 'It automatically fixes errors in the code'), 'answer' => 1, 'explanation' => 'input() is what makes a program interactive, letting it react to real user input rather than fixed values.'),
        array('id' => 'q6', 'text' => 'Which of these correctly prompts the user and stores their answer?', 'options' => array('name = input("Your name: ")', 'input(name = "Your name: ")', 'name == input("Your name: ")', 'print(input("Your name: "))  only'), 'answer' => 0, 'explanation' => 'Assigning the result of input() to a variable with = is the standard way to capture and reuse the user\'s response.'),
    ),
);

// Topic 7 — Type Conversion and Formatting Output
$topics[] = array(
    'title' => 'Type Conversion and Formatting Output',
    'overview' => array(
        'intro' => "This unit's capstone lesson — converting between data types so you can actually use what input() gives you, wrapping up everything covered so far.",
        'points' => array("Converting strings to numbers with int() and float()", "Converting numbers to strings with str()", "Combining input() with conversion to do real calculations"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Why Conversion Matters', 'body_html' => "<p>Since <code>input()</code> always returns a string, trying to do maths with it directly causes an error. To fix this, Python provides conversion functions: <code>int(\"25\")</code> turns the string \"25\" into the whole number 25, and <code>float(\"3.14\")</code> turns \"3.14\" into the decimal number 3.14.</p>", 'code' => "age_text = input(\"How old are you? \")   # a string, e.g. \"20\"\nage_number = int(age_text)                 # now a real int\nprint(\"In 5 years you'll be\", age_number + 5)"),
            array('heading' => 'Converting the Other Way', 'body_html' => "<p><code>str()</code> does the opposite — it converts a number into a string, useful when you want to join it directly with other text using <code>+</code>.</p>", 'code' => "score = 95\nmessage = \"Your score is \" + str(score)\nprint(message)"),
            array('heading' => 'Putting It All Together', 'body_html' => "<p>Combining <code>input()</code> with a conversion function in one line is a very common pattern — it reads the input and converts it immediately.</p>", 'code' => "age = int(input(\"How old are you? \"))\nprint(\"Next year you'll be\", age + 1)"),
        ),
    ),
    'practical' => array(
        'brief' => "Ask for the user's age (as text via input()), convert it to an integer, then print what their age will be in 5 years.",
        'starter' => "age = input(\"How old are you? \")\n\n# convert age to an integer, add 5, then print the result\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '25', 'label' => 'Prints the correct age in 5 years (25)'),
            array('type' => 'source_contains', 'value' => 'int(', 'label' => 'Converts the input using int()'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
        'simulated_inputs' => array('20'),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does int("25") return?', 'options' => array('The string "25"', 'The whole number 25', 'An error, since "25" is text', 'The float 25.0'), 'answer' => 1, 'explanation' => 'int() converts a numeric string into a real integer.'),
        array('id' => 'q2', 'text' => 'Why would int(input("Age: ")) + 5 fail without the int()?', 'options' => array('It would not fail — Python auto-converts', 'input() returns a string, and you cannot add a number directly to a string', 'input() only accepts numbers', 'The + operator does not work in Python'), 'answer' => 1, 'explanation' => 'Without converting first, you would be trying to add a number to a string, which Python does not allow.'),
        array('id' => 'q3', 'text' => 'What does str(95) return?', 'options' => array('The integer 95', 'The string "95"', 'An error', 'The float 95.0'), 'answer' => 1, 'explanation' => 'str() converts a number into its text (string) representation.'),
        array('id' => 'q4', 'text' => 'Which function converts a string like "3.14" into a decimal number?', 'options' => array('int()', 'str()', 'float()', 'bool()'), 'answer' => 2, 'explanation' => 'float() converts a numeric string with a decimal point into a real float value.'),
        array('id' => 'q5', 'text' => 'What does age = int(input("Age: ")) do in one line?', 'options' => array('Only prompts the user, without storing anything', 'Prompts the user, then converts and stores their answer as an integer', 'Converts age to a string', 'Causes a syntax error — input() and int() cannot be combined'), 'answer' => 1, 'explanation' => 'Combining input() and int() in one line reads the input and immediately converts it to an integer.'),
        array('id' => 'q6', 'text' => 'Which conversion function would you use to safely join a number with text using +?', 'options' => array('int()', 'str()', 'float()', 'input()'), 'answer' => 1, 'explanation' => 'str() converts a number to text so it can be joined with other strings using +.'),
    ),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PRP01_UNIT1_BUILD') && MTTI_RUN_PRP01_UNIT1_BUILD) {
    global $wpdb;
    $units_table = $wpdb->prefix . 'mtti_course_units';
    $lessons_table = $wpdb->prefix . 'mtti_lessons';

    // 1. Soft-delete the 3 old units. unit_code is UNIQUE regardless of
    // deleted_at (same collision hit during the CAG-01 cleanup), and the
    // new units reuse the PRP-01-00N codes, so free the codes first.
    $wpdb->query("UPDATE {$units_table} SET status='Inactive', deleted_at=NOW(), unit_code=CONCAT(unit_code,'-DELETED') WHERE course_id=9 AND unit_id IN (80,81,82)");
    echo "Soft-deleted 3 old units\n";

    // 2. Create the 6 new units
    $unit_ids = array();
    foreach ($new_units as $u) {
        $wpdb->insert($units_table, array(
            'course_id'   => 9,
            'unit_code'   => $u['code'],
            'unit_name'   => $u['name'],
            'order_number'=> $u['order'],
            'module_id'   => 0,
            'is_active'   => 1,
            'status'      => 'Active',
        ));
        $unit_ids[$u['order']] = (int) $wpdb->insert_id;
        echo "Created unit '{$u['name']}' unit_id={$wpdb->insert_id}\n";
    }
    $unit1_id = $unit_ids[1];

    // 3. Author Unit 1's 7 topics x 5 lesson types
    $order = 10;
    foreach ($topics as $topic) {
        $tag = 'Python Fundamentals · ' . $topic['title'];

        // Overview
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit1_id,
            'title' => $topic['title'] . ' — Overview',
            'content' => mtti_objectives_fragment($topic['overview']['intro'], $topic['overview']['points']),
            'content_type' => 'objectives', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 1,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        echo "  Overview lesson_id={$wpdb->insert_id}: {$topic['title']}\n";
        $order++;

        // Video (placeholder — existing render code shows "Video Coming Soon")
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit1_id,
            'title' => $topic['title'] . ' — Video',
            'content' => null, 'content_type' => 'video', 'content_url' => null,
            'interactive_role' => null,
            'order_number' => $order, 'release_week' => 1,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        // Lesson (theory)
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit1_id,
            'title' => $topic['title'] . ' — Lesson',
            'content' => mtti_lesson_theory_html(array('title' => $topic['title'], 'lesson_tag' => $tag, 'sections' => $topic['lesson']['sections'])),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 1,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        // Practical (real Python lab via PyLab)
        $practical_config = array(
            'title' => $topic['title'] . ' — Practical',
            'lesson_tag' => $tag,
            'brief' => $topic['practical']['brief'],
            'starter' => $topic['practical']['starter'],
            'checks' => $topic['practical']['checks'],
            'pyodide_version' => 'v0.26.4',
        );
        if (!empty($topic['practical']['simulated_inputs'])) {
            $practical_config['simulated_inputs'] = $topic['practical']['simulated_inputs'];
        }
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit1_id,
            'title' => $topic['title'] . ' — Practical',
            'content' => mtti_python_lab_html($practical_config),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 1,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        // Quiz (via template splice — reuse a proven quiz engine)
        $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$lessons_table} WHERE lesson_id = %d", 460)); // WDD-01 quiz, {id,text,options,answer,explanation} convention
        $quiz_html = mtti_build_final_quiz_html($template, $topic['title'] . ' — Quiz', $topic['quiz']);
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit1_id,
            'title' => $topic['title'] . ' — Quiz',
            'content' => $quiz_html,
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 1,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $order = ceil($order / 10) * 10; // round up to next multiple of 10 for the next topic
        echo "Finished topic: {$topic['title']}\n";
    }

    echo "Unit 1 build complete.\n";

    // 5. Archive superseded content — soft-delete only, never hard-delete
    // real content (CAG-01 precedent). Old broad lessons whose content was
    // mined as source material for Unit 1, the 2 PDF placeholders, the
    // stray unrelated "POS to Production" lesson, and an old quiz (36).
    $archive_ids = array(8, 10, 14, 17, 19, 20, 24, 36, 51, 83, 84, 92);
    $placeholders = implode(',', array_fill(0, count($archive_ids), '%d'));
    $wpdb->query($wpdb->prepare(
        "UPDATE {$lessons_table} SET status='Inactive', deleted_at=NOW() WHERE course_id=9 AND lesson_id IN ({$placeholders})",
        $archive_ids
    ));
    echo "Archived " . count($archive_ids) . " old lessons\n";

    // Genuinely disposable test data — hard-delete is correct here.
    $wpdb->query("DELETE FROM {$lessons_table} WHERE lesson_id = 1423 AND title LIKE 'ZZZ_TEST_DELETE_ME%'");
    echo "Hard-deleted throwaway test lesson 1423\n";
}

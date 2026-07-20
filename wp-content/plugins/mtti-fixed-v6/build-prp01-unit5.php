<?php
/**
 * PRP-01 (Programming/Coding Principles) curriculum rebuild — Unit 5:
 * Error Handling and File I/O. Same granular pattern as Units 1-4.
 *
 * File I/O practicals rely on Pyodide's built-in in-memory filesystem
 * (MEMFS) — open()/write()/read() work exactly like real Python within
 * a single run, no backend or real disk access needed.
 *
 * Usage: wp eval-file build-prp01-unit5.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

require_once __DIR__ . '/build-prp01-unit4.php'; // cascades: mtti_lesson_theory_html(), mtti_python_lab_html(), mtti_objectives_fragment(), mtti_build_final_quiz_html()

$unit5_topics = array();

// Topic 1 — Understanding Errors and Exceptions
$unit5_topics[] = array(
    'title' => 'Understanding Errors and Exceptions',
    'overview' => array(
        'intro' => "Every program eventually hits something unexpected — this lesson covers the different kinds of errors Python can run into, and a first, simple way to guard against them.",
        'points' => array("The difference between a syntax error and a runtime error (exception)", "Common built-in exceptions: ValueError, ZeroDivisionError, TypeError", "Guarding against a foreseeable error with a plain if check"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Syntax Errors vs Runtime Errors', 'body_html' => "<p>A <strong>syntax error</strong> means Python couldn't even understand your code — it happens before the program runs at all (like a missing colon). A <strong>runtime error</strong>, or <strong>exception</strong>, happens while the code is executing, when something goes wrong that Python couldn't have caught in advance.</p>"),
            array('heading' => 'Common Built-in Exceptions', 'body_html' => "<p>Python has many built-in exception types for different problems: <code>ValueError</code> (a value is the wrong kind, e.g. converting text that isn't a number), <code>ZeroDivisionError</code> (dividing by zero), and <code>TypeError</code> (an operation used on the wrong data type).</p>", 'code' => "print(5 / 0)          # ZeroDivisionError: division by zero\nage = int(\"twenty\")   # ValueError: invalid literal for int()\nresult = \"5\" + 5       # TypeError: can only concatenate str to str"),
            array('heading' => 'A Simple Guard: Checking Before Acting', 'body_html' => "<p>The simplest way to avoid a foreseeable error is to check for it first with an if statement, before the risky operation runs. This works for simple, predictable cases — the next lesson introduces a more general tool for handling errors.</p>", 'code' => "a = 10\nb = 0\nif b != 0:\n    print(a / b)\nelse:\n    print(\"Cannot divide by zero\")"),
        ),
    ),
    'practical' => array(
        'brief' => "Given a = 10 and b = 0, write an if statement that checks whether b is 0 before dividing — print \"Cannot divide by zero\" if so, otherwise print a / b.",
        'starter' => "a = 10\nb = 0\n\n# check if b is 0 before dividing; print the right message either way\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Cannot divide by zero', 'label' => 'Correctly guards against dividing by zero'),
            array('type' => 'source_contains', 'value' => 'if', 'label' => 'Uses an if statement to check first'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What is a syntax error?', 'options' => array('An error that happens while the code is running', 'An error where Python cannot even understand the code, caught before it runs', 'A type of exception you can catch with try/except', 'An error that only happens with numbers'), 'answer' => 1, 'explanation' => "A syntax error means the code doesn't follow Python's grammar rules — it's caught before execution even starts."),
        array('id' => 'q2', 'text' => 'What is a runtime error (exception)?', 'options' => array('An error caught before the program runs', 'A problem that occurs while the program is executing', 'A typo in a variable name only', 'An error that never actually happens'), 'answer' => 1, 'explanation' => 'A runtime error, or exception, happens during execution — the code was valid Python, but something went wrong while running.'),
        array('id' => 'q3', 'text' => 'What exception does 5 / 0 raise?', 'options' => array('ValueError', 'TypeError', 'ZeroDivisionError', 'NameError'), 'answer' => 2, 'explanation' => 'Dividing by zero raises a ZeroDivisionError.'),
        array('id' => 'q4', 'text' => 'What exception does int("twenty") raise?', 'options' => array('ZeroDivisionError', 'ValueError', 'TypeError', 'No exception — it works fine'), 'answer' => 1, 'explanation' => '"twenty" cannot be converted to an int, so Python raises a ValueError.'),
        array('id' => 'q5', 'text' => 'What exception does "5" + 5 raise?', 'options' => array('ValueError', 'ZeroDivisionError', 'TypeError', 'None — Python converts automatically'), 'answer' => 2, 'explanation' => "Python doesn't automatically mix strings and numbers with + — this raises a TypeError."),
        array('id' => 'q6', 'text' => 'Why check `if b != 0:` before dividing by b?', 'options' => array('It has no real effect', 'It prevents a foreseeable ZeroDivisionError by avoiding the risky operation entirely', 'It converts b to a string', 'It is required syntax for all division'), 'answer' => 1, 'explanation' => 'Checking the condition first avoids the division ever running when it would fail.'),
    ),
);

// Topic 2 — try/except Basics
$unit5_topics[] = array(
    'title' => 'try/except Basics',
    'overview' => array(
        'intro' => "Checking with if only works when you can predict every possible problem in advance — this lesson covers try/except, Python's general-purpose tool for handling errors gracefully.",
        'points' => array("Writing a try/except block", "Catching a specific exception type", "Why try/except stops a program from crashing"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The try/except Block', 'body_html' => "<p>Code inside a <code>try</code> block is attempted normally. If an exception happens anywhere inside it, Python immediately jumps to the matching <code>except</code> block instead of crashing the whole program.</p>", 'code' => "try:\n    result = 10 / 0\n    print(result)\nexcept:\n    print(\"Something went wrong\")"),
            array('heading' => 'Catching a Specific Exception', 'body_html' => "<p>It's better practice to catch a specific exception type — this makes clear exactly what kind of problem you're handling, and avoids silently hiding unrelated bugs.</p>", 'code' => "try:\n    age = int(input(\"Enter your age: \"))\n    print(\"Next year you'll be\", age + 1)\nexcept ValueError:\n    print(\"That's not a valid number\")"),
            array('heading' => 'If No Exception Occurs', 'body_html' => "<p>If the code inside <code>try</code> runs without any error, the <code>except</code> block is simply skipped entirely — it only runs when a matching exception actually happens.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "Ask the user for their age using input(), wrapped in a try/except. If the conversion to int() succeeds, print \"Next year you'll be\" followed by age + 1. If they type something that isn't a number, catch the ValueError and print \"That's not a valid number\".",
        'starter' => "# ask for age with input(), inside a try/except ValueError\n# if valid: print \"Next year you'll be\", age + 1\n# if invalid: print \"That's not a valid number\"\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => "That's not a valid number", 'label' => 'Catches the invalid input gracefully'),
            array('type' => 'source_contains', 'value' => 'except', 'label' => 'Uses except to handle the error'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without crashing, even on bad input'),
        ),
        'simulated_inputs' => array('abc'),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does code inside a try block do?', 'options' => array('It never runs', 'It is attempted — if it raises an exception, Python jumps to the matching except block', 'It always raises an error on purpose', 'It replaces the need for functions'), 'answer' => 1, 'explanation' => 'try attempts the code normally; if an exception occurs, control passes to except instead of crashing.'),
        array('id' => 'q2', 'text' => 'What happens if the code inside try runs with no errors?', 'options' => array('The except block still runs afterward', 'The except block is skipped entirely', 'Python raises an error anyway', 'The program stops immediately'), 'answer' => 1, 'explanation' => 'except only runs if an exception actually occurs — otherwise it is simply skipped.'),
        array('id' => 'q3', 'text' => 'Why catch a specific exception type like ValueError instead of a bare except:?', 'options' => array('Bare except is not allowed in Python', 'It makes clear exactly what problem is being handled, without silently hiding unrelated bugs', 'It runs faster', 'There is no real difference'), 'answer' => 1, 'explanation' => 'Catching a specific exception type is more precise and avoids accidentally swallowing unrelated errors.'),
        array('id' => 'q4', 'text' => 'What is the main benefit of try/except over just letting an error happen?', 'options' => array('It prevents the program from crashing and lets you handle the problem gracefully', 'It makes the code run faster', 'It removes the need for variables', 'It automatically fixes the underlying bug'), 'answer' => 0, 'explanation' => "try/except lets a program respond to a problem instead of stopping entirely with an unhandled crash."),
        array('id' => 'q5', 'text' => 'In `try: age = int(input(...)) except ValueError: print(...)`, when does the except block run?', 'options' => array('Every single time, regardless of input', 'Only if int() fails to convert the input, e.g. the user typed letters', 'Only if the user types a number', 'Never — this code is written wrong'), 'answer' => 1, 'explanation' => 'The except ValueError block only runs when the int() conversion actually raises a ValueError.'),
        array('id' => 'q6', 'text' => 'Which keyword marks the block of code that might raise an exception?', 'options' => array('except', 'catch', 'try', 'raise'), 'answer' => 2, 'explanation' => "Python uses try (not catch, which is used in some other languages) to mark the risky code block."),
    ),
);

// Topic 3 — else, finally, and Multiple except Blocks
$unit5_topics[] = array(
    'title' => 'else, finally, and Multiple except Blocks',
    'overview' => array(
        'intro' => "A single try/except often isn't enough for real code — this lesson covers handling more than one possible error, and running code only on success or always at the end.",
        'points' => array("Using else to run code only if no exception occurred", "Using finally to run code no matter what happens", "Catching different exception types with separate except blocks"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The else Clause', 'body_html' => "<p>An optional <code>else</code> block after try/except runs only if the try block completed with <strong>no</strong> exception — a clean way to separate \"what happens on success\" from the risky code itself.</p>", 'code' => "try:\n    num = int(\"25\")\nexcept ValueError:\n    print(\"Invalid number\")\nelse:\n    print(\"Conversion succeeded:\", num)"),
            array('heading' => 'The finally Clause', 'body_html' => "<p>An optional <code>finally</code> block always runs, whether an exception happened or not — useful for cleanup steps that must happen either way.</p>", 'code' => "try:\n    num = int(\"25\")\nexcept ValueError:\n    print(\"Invalid number\")\nfinally:\n    print(\"Done attempting conversion\")"),
            array('heading' => 'Multiple except Blocks', 'body_html' => "<p>A try statement can have several except blocks, each catching a different exception type — Python checks them in order and runs the first one that matches, the same way elif works.</p>", 'code' => "try:\n    result = 10 / int(input(\"Enter a number: \"))\nexcept ValueError:\n    print(\"That's not a number\")\nexcept ZeroDivisionError:\n    print(\"Cannot divide by zero\")\nelse:\n    print(\"Result:\", result)"),
        ),
    ),
    'practical' => array(
        'brief' => "Read a number with input(), then divide 100 by it inside a try block. Catch ValueError if the input isn't a number (print \"That's not a number\"), and catch ZeroDivisionError if they enter 0 (print \"Cannot divide by zero\"). If it succeeds, use else to print the result.",
        'starter' => "# read a number with input(), divide 100 by it inside try\n# except ValueError: print \"That's not a number\"\n# except ZeroDivisionError: print \"Cannot divide by zero\"\n# else: print the result\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Cannot divide by zero', 'label' => 'Correctly catches ZeroDivisionError'),
            array('type' => 'source_contains', 'value' => 'except ZeroDivisionError', 'label' => 'Has a separate except block for ZeroDivisionError'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without crashing'),
        ),
        'simulated_inputs' => array('0'),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'When does the else block after try/except run?', 'options' => array('Only if an exception occurred', 'Only if the try block completed with no exception', 'Every time, regardless of exceptions', 'Never — else cannot follow try/except'), 'answer' => 1, 'explanation' => "The else block runs only when the try block succeeds without raising an exception."),
        array('id' => 'q2', 'text' => 'When does the finally block run?', 'options' => array('Only if an exception occurred', 'Only if no exception occurred', 'Always, whether or not an exception occurred', 'Never automatically'), 'answer' => 2, 'explanation' => 'finally always executes, making it useful for cleanup that must happen either way.'),
        array('id' => 'q3', 'text' => 'Can a try statement have more than one except block?', 'options' => array('No, only one is allowed', 'Yes — each can catch a different exception type', 'Only two are allowed, never more', 'Only if there is no else block'), 'answer' => 1, 'explanation' => 'Multiple except blocks let you handle different exception types with different responses.'),
        array('id' => 'q4', 'text' => 'If a ZeroDivisionError occurs, and there are separate except ValueError and except ZeroDivisionError blocks, which one runs?', 'options' => array('Both run', 'Neither runs', 'Only the except ZeroDivisionError block', 'Only the except ValueError block'), 'answer' => 2, 'explanation' => 'Python matches the exception to the specific except block that handles that exception type.'),
        array('id' => 'q5', 'text' => 'What is a good use for a finally block?', 'options' => array('Skipping the try block entirely', 'Cleanup code that must run whether the operation succeeded or failed', 'Catching every possible exception type at once', 'Replacing the need for except entirely'), 'answer' => 1, 'explanation' => 'finally is ideal for guaranteed cleanup steps, like closing a resource, regardless of the outcome.'),
        array('id' => 'q6', 'text' => 'In what order does Python check multiple except blocks?', 'options' => array('Randomly', 'From bottom to top', 'In the order they are written, top to bottom, running the first match', 'It checks all of them and runs every match'), 'answer' => 2, 'explanation' => 'Python checks except blocks in order, the same way elif branches are checked, and runs only the first match.'),
    ),
);

// Topic 4 — Raising Custom Exceptions
$unit5_topics[] = array(
    'title' => 'Raising Custom Exceptions',
    'overview' => array(
        'intro' => "Sometimes a value is technically valid Python, but still breaks your program's own rules — this lesson covers deliberately raising an exception yourself.",
        'points' => array("Using raise to trigger an exception on purpose", "Attaching a helpful message to a raised exception", "Capturing that message with except ... as e"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The raise Keyword', 'body_html' => "<p>Sometimes Python itself won't catch a problem, because the value is technically valid — but your program has its own rules. The <code>raise</code> keyword lets you trigger an exception on purpose when your own logic detects something wrong.</p>", 'code' => "def set_age(age):\n    if age < 0:\n        raise ValueError(\"Age cannot be negative\")\n    print(\"Age set to\", age)\n\nset_age(-5)   # raises ValueError: Age cannot be negative"),
            array('heading' => 'Catching a Raised Exception', 'body_html' => "<p>A raised exception can be caught by a try/except exactly like any built-in one. Writing <code>except ValueError as e</code> captures the exception's message in the variable <code>e</code>, so you can display it.</p>", 'code' => "try:\n    set_age(-5)\nexcept ValueError as e:\n    print(\"Error:\", e)\n# Output: Error: Age cannot be negative"),
        ),
    ),
    'practical' => array(
        'brief' => "Define a function set_score(score) that raises ValueError(\"Score must be between 0 and 100\") if score is below 0 or above 100, otherwise prints \"Score set to \" followed by the score. Call set_score(150) inside a try/except, catch ValueError as e, and print(\"Error:\", e).",
        'starter' => "def set_score(score):\n    # raise ValueError(\"Score must be between 0 and 100\") if score < 0 or score > 100\n    # otherwise print \"Score set to \" + str(score)\n\n\n# call set_score(150) inside a try/except, catch ValueError as e, print(\"Error:\", e)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Error: Score must be between 0 and 100', 'label' => 'Raises and catches the custom exception correctly'),
            array('type' => 'source_contains', 'value' => 'raise ValueError', 'label' => 'Uses raise to trigger the exception'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without crashing'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does the raise keyword do?', 'options' => array('Catches an exception', 'Deliberately triggers an exception', 'Prevents any exception from occurring', 'Defines a new function'), 'answer' => 1, 'explanation' => 'raise triggers an exception on purpose, based on your own program logic.'),
        array('id' => 'q2', 'text' => 'Why would a program raise its own exception, if the value is technically valid Python?', 'options' => array('It never should — raise is only for Python\'s own internal use', 'Because the value breaks the program\'s own business rules, even though Python itself has no complaint', 'To make the program run faster', 'To avoid using if statements'), 'answer' => 1, 'explanation' => "raise lets you enforce your program's own rules (like a score needing to be 0-100) that Python has no built-in way to check."),
        array('id' => 'q3', 'text' => 'What does except ValueError as e do?', 'options' => array('Ignores the exception completely', 'Catches the ValueError and stores its message in the variable e', 'Raises a new exception called e', 'Only works for built-in exceptions, never custom ones'), 'answer' => 1, 'explanation' => 'as e captures the caught exception object (including its message) into the variable e.'),
        array('id' => 'q4', 'text' => 'Given raise ValueError("Score must be between 0 and 100") is caught by except ValueError as e: print("Error:", e), what prints?', 'options' => array('Error: ValueError', 'Error: Score must be between 0 and 100', 'Just "Error:"', 'Nothing prints'), 'answer' => 1, 'explanation' => 'e holds the message passed to ValueError(), so it prints exactly that text.'),
        array('id' => 'q5', 'text' => 'Can a raised exception be caught by a normal try/except, the same as a built-in one?', 'options' => array('No, raised exceptions cannot be caught', 'Yes — raise creates a real exception that try/except handles the same way', 'Only if it is caught in the same function', 'Only for ValueError specifically'), 'answer' => 1, 'explanation' => 'An exception created with raise behaves exactly like any other exception once it is raised.'),
        array('id' => 'q6', 'text' => 'In def set_score(score): if score < 0 or score > 100: raise ValueError(...), what happens if score = 150?', 'options' => array('The function prints "Score set to 150"', 'The function raises ValueError, since 150 > 100', 'Nothing happens', 'Python automatically caps score at 100'), 'answer' => 1, 'explanation' => '150 is greater than 100, so the condition is True and the function raises the ValueError.'),
    ),
);

// Topic 5 — Reading and Writing Files
$unit5_topics[] = array(
    'title' => 'Reading and Writing Files',
    'overview' => array(
        'intro' => "Data doesn't have to disappear when a program ends — this lesson covers saving information to a file and reading it back.",
        'points' => array("Opening a file with open() and choosing a mode", "Using the with statement to open a file safely", "Writing to a file and reading its contents back"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Opening a File', 'body_html' => "<p><code>open(filename, mode)</code> opens a file. The mode controls what you can do with it: <code>\"w\"</code> write (creates or overwrites the file), <code>\"r\"</code> read (the default), and <code>\"a\"</code> append (adds to the end without erasing existing content).</p>"),
            array('heading' => 'The with Statement', 'body_html' => "<p>Using <code>with open(...) as f:</code> automatically closes the file for you once the indented block finishes — even if an error happens partway through. This is the standard, safer way to work with files in Python.</p>", 'code' => "with open(\"notes.txt\", \"w\") as f:\n    f.write(\"MTTI Programming Principles\\n\")\n    f.write(\"Unit 5: Error Handling and File I/O\\n\")"),
            array('heading' => 'Reading a File Back', 'body_html' => "<p>Opening the same file in read mode and calling <code>.read()</code> retrieves everything that was written to it.</p>", 'code' => "with open(\"notes.txt\", \"r\") as f:\n    content = f.read()\n    print(content)"),
        ),
    ),
    'practical' => array(
        'brief' => "Write the lines \"Student: Brian\" and \"Course: Programming Principles\" (each ending with a newline) to a file called student.txt using with open(). Then open student.txt again in read mode, read its contents, and print them.",
        'starter' => "# write \"Student: Brian\\n\" and \"Course: Programming Principles\\n\" to student.txt, using with open(\"student.txt\", \"w\") as f\n\n\n# open student.txt in read mode, read its contents, and print them\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Student: Brian', 'label' => 'Writes and reads back the first line correctly'),
            array('type' => 'output_contains', 'value' => 'Programming Principles', 'label' => 'Writes and reads back the second line correctly'),
            array('type' => 'source_contains', 'value' => 'with open', 'label' => 'Uses the with statement to handle the file'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does open("notes.txt", "w") do?', 'options' => array('Opens the file for reading only', 'Opens the file for writing, creating or overwriting it', 'Deletes the file', 'Appends to the file without erasing existing content'), 'answer' => 1, 'explanation' => '"w" mode opens a file for writing, creating it if it doesn\'t exist or overwriting it if it does.'),
        array('id' => 'q2', 'text' => 'What does the "a" mode do when opening a file?', 'options' => array('Overwrites the whole file', 'Appends new content to the end, keeping existing content', 'Opens the file for reading only', 'Deletes the file'), 'answer' => 1, 'explanation' => '"a" (append) mode adds new content after whatever is already in the file.'),
        array('id' => 'q3', 'text' => 'What is the benefit of using with open(...) as f: instead of open() alone?', 'options' => array('It makes the file read-only', 'It automatically closes the file when the block finishes, even if an error occurs', 'It is required to write any text at all', 'There is no real benefit'), 'answer' => 1, 'explanation' => 'The with statement guarantees the file gets closed properly, which is safer than remembering to call close() manually.'),
        array('id' => 'q4', 'text' => 'Which method reads the entire contents of an open file into a string?', 'options' => array('f.write()', 'f.open()', 'f.read()', 'f.close()'), 'answer' => 2, 'explanation' => 'f.read() reads the whole file content as one string.'),
        array('id' => 'q5', 'text' => 'What is the default mode if you call open("notes.txt") with no mode specified?', 'options' => array('Write mode ("w")', 'Read mode ("r")', 'Append mode ("a")', 'It raises an error, mode is always required'), 'answer' => 1, 'explanation' => "Python defaults to read mode (\"r\") if no mode argument is given."),
        array('id' => 'q6', 'text' => 'Which method writes text to an open file?', 'options' => array('f.read()', 'f.print()', 'f.write()', 'f.save()'), 'answer' => 2, 'explanation' => 'f.write(text) writes the given text into the open file.'),
    ),
);

// Topic 6 — Mini-Project: Safe Grade Logger
$unit5_topics[] = array(
    'title' => 'Mini-Project: Safe Grade Logger',
    'overview' => array(
        'intro' => "This unit's capstone — combining error handling with file I/O to build something close to real, working software: a program that safely logs valid data and skips bad entries instead of crashing.",
        'points' => array("Using try/except inside a loop to skip bad data without stopping the whole program", "Writing only the valid results to a file", "Reviewing everything covered in this unit"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Skipping Bad Data Instead of Crashing', 'body_html' => "<p>Real data is messy — a list of \"scores\" might contain a typo or invalid entry. Wrapping the risky conversion in try/except <em>inside</em> a loop lets the program skip just the bad entry and keep processing the rest, instead of crashing entirely.</p>", 'code' => "scores_input = [\"85\", \"92\", \"abc\", \"76\"]\nvalid_scores = []\n\nfor entry in scores_input:\n    try:\n        valid_scores.append(int(entry))\n    except ValueError:\n        print(\"Skipping invalid entry:\", entry)"),
            array('heading' => 'Saving the Valid Results to a File', 'body_html' => "<p>Once the data is cleaned, it can be written to a file for permanent storage — combining this unit's two big topics into one small but realistic program.</p>", 'code' => "with open(\"scores_log.txt\", \"w\") as f:\n    for score in valid_scores:\n        f.write(str(score) + \"\\n\")\n\nprint(\"Saved\", len(valid_scores), \"valid scores\")"),
        ),
    ),
    'practical' => array(
        'brief' => "Given scores_input = [\"85\", \"92\", \"abc\", \"76\"], loop through it. Use try/except to convert each entry to int() — skip invalid ones, printing \"Skipping invalid entry: \" followed by the entry. Collect the valid numbers into a list, write them to a file called scores_log.txt (one per line), and print \"Saved \" followed by how many valid scores were saved.",
        'starter' => "scores_input = [\"85\", \"92\", \"abc\", \"76\"]\nvalid_scores = []\n\n# loop through scores_input; try converting each to int(), append valid ones to valid_scores\n# except ValueError: print \"Skipping invalid entry: \" + entry\n\n\n# write valid_scores to scores_log.txt, one per line, using with open()\n\n# print \"Saved \" + number of valid scores + \" valid scores\"\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Skipping invalid entry: abc', 'label' => 'Correctly skips the invalid entry'),
            array('type' => 'output_contains', 'value' => 'Saved 3 valid scores', 'label' => 'Correctly counts and reports the valid scores'),
            array('type' => 'source_contains', 'value' => 'except ValueError', 'label' => 'Uses try/except to handle bad entries'),
            array('type' => 'source_contains', 'value' => 'with open', 'label' => 'Writes the results to a file'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without crashing'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'Why put the try/except inside the loop, rather than around the whole loop?', 'options' => array('It has no effect either way', 'So one bad entry only skips itself, instead of stopping the loop from processing the rest', 'It makes the loop run faster', 'Python requires try/except inside every loop'), 'answer' => 1, 'explanation' => 'Placing try/except inside the loop body means each iteration is handled independently — one failure doesn\'t abort the others.'),
        array('id' => 'q2', 'text' => 'For scores_input = ["85", "92", "abc", "76"], how many entries convert successfully to int?', 'options' => array('2', '3', '4', '1'), 'answer' => 1, 'explanation' => '"85", "92", and "76" convert fine; "abc" fails — 3 valid scores.'),
        array('id' => 'q3', 'text' => 'What does valid_scores.append(int(entry)) do inside the try block?', 'options' => array('Converts entry to a string and prints it', 'Converts entry to an int and adds it to the valid_scores list', 'Removes entry from scores_input', 'Writes entry directly to a file'), 'answer' => 1, 'explanation' => 'int(entry) converts the value, and .append() adds the result to the valid_scores list.'),
        array('id' => 'q4', 'text' => 'Why write str(score) rather than score directly when saving to the file?', 'options' => array('str() is not actually needed here', 'File writing requires a string, and score is a number (int), so it must be converted first', 'str() deletes the number', 'It has no effect either way'), 'answer' => 1, 'explanation' => "f.write() only accepts strings — an int must be converted with str() first, or Python raises a TypeError."),
        array('id' => 'q5', 'text' => 'Which two topics from this unit does the mini-project combine?', 'options' => array('Only loops', 'Error handling (try/except) and file I/O (open/write)', 'Only functions', 'Only variables'), 'answer' => 1, 'explanation' => "The project combines try/except for safely processing messy data with file writing to save the clean results."),
        array('id' => 'q6', 'text' => 'What best summarizes what this unit covered overall?', 'options' => array('Only printing text to the screen', 'Handling errors gracefully with try/except/else/finally and raise, and reading/writing files', 'Only mathematical operators', 'Only list methods'), 'answer' => 1, 'explanation' => "This unit's core theme is robustness: catching and responding to errors instead of crashing, and persisting data to files."),
    ),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PRP01_UNIT5_BUILD') && MTTI_RUN_PRP01_UNIT5_BUILD) {
    global $wpdb;
    $lessons_table = $wpdb->prefix . 'mtti_lessons';
    $unit5_id = 152; // Error Handling and File I/O, created during the Unit 1 pass

    $order = 400;
    foreach ($unit5_topics as $topic) {
        $tag = 'Error Handling and File I/O · ' . $topic['title'];

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit5_id,
            'title' => $topic['title'] . ' — Overview',
            'content' => mtti_objectives_fragment($topic['overview']['intro'], $topic['overview']['points']),
            'content_type' => 'objectives', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 5,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        echo "  Overview lesson_id={$wpdb->insert_id}: {$topic['title']}\n";
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit5_id,
            'title' => $topic['title'] . ' — Video',
            'content' => null, 'content_type' => 'video', 'content_url' => null,
            'interactive_role' => null,
            'order_number' => $order, 'release_week' => 5,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit5_id,
            'title' => $topic['title'] . ' — Lesson',
            'content' => mtti_lesson_theory_html(array('title' => $topic['title'], 'lesson_tag' => $tag, 'sections' => $topic['lesson']['sections'])),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 5,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

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
            'course_id' => 9, 'unit_id' => $unit5_id,
            'title' => $topic['title'] . ' — Practical',
            'content' => mtti_python_lab_html($practical_config),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 5,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$lessons_table} WHERE lesson_id = %d", 460));
        $quiz_html = mtti_build_final_quiz_html($template, $topic['title'] . ' — Quiz', $topic['quiz']);
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit5_id,
            'title' => $topic['title'] . ' — Quiz',
            'content' => $quiz_html,
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 5,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $order = ceil($order / 10) * 10;
        echo "Finished topic: {$topic['title']}\n";
    }

    echo "Unit 5 build complete.\n";
}

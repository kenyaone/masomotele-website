<?php
/**
 * PRP-01 (Programming/Coding Principles) curriculum rebuild — Unit 2:
 * Control Flow. Same granular pattern as Unit 1 (Overview + Video +
 * Lesson + Practical + Quiz per topic), building on Unit 1's variables/
 * types/operators/input() foundation.
 *
 * Usage: wp eval-file build-prp01-unit2.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

require_once __DIR__ . '/build-prp01-unit1.php'; // mtti_lesson_theory_html(), mtti_python_lab_html(), mtti_objectives_fragment(), mtti_build_final_quiz_html()

$unit2_topics = array();

// Topic 1 — Conditional Statements: if, elif, else
$unit2_topics[] = array(
    'title' => 'Conditional Statements: if, elif, else',
    'overview' => array(
        'intro' => "Real programs need to make decisions — this lesson covers how Python branches between different blocks of code based on a condition.",
        'points' => array("Writing if statements and Python's indentation rules", "Adding elif for multiple branches and else for the fallback", "Combining conditions with the comparison operators from Unit 1"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The if Statement', 'body_html' => "<p>An <code>if</code> statement runs a block of code only when its condition is <code>True</code>. Python uses <strong>indentation</strong> (not curly braces) to mark which lines belong to the block — this is a strict rule, not just a style choice.</p>", 'code' => "age = 20\nif age >= 18:\n    print(\"You are an adult\")"),
            array('heading' => 'Adding elif and else', 'body_html' => "<p><code>elif</code> (\"else if\") lets you check additional conditions if the first one was False, and <code>else</code> catches everything else. Python checks each condition in order and runs only the first one that matches.</p>", 'code' => "score = 75\nif score >= 80:\n    print(\"Grade: A\")\nelif score >= 60:\n    print(\"Grade: B\")\nelse:\n    print(\"Grade: C\")\n# Output: Grade: B"),
            array('heading' => 'Using Comparison Operators in Conditions', 'body_html' => "<p>Any expression that evaluates to <code>True</code> or <code>False</code> can be an <code>if</code> condition — this is exactly where the comparison operators (<code>== != &gt; &lt; &gt;= &lt;=</code>) from Unit 1 come in.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "A student's exam score is stored in the variable score (72). Write an if/elif/else that prints their grade: 80+ is 'A', 60-79 is 'B', below 60 is 'C'.",
        'starter' => "score = 72\n\n# check the score and print the grade (A, B, or C)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'B', 'label' => "Prints the correct grade for a score of 72"),
            array('type' => 'source_contains', 'value' => 'elif', 'label' => 'Uses elif for the middle case'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does Python use to mark which lines belong to an if block?', 'options' => array('Curly braces {}', 'Indentation', 'Semicolons', 'Parentheses'), 'answer' => 1, 'explanation' => 'Python uses indentation, not braces, to define code blocks — this is enforced, not optional.'),
        array('id' => 'q2', 'text' => 'What does elif mean?', 'options' => array('"Else if" — check another condition if the previous one was False', 'A loop keyword', 'A way to end an if statement', 'A comparison operator'), 'answer' => 0, 'explanation' => 'elif lets you check an additional condition only if the earlier if/elif conditions were False.'),
        array('id' => 'q3', 'text' => 'Given score = 75, if score >= 80: print("A") elif score >= 60: print("B") else: print("C") — what prints?', 'options' => array('A', 'B', 'C', 'Nothing'), 'answer' => 1, 'explanation' => '75 is not >= 80, but it is >= 60, so the elif branch runs, printing B.'),
        array('id' => 'q4', 'text' => 'How many branches of an if/elif/else chain can run for a single execution?', 'options' => array('All of them', 'None, ever', 'At most one — the first matching condition', 'Exactly two'), 'answer' => 2, 'explanation' => 'Python checks conditions in order and runs only the first block whose condition is True (or the else block if none match).'),
        array('id' => 'q5', 'text' => 'Is an else block required after an if statement?', 'options' => array('Yes, always', 'No, it is optional', 'Only if there is an elif', 'Only for loops'), 'answer' => 1, 'explanation' => 'else is optional — a plain if (or if/elif) with no else is completely valid.'),
        array('id' => 'q6', 'text' => 'Which of these is a valid if condition?', 'options' => array('if age >= 18:', 'if (age >= 18)', 'if age >= 18 then', 'if age => 18:'), 'answer' => 0, 'explanation' => "Python's if syntax is `if <condition>:` — no parentheses or `then` keyword required, and the operator is `>=` not `=>`."),
    ),
);

// Topic 2 — Boolean Logic in Conditions
$unit2_topics[] = array(
    'title' => 'Boolean Logic in Conditions',
    'overview' => array(
        'intro' => "Real decisions often depend on more than one thing being true — this lesson covers combining conditions and a Python quirk called truthy/falsy values.",
        'points' => array("Combining conditions with and, or, and not", "What 'truthy' and 'falsy' values are in Python", "Nesting if statements inside each other"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Combining Conditions', 'body_html' => "<p>The logical operators from Unit 1 — <code>and</code>, <code>or</code>, <code>not</code> — are what you use to combine multiple conditions in one if statement.</p>", 'code' => "age = 25\nhas_id = True\nif age >= 18 and has_id:\n    print(\"Entry allowed\")"),
            array('heading' => 'Truthy and Falsy Values', 'body_html' => "<p>Python doesn't only accept <code>True</code>/<code>False</code> in an if condition — it treats certain values as \"falsy\" (counted as False): <code>0</code>, <code>0.0</code>, an empty string <code>\"\"</code>, <code>None</code>, and empty containers like <code>[]</code>. Everything else is \"truthy\".</p>", 'code' => "name = \"\"\nif name:\n    print(\"Hello, \" + name)\nelse:\n    print(\"No name given\")\n# Output: No name given — an empty string is falsy"),
            array('heading' => 'Nested if Statements', 'body_html' => "<p>An if statement can contain another if statement inside it — useful when a second decision only makes sense after the first condition is already True.</p>", 'code' => "age = 20\nhas_id = True\nif age >= 18:\n    if has_id:\n        print(\"Entry allowed\")\n    else:\n        print(\"ID required\")"),
        ),
    ),
    'practical' => array(
        'brief' => "A shop lets a customer in only if they are 18 or older AND have a membership card. Given age (20) and membership (True), print 'Welcome' if both conditions are met, otherwise print 'Sorry, entry denied'.",
        'starter' => "age = 20\nmembership = True\n\n# check both conditions and print the right message\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Welcome', 'label' => 'Prints Welcome when both conditions are true'),
            array('type' => 'source_contains', 'value' => 'and', 'label' => 'Combines both conditions with and'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'For "age >= 18 and has_id" to be True, what must hold?', 'options' => array('Only one of the two needs to be True', 'Both conditions must be True', 'Neither needs to be True', 'age must equal 18 exactly'), 'answer' => 1, 'explanation' => 'and requires both sides to be True.'),
        array('id' => 'q2', 'text' => 'Which of these values is "falsy" in Python?', 'options' => array('"hello"', '1', '""  (empty string)', '[1, 2, 3]'), 'answer' => 2, 'explanation' => 'An empty string is one of Python\'s falsy values, along with 0, None, and empty containers.'),
        array('id' => 'q3', 'text' => 'What does `if name:` do when name = ""?', 'options' => array('Raises an error', 'Runs the if block, since name exists', 'Skips the if block, since an empty string is falsy', 'Always runs, regardless of the value'), 'answer' => 2, 'explanation' => 'An empty string is falsy, so the if block is skipped.'),
        array('id' => 'q4', 'text' => 'What is a nested if statement?', 'options' => array('Two unrelated if statements in different functions', 'An if statement written entirely on one line', 'An if statement placed inside the block of another if statement', 'A loop disguised as an if statement'), 'answer' => 2, 'explanation' => 'Nesting means placing one if statement inside the body of another.'),
        array('id' => 'q5', 'text' => 'What does the `not` operator do?', 'options' => array('Combines two conditions, both must be True', 'Combines two conditions, only one must be True', 'Reverses a Boolean value (True becomes False and vice versa)', 'Compares two numbers for equality'), 'answer' => 2, 'explanation' => 'not inverts a Boolean — not True is False, and not False is True.'),
        array('id' => 'q6', 'text' => 'Which value is considered truthy?', 'options' => array('0', 'None', '"" ', '"MTTI"'), 'answer' => 3, 'explanation' => 'A non-empty string like "MTTI" is truthy; 0, None, and "" are all falsy.'),
    ),
);

// Topic 3 — While Loops
$unit2_topics[] = array(
    'title' => 'While Loops',
    'overview' => array(
        'intro' => "Sometimes code needs to repeat until a condition changes — this lesson covers the while loop, Python's condition-controlled repetition tool.",
        'points' => array("How a while loop repeats code while a condition stays True", "Why a loop variable must change, to avoid an infinite loop", "The accumulator pattern for running totals"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The while Loop', 'body_html' => "<p>A <code>while</code> loop repeats its block of code for as long as its condition stays <code>True</code>. Python re-checks the condition before every repeat.</p>", 'code' => "count = 1\nwhile count <= 5:\n    print(count)\n    count += 1\n# Output: 1 2 3 4 5, each on its own line"),
            array('heading' => 'Avoiding an Infinite Loop', 'body_html' => "<p>The loop variable (<code>count</code> above) must eventually make the condition False, or the loop never stops. Forgetting <code>count += 1</code> is one of the most common beginner mistakes — always double-check that something inside the loop moves you toward the stopping condition.</p>"),
            array('heading' => 'The Accumulator Pattern', 'body_html' => "<p>A very common use of while (and for) loops is building up a running total — a variable that starts at 0 and grows with each pass through the loop.</p>", 'code' => "total = 0\nnum = 1\nwhile num <= 5:\n    total += num\n    num += 1\nprint(total)  # 15 (1+2+3+4+5)"),
        ),
    ),
    'practical' => array(
        'brief' => "Use a while loop to calculate the sum of the numbers from 1 to 10 (inclusive). Print the final total.",
        'starter' => "total = 0\nnum = 1\n\n# write a while loop that adds num to total, for num from 1 to 10\n\nprint(total)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '55', 'label' => 'Prints the correct total (55)'),
            array('type' => 'source_contains', 'value' => 'while', 'label' => 'Uses a while loop'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'When does a while loop stop repeating?', 'options' => array('After exactly 10 repeats, always', 'As soon as its condition becomes False', 'Only when the program ends', 'Never, unless the computer is restarted'), 'answer' => 1, 'explanation' => 'A while loop keeps repeating until its condition evaluates to False.'),
        array('id' => 'q2', 'text' => 'What causes an infinite loop in a while statement?', 'options' => array('Using print() inside the loop', 'The loop condition never becomes False', 'Using += instead of =', 'Starting the loop variable at 0'), 'answer' => 1, 'explanation' => 'If nothing inside the loop changes the condition toward False, the loop repeats forever.'),
        array('id' => 'q3', 'text' => 'In `count = 1` then `while count <= 5: print(count); count += 1`, how many times does the loop run?', 'options' => array('4', '5', '6', 'Infinitely'), 'answer' => 1, 'explanation' => 'count goes 1,2,3,4,5 (each passes the <= 5 check) then becomes 6, which fails the check — 5 total runs.'),
        array('id' => 'q4', 'text' => 'What does count += 1 do?', 'options' => array('Sets count to 1', 'Adds 1 to count and stores the result back in count', 'Compares count to 1', 'Divides count by 1'), 'answer' => 1, 'explanation' => 'count += 1 is shorthand for count = count + 1.'),
        array('id' => 'q5', 'text' => 'What is the "accumulator pattern"?', 'options' => array('A way to stop a loop immediately', 'Using a variable that starts at a base value and builds up a running result across loop iterations', 'A type of if statement', 'A method for sorting numbers'), 'answer' => 1, 'explanation' => 'The accumulator pattern uses a variable (like total = 0) that accumulates a result as the loop repeats.'),
        array('id' => 'q6', 'text' => 'What does total = 0 followed by a loop that does total += num for num 1 to 5 leave total holding?', 'options' => array('5', '15', '0', '25'), 'answer' => 1, 'explanation' => '1+2+3+4+5 = 15.'),
    ),
);

// Topic 4 — For Loops and range()
$unit2_topics[] = array(
    'title' => 'For Loops and range()',
    'overview' => array(
        'intro' => "When you know exactly how many times to repeat something, a for loop is usually the cleaner tool — this lesson covers for loops and Python's range() function.",
        'points' => array("Writing a for loop with range()", "range()'s start, stop, and step arguments", "Looping over a string, character by character"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The for Loop with range()', 'body_html' => "<p>A <code>for</code> loop paired with <code>range()</code> repeats a fixed number of times. <code>range(5)</code> produces the numbers 0, 1, 2, 3, 4 — it starts at 0 and stops <em>before</em> the number given.</p>", 'code' => "for i in range(5):\n    print(i)\n# Output: 0 1 2 3 4"),
            array('heading' => "range()'s Arguments", 'body_html' => "<p><code>range()</code> can take up to three arguments: <code>range(start, stop)</code> counts from start up to (not including) stop, and <code>range(start, stop, step)</code> adds a custom step size.</p>", 'code' => "for i in range(1, 11):\n    print(i)\n# Output: 1 through 10\n\nfor i in range(0, 10, 2):\n    print(i)\n# Output: 0 2 4 6 8"),
            array('heading' => 'Looping Over a String', 'body_html' => "<p>A for loop can iterate directly over the characters of a string, without needing range() at all.</p>", 'code' => "for letter in \"MTTI\":\n    print(letter)\n# Output: M, T, T, I — each on its own line"),
        ),
    ),
    'practical' => array(
        'brief' => "Use a for loop with range() to print the 5 times table, from 5x1 up to 5x10, one line each in the format '5 x 1 = 5'.",
        'starter' => "# use a for loop with range(1, 11) to print the 5 times table\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '5 x 10 = 50', 'label' => 'Prints the correct final line of the table'),
            array('type' => 'source_contains', 'value' => 'range', 'label' => 'Uses range()'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What numbers does range(5) produce?', 'options' => array('1, 2, 3, 4, 5', '0, 1, 2, 3, 4', '0, 1, 2, 3, 4, 5', '1, 2, 3, 4'), 'answer' => 1, 'explanation' => 'range(5) starts at 0 and stops before 5, giving 0,1,2,3,4.'),
        array('id' => 'q2', 'text' => 'What does range(1, 11) produce?', 'options' => array('1 through 10', '1 through 11', '0 through 10', '0 through 11'), 'answer' => 0, 'explanation' => 'range(1, 11) starts at 1 and stops before 11, giving 1 through 10.'),
        array('id' => 'q3', 'text' => 'What does range(0, 10, 2) produce?', 'options' => array('0,1,2,...,10', '0,2,4,6,8', '2,4,6,8,10', '0,5,10'), 'answer' => 1, 'explanation' => 'The third argument is the step size — counting by 2s from 0 up to (not including) 10 gives 0,2,4,6,8.'),
        array('id' => 'q4', 'text' => 'Can a for loop iterate directly over a string?', 'options' => array('No, only over range()', 'Yes — it goes character by character', 'Only if the string is converted to a list first', 'Only for strings of exactly one character'), 'answer' => 1, 'explanation' => 'Python strings are iterable — a for loop can go through them one character at a time.'),
        array('id' => 'q5', 'text' => 'When is a for loop usually preferred over a while loop?', 'options' => array('When the number of repetitions is known in advance', 'Only when using strings', 'Never — while loops are always better', 'When you need an infinite loop'), 'answer' => 0, 'explanation' => 'for loops are the natural fit when you know how many times (or over what sequence) you want to repeat.'),
        array('id' => 'q6', 'text' => 'What is the output of: for i in range(3): print(i * 2)', 'options' => array('0 2 4', '2 4 6', '0 1 2', '1 2 3'), 'answer' => 0, 'explanation' => 'i takes 0,1,2, and i*2 gives 0,2,4.'),
    ),
);

// Topic 5 — Loop Control: break, continue, and Nested Loops
$unit2_topics[] = array(
    'title' => 'Loop Control: break, continue, and Nested Loops',
    'overview' => array(
        'intro' => "Loops don't always need to run to completion in the simplest way — this lesson covers exiting early, skipping iterations, and loops inside loops.",
        'points' => array("Using break to exit a loop early", "Using continue to skip to the next iteration", "Nesting one loop inside another"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'break — Exiting a Loop Early', 'body_html' => "<p><code>break</code> immediately stops the loop entirely, skipping any remaining iterations.</p>", 'code' => "for num in range(1, 10):\n    if num == 5:\n        break\n    print(num)\n# Output: 1 2 3 4 (stops before 5)"),
            array('heading' => 'continue — Skipping an Iteration', 'body_html' => "<p><code>continue</code> skips the rest of the current iteration and jumps straight to the next one — the loop keeps going, it just skips one pass.</p>", 'code' => "for num in range(1, 6):\n    if num == 3:\n        continue\n    print(num)\n# Output: 1 2 4 5 (3 is skipped, loop continues)"),
            array('heading' => 'Nested Loops', 'body_html' => "<p>A loop can contain another loop inside it — the inner loop runs completely for every single pass of the outer loop. This is how a multiplication table or grid pattern is usually built.</p>", 'code' => "for i in range(1, 4):\n    for j in range(1, 4):\n        print(i, \"x\", j, \"=\", i * j)"),
        ),
    ),
    'practical' => array(
        'brief' => "Loop through numbers 1 to 20 with a for loop. Skip multiples of 3 using continue. Stop the loop completely with break once you reach the number 15 (don't print 15 itself).",
        'starter' => "for num in range(1, 21):\n    # stop completely once num reaches 15\n\n    # skip multiples of 3\n\n    print(num)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '14', 'label' => 'Prints numbers up to 14'),
            array('type' => 'source_contains', 'value' => 'break', 'label' => 'Uses break to stop the loop'),
            array('type' => 'source_contains', 'value' => 'continue', 'label' => 'Uses continue to skip multiples of 3'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does break do inside a loop?', 'options' => array('Skips to the next iteration', 'Immediately exits the loop entirely', 'Pauses the loop for one second', 'Restarts the loop from the beginning'), 'answer' => 1, 'explanation' => 'break stops the loop completely — no further iterations run.'),
        array('id' => 'q2', 'text' => 'What does continue do inside a loop?', 'options' => array('Exits the loop entirely', 'Skips the rest of the current iteration and moves to the next one', 'Ends the program', 'Repeats the current iteration again'), 'answer' => 1, 'explanation' => 'continue skips the remaining code in the current pass and moves on to the next iteration.'),
        array('id' => 'q3', 'text' => 'What prints from: for num in range(1,10): if num==5: break; print(num)?', 'options' => array('1 2 3 4', '1 2 3 4 5', '5 6 7 8 9', 'Nothing'), 'answer' => 0, 'explanation' => 'The loop prints 1,2,3,4, then hits break when num==5 and stops before printing 5.'),
        array('id' => 'q4', 'text' => 'What prints from: for num in range(1,6): if num==3: continue; print(num)?', 'options' => array('1 2 4 5', '1 2 3 4 5', '1 2', '3'), 'answer' => 0, 'explanation' => 'The loop skips printing when num==3 (continue), but keeps looping — so it prints 1,2,4,5.'),
        array('id' => 'q5', 'text' => 'In a nested loop, how many times does the inner loop run in total if the outer loop runs 3 times and the inner loop runs 3 times each pass?', 'options' => array('3', '6', '9', '3 (the inner loop only counts once)'), 'answer' => 2, 'explanation' => 'The inner loop runs fully (3 times) for each of the outer loop\'s 3 passes: 3 x 3 = 9 total.'),
        array('id' => 'q6', 'text' => 'Which statement is true about break and continue?', 'options' => array('They do the same thing', 'break exits the loop, continue only skips the current iteration', 'break skips the current iteration, continue exits the loop', 'Neither can be used inside a for loop'), 'answer' => 1, 'explanation' => 'break stops the loop entirely; continue just skips ahead to the next iteration.'),
    ),
);

// Topic 6 — Control Flow Mini-Project: Login Attempt Limiter
$unit2_topics[] = array(
    'title' => 'Control Flow Mini-Project: Login Attempt Limiter',
    'overview' => array(
        'intro' => "This unit's capstone — combining input(), conditionals, and a while loop into one real, working mini-program, the same way real software handles a login screen.",
        'points' => array("Planning a program that combines input, conditions, and a loop", "Limiting the number of attempts with a counter", "Reviewing everything covered in this unit"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Planning the Program', 'body_html' => "<p>Real programs combine everything you've learned so far. A login attempt limiter needs: a stored correct password, a loop that keeps asking as long as attempts remain, an if check comparing the entry to the password, and a counter tracking how many tries have been used.</p>"),
            array('heading' => 'Putting It Together', 'body_html' => "<p>The loop condition itself can check the attempts counter, and break is used to exit immediately the moment the correct password is entered — no need to keep looping once the goal is reached.</p>", 'code' => "password = \"mtti2026\"\nattempts = 0\n\nwhile attempts < 3:\n    entry = input(\"Enter password: \")\n    attempts += 1\n    if entry == password:\n        print(\"Access granted\")\n        break\n    else:\n        print(\"Wrong password\")\nelse:\n    print(\"Access denied\")"),
            array('heading' => "A Note on while...else", 'body_html' => "<p>Python's while loop can have an optional <code>else</code> clause — it runs only if the loop finished naturally (the condition became False) and was never stopped early with <code>break</code>. That's exactly how the example above prints \"Access denied\" only when all 3 attempts are used up, but not when break already fired.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "The correct password is \"mtti2026\". Give the user up to 3 attempts using input() inside a while loop. Print 'Access granted' and stop as soon as they enter it correctly.",
        'starter' => "password = \"mtti2026\"\nattempts = 0\n\n# write a while loop: ask for the password, count attempts, allow up to 3 tries\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Access granted', 'label' => 'Grants access on the correct password'),
            array('type' => 'source_contains', 'value' => 'while', 'label' => 'Uses a while loop'),
            array('type' => 'source_contains', 'value' => 'input(', 'label' => 'Uses input() to read the password'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
        'simulated_inputs' => array('wrong1', 'wrong2', 'mtti2026'),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What runs a while loop\'s else clause?', 'options' => array('Every time the loop runs, no matter what', 'Only when the loop finishes naturally without hitting a break', 'Only when the loop never runs at all', 'It never runs — while loops cannot have an else'), 'answer' => 1, 'explanation' => "A while loop's else block runs only if the loop completes without being interrupted by break."),
        array('id' => 'q2', 'text' => 'In the login example, what stops the loop immediately once the password is correct?', 'options' => array('continue', 'break', 'The while condition alone', 'pass'), 'answer' => 1, 'explanation' => 'break exits the loop right away once the correct password is entered, without waiting for the attempts counter.'),
        array('id' => 'q3', 'text' => 'Why does the login program use attempts += 1 inside the loop?', 'options' => array('To print the attempt number', 'To track how many tries have been used, so the loop can eventually stop', 'To reset the password', 'It has no real purpose'), 'answer' => 1, 'explanation' => 'attempts += 1 counts each try so the while attempts < 3 condition can eventually become False.'),
        array('id' => 'q4', 'text' => 'Which control-flow tools does the login mini-project combine?', 'options' => array('Only if statements', 'Only loops', 'input(), a while loop, and an if/else check', 'Only comparison operators'), 'answer' => 2, 'explanation' => 'The mini-project combines input() for user entry, a while loop for repeated attempts, and if/else for the password check.'),
        array('id' => 'q5', 'text' => 'What would happen if attempts += 1 was accidentally removed from the loop?', 'options' => array('Nothing would change', 'The loop would still stop after exactly 3 tries', 'The loop could run forever if the password is never entered correctly, since attempts never increases', 'The program would crash immediately'), 'answer' => 2, 'explanation' => 'Without incrementing attempts, the while attempts < 3 condition would stay True forever, causing an infinite loop of prompts.'),
        array('id' => 'q6', 'text' => 'What best describes what this unit covered overall?', 'options' => array('Only how to print text', 'Making decisions with if/elif/else and repeating code with while/for loops, including break/continue control', 'Only mathematical operators', 'Only how to store data in variables'), 'answer' => 1, 'explanation' => "This unit's core theme is control flow: branching with conditionals and repeating with loops, plus the tools to control them precisely."),
    ),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PRP01_UNIT2_BUILD') && MTTI_RUN_PRP01_UNIT2_BUILD) {
    global $wpdb;
    $lessons_table = $wpdb->prefix . 'mtti_lessons';
    $unit2_id = 149; // Control Flow, created during the Unit 1 pass

    $order = 100;
    foreach ($unit2_topics as $topic) {
        $tag = 'Control Flow · ' . $topic['title'];

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit2_id,
            'title' => $topic['title'] . ' — Overview',
            'content' => mtti_objectives_fragment($topic['overview']['intro'], $topic['overview']['points']),
            'content_type' => 'objectives', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 2,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        echo "  Overview lesson_id={$wpdb->insert_id}: {$topic['title']}\n";
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit2_id,
            'title' => $topic['title'] . ' — Video',
            'content' => null, 'content_type' => 'video', 'content_url' => null,
            'interactive_role' => null,
            'order_number' => $order, 'release_week' => 2,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit2_id,
            'title' => $topic['title'] . ' — Lesson',
            'content' => mtti_lesson_theory_html(array('title' => $topic['title'], 'lesson_tag' => $tag, 'sections' => $topic['lesson']['sections'])),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 2,
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
            'course_id' => 9, 'unit_id' => $unit2_id,
            'title' => $topic['title'] . ' — Practical',
            'content' => mtti_python_lab_html($practical_config),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 2,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$lessons_table} WHERE lesson_id = %d", 460));
        $quiz_html = mtti_build_final_quiz_html($template, $topic['title'] . ' — Quiz', $topic['quiz']);
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit2_id,
            'title' => $topic['title'] . ' — Quiz',
            'content' => $quiz_html,
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 2,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $order = ceil($order / 10) * 10;
        echo "Finished topic: {$topic['title']}\n";
    }

    echo "Unit 2 build complete.\n";
}

<?php
/**
 * PRP-01 (Programming/Coding Principles) curriculum rebuild — Unit 4:
 * Data Structures. Same granular pattern as Units 1-3, introducing lists
 * and dictionaries on top of the variables/control-flow/functions
 * foundation from earlier units.
 *
 * Usage: wp eval-file build-prp01-unit4.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

require_once __DIR__ . '/build-prp01-unit3.php'; // cascades: mtti_lesson_theory_html(), mtti_python_lab_html(), mtti_objectives_fragment(), mtti_build_final_quiz_html()

$unit4_topics = array();

// Topic 1 — Introduction to Lists
$unit4_topics[] = array(
    'title' => 'Introduction to Lists',
    'overview' => array(
        'intro' => "A single variable can only hold one value at a time — this lesson covers lists, Python's tool for storing many values together in order.",
        'points' => array("Creating a list with square brackets", "Accessing items by their position (index)", "Finding a list's length with len()"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'What is a List?', 'body_html' => "<p>A <strong>list</strong> is an ordered collection of values, written inside square brackets and separated by commas. A list can hold any data type, and its items stay in the order you put them in.</p>", 'code' => "courses = [\"Computer Applications\", \"Nursing Assistant\", \"Digital Marketing\"]\nprint(courses)"),
            array('heading' => 'Accessing Items by Index', 'body_html' => "<p>Each item in a list has a position number called its <strong>index</strong>, starting at <code>0</code> for the first item — exactly like the string indexing you'll see follows the same rule.</p>", 'code' => "print(courses[0])   # Computer Applications — the first item\nprint(courses[1])   # Nursing Assistant — the second item"),
            array('heading' => 'Finding a List\'s Length', 'body_html' => "<p>The built-in <code>len()</code> function — already used on strings — also works on lists, returning how many items they contain.</p>", 'code' => "print(len(courses))   # 3"),
        ),
    ),
    'practical' => array(
        'brief' => "Given the list courses (already created for you), print the whole list, then print the first course using indexing, then print the number of items using len().",
        'starter' => "courses = [\"Computer Applications\", \"Nursing Assistant\", \"Digital Marketing\"]\n\n# print the whole list\n\n# print the first item using courses[0]\n\n# print the number of items using len(courses)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Computer Applications', 'label' => 'Prints the list / first item correctly'),
            array('type' => 'source_contains', 'value' => 'len(', 'label' => 'Uses len() to count the items'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'How is a list written in Python?', 'options' => array('In curly braces {}', 'In square brackets [], items separated by commas', 'In parentheses ()', 'With no brackets at all'), 'answer' => 1, 'explanation' => 'A list is written with square brackets, e.g. [1, 2, 3].'),
        array('id' => 'q2', 'text' => 'What index refers to the first item in a list?', 'options' => array('1', '0', '-1', 'first'), 'answer' => 1, 'explanation' => 'Python list indexing starts at 0, so the first item is at index 0.'),
        array('id' => 'q3', 'text' => 'Given courses = ["A", "B", "C"], what does courses[1] return?', 'options' => array('"A"', '"B"', '"C"', 'An error'), 'answer' => 1, 'explanation' => 'Index 1 is the second item, "B".'),
        array('id' => 'q4', 'text' => 'What does len(courses) return for courses = ["A", "B", "C"]?', 'options' => array('2', '3', '1', 'An error'), 'answer' => 1, 'explanation' => 'len() returns the number of items in the list — 3.'),
        array('id' => 'q5', 'text' => 'Can a list contain items in a specific, remembered order?', 'options' => array('No, lists are always unordered', 'Yes — a list keeps items in the order they were added', 'Only if the items are numbers', 'Only if sort() is called first'), 'answer' => 1, 'explanation' => 'Lists are ordered — items stay in the sequence you put them in unless you explicitly reorder them.'),
        array('id' => 'q6', 'text' => 'Why use a list instead of several separate variables?', 'options' => array('Lists are required by Python for all data', 'A list groups related values together and lets you work with them as one collection', 'Separate variables cannot hold text', 'Lists run code faster'), 'answer' => 1, 'explanation' => 'A list lets you store and manage many related values together, instead of juggling many separate variable names.'),
    ),
);

// Topic 2 — List Indexing and Slicing
$unit4_topics[] = array(
    'title' => 'List Indexing and Slicing',
    'overview' => array(
        'intro' => "Beyond a single item, you can pull out the last item or a whole range of items at once — this lesson covers negative indexing and slicing.",
        'points' => array("Using negative indexes to count from the end of a list", "Slicing out a range of items with [start:stop]", "How slicing's stop position works (up to, but not including)"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Negative Indexing', 'body_html' => "<p>A negative index counts from the <strong>end</strong> of the list: <code>-1</code> is the last item, <code>-2</code> is the second-to-last, and so on.</p>", 'code' => "courses = [\"CompApps\", \"Nursing\", \"DigitalMkt\", \"WebDev\", \"CyberSec\"]\nprint(courses[-1])   # CyberSec — the last item"),
            array('heading' => 'Slicing a List', 'body_html' => "<p>A <strong>slice</strong> pulls out a range of items using <code>list[start:stop]</code> — it includes the item at <code>start</code> but stops <em>before</em> the item at <code>stop</code>, and always returns a new list.</p>", 'code' => "print(courses[1:3])   # ['Nursing', 'DigitalMkt'] — indexes 1 and 2, not 3\nprint(courses[:2])    # ['CompApps', 'Nursing'] — from the start up to index 2"),
        ),
    ),
    'practical' => array(
        'brief' => "Given numbers = [10, 20, 30, 40, 50], print the last item using negative indexing, then print a slice containing the middle three items (20, 30, 40) using slicing.",
        'starter' => "numbers = [10, 20, 30, 40, 50]\n\n# print the last item using negative indexing (numbers[-1])\n\n# print the slice containing 20, 30, 40\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '50', 'label' => 'Prints the last item correctly'),
            array('type' => 'output_contains', 'value' => '[20, 30, 40]', 'label' => 'Prints the correct slice'),
            array('type' => 'source_contains', 'value' => '-1', 'label' => 'Uses negative indexing'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does numbers[-1] return for numbers = [10, 20, 30]?', 'options' => array('10', '20', '30', 'An error'), 'answer' => 2, 'explanation' => '-1 refers to the last item in the list, which is 30.'),
        array('id' => 'q2', 'text' => 'What does numbers[1:3] return for numbers = [10, 20, 30, 40, 50]?', 'options' => array('[20, 30]', '[20, 30, 40]', '[10, 20, 30]', '[30, 40]'), 'answer' => 0, 'explanation' => 'A slice [1:3] includes index 1 and 2, but stops before index 3 — giving [20, 30].'),
        array('id' => 'q3', 'text' => 'What does numbers[:2] return for numbers = [10, 20, 30, 40]?', 'options' => array('[10, 20]', '[10]', '[20, 30]', '[10, 20, 30]'), 'answer' => 0, 'explanation' => 'Omitting the start defaults to the beginning of the list, so [:2] gives indexes 0 and 1: [10, 20].'),
        array('id' => 'q4', 'text' => 'Does a slice include the item at the stop index?', 'options' => array('Yes, always', 'No — it stops just before the stop index', 'Only for negative indexes', 'Only if stop equals len(list)'), 'answer' => 1, 'explanation' => 'Slicing is exclusive of the stop index, matching how range() behaves.'),
        array('id' => 'q5', 'text' => 'What does a slice return?', 'options' => array('A single value', 'A new list containing the selected items', 'The original list, unchanged', 'An error, slices are not allowed on lists'), 'answer' => 1, 'explanation' => 'Slicing produces a new list containing just the selected range of items.'),
        array('id' => 'q6', 'text' => 'What index would you use to get the second-to-last item in a list?', 'options' => array('-2', '2', '-1', 'len(list) - 1'), 'answer' => 0, 'explanation' => '-2 counts two from the end, which is the second-to-last item.'),
    ),
);

// Topic 3 — List Methods
$unit4_topics[] = array(
    'title' => 'List Methods',
    'overview' => array(
        'intro' => "A list isn't fixed once created — this lesson covers the built-in methods for adding, removing, and sorting items.",
        'points' => array("Adding an item to the end with append()", "Removing an item by value with remove()", "Sorting a list in place with sort()"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Adding Items with append()', 'body_html' => "<p><code>append()</code> adds a single item to the end of a list — it changes the list directly, rather than returning a new one.</p>", 'code' => "courses = [\"CompApps\", \"Nursing\"]\ncourses.append(\"WebDev\")\nprint(courses)   # ['CompApps', 'Nursing', 'WebDev']"),
            array('heading' => 'Removing Items with remove()', 'body_html' => "<p><code>remove()</code> removes the first item that matches the given value.</p>", 'code' => "courses.remove(\"Nursing\")\nprint(courses)   # ['CompApps', 'WebDev']"),
            array('heading' => 'Sorting with sort()', 'body_html' => "<p><code>sort()</code> arranges a list's items in order (ascending by default), changing the list itself.</p>", 'code' => "numbers = [5, 2, 8, 1]\nnumbers.sort()\nprint(numbers)   # [1, 2, 5, 8]"),
        ),
    ),
    'practical' => array(
        'brief' => "Start with an empty list called cart. Use append() to add \"Laptop Bag\", \"Notebook\", and \"Pen\" in that order. Then use remove() to remove \"Notebook\". Print the final list.",
        'starter' => "cart = []\n\n# append \"Laptop Bag\", \"Notebook\", \"Pen\" in that order\n\n# remove \"Notebook\"\n\nprint(cart)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => "['Laptop Bag', 'Pen']", 'label' => 'Prints the correct final list'),
            array('type' => 'source_contains', 'value' => 'append', 'label' => 'Uses append()'),
            array('type' => 'source_contains', 'value' => 'remove', 'label' => 'Uses remove()'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does courses.append("WebDev") do?', 'options' => array('Removes "WebDev" from the list', 'Adds "WebDev" to the end of the list', 'Sorts the list', 'Replaces the whole list with "WebDev"'), 'answer' => 1, 'explanation' => 'append() adds the given item to the end of the list.'),
        array('id' => 'q2', 'text' => 'What does courses.remove("Nursing") do?', 'options' => array('Removes the first item equal to "Nursing"', 'Adds "Nursing" to the list', 'Removes every item in the list', 'Sorts "Nursing" to the front'), 'answer' => 0, 'explanation' => 'remove() deletes the first matching item with that value.'),
        array('id' => 'q3', 'text' => 'What does numbers.sort() do to numbers = [5, 2, 8, 1]?', 'options' => array('Returns a new sorted list, leaving numbers unchanged', 'Sorts numbers in place to [1, 2, 5, 8]', 'Reverses the list', 'Removes duplicate values'), 'answer' => 1, 'explanation' => 'sort() rearranges the existing list in ascending order by default.'),
        array('id' => 'q4', 'text' => 'What is the syntax for calling a method on a list called cart?', 'options' => array('append(cart, item)', 'cart.append(item)', 'cart -> append(item)', 'append cart item'), 'answer' => 1, 'explanation' => "Python methods use dot notation: object.method(arguments), e.g. cart.append(item)."),
        array('id' => 'q5', 'text' => 'Starting with cart = [], what does cart look like after cart.append("Pen") followed by cart.append("Bag")?', 'options' => array('["Pen", "Bag"]', '["Bag", "Pen"]', '["Pen"]', '[]'), 'answer' => 0, 'explanation' => 'Each append() adds to the end, so the order matches the order the calls were made: Pen then Bag.'),
        array('id' => 'q6', 'text' => 'If you call cart.remove("X") but "X" is not in the list, what happens?', 'options' => array('Nothing happens, silently', 'Python raises an error (ValueError)', 'It adds "X" instead', 'It removes the first item regardless'), 'answer' => 1, 'explanation' => "remove() raises a ValueError if the given value isn't found in the list."),
    ),
);

// Topic 4 — Looping Through Lists
$unit4_topics[] = array(
    'title' => 'Looping Through Lists',
    'overview' => array(
        'intro' => "Lists and for loops go together constantly — this lesson covers looping over every item in a list, and combining that with the conditionals from Unit 2.",
        'points' => array("Looping through a list with a for-in loop", "Combining a loop with an if statement to count or filter items", "Why this combination is one of the most common patterns in programming"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Looping Through a List', 'body_html' => "<p>A <code>for</code> loop can iterate directly over a list's items, one at a time, without needing <code>range()</code> or manual indexing at all.</p>", 'code' => "courses = [\"CompApps\", \"Nursing\", \"WebDev\"]\nfor course in courses:\n    print(course)"),
            array('heading' => 'Combining a Loop with a Condition', 'body_html' => "<p>Pairing a for-in loop with an if statement — one of the most useful combinations in programming — lets you process only the items that meet some condition, such as counting how many pass a threshold.</p>", 'code' => "scores = [45, 78, 92, 60, 35]\ncount_pass = 0\nfor score in scores:\n    if score >= 50:\n        count_pass += 1\nprint(count_pass)   # 3"),
        ),
    ),
    'practical' => array(
        'brief' => "Given scores = [45, 78, 92, 60, 35], loop through the list and count how many scores are 50 or above. Print the count.",
        'starter' => "scores = [45, 78, 92, 60, 35]\ncount_pass = 0\n\n# loop through scores, count how many are >= 50\n\nprint(count_pass)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '3', 'label' => 'Prints the correct count (3)'),
            array('type' => 'source_contains', 'value' => 'for', 'label' => 'Uses a for loop'),
            array('type' => 'source_contains', 'value' => '>=', 'label' => 'Checks scores against the threshold'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does for course in courses: print(course) do?', 'options' => array('Prints only the first item', 'Prints every item in courses, one at a time', 'Prints the list once as a whole', 'Raises an error'), 'answer' => 1, 'explanation' => 'A for-in loop visits every item in the list in order, running the block once per item.'),
        array('id' => 'q2', 'text' => 'For scores = [45, 78, 92, 60, 35], how many are >= 50?', 'options' => array('2', '3', '4', '5'), 'answer' => 1, 'explanation' => '78, 92, and 60 are >= 50 — that\'s 3 scores.'),
        array('id' => 'q3', 'text' => 'Do you need range() to loop over a list with a for loop?', 'options' => array('Yes, always', 'No — a for loop can iterate over a list directly', 'Only for lists of numbers', 'Only for lists longer than 10 items'), 'answer' => 1, 'explanation' => 'for item in my_list: works directly on lists without needing range() or indexes.'),
        array('id' => 'q4', 'text' => 'Why combine a for loop with an if statement when processing a list?', 'options' => array('It is required by Python syntax', 'To process or count only the items that meet a specific condition', 'It makes the list sort automatically', 'It converts the list to a dictionary'), 'answer' => 1, 'explanation' => 'Combining a loop with a condition lets you filter, count, or act only on items matching some rule.'),
        array('id' => 'q5', 'text' => 'In the scores example, what does count_pass += 1 do?', 'options' => array('Resets count_pass to 1', 'Adds 1 to count_pass each time a score meets the condition', 'Removes a score from the list', 'Ends the loop'), 'answer' => 1, 'explanation' => 'count_pass += 1 increments the running total by 1 each time the if condition is True.'),
        array('id' => 'q6', 'text' => 'What order does a for-in loop visit list items in?', 'options' => array('Random order', 'Reverse order, always', 'The same order the items appear in the list', 'Alphabetical order, always'), 'answer' => 2, 'explanation' => 'A for-in loop over a list visits items in their existing list order.'),
    ),
);

// Topic 5 — Dictionaries
$unit4_topics[] = array(
    'title' => 'Dictionaries',
    'overview' => array(
        'intro' => "Not all data fits neatly into an ordered list of values — this lesson covers dictionaries, which store data as labeled key-value pairs.",
        'points' => array("Creating a dictionary with curly braces and key-value pairs", "Accessing and updating values by key", "Checking whether a key exists with in"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'What is a Dictionary?', 'body_html' => "<p>A <strong>dictionary</strong> stores data as <code>key: value</code> pairs inside curly braces, instead of a plain ordered sequence. Each key acts like a label you use to look up its value.</p>", 'code' => "student = {\"name\": \"Amina\", \"course\": \"Nursing Assistant\", \"age\": 22}\nprint(student[\"name\"])   # Amina"),
            array('heading' => 'Updating and Adding Keys', 'body_html' => "<p>Assigning to an existing key updates its value. Assigning to a key that doesn't exist yet adds it as a brand-new entry.</p>", 'code' => "student[\"age\"] = 23                         # update an existing key\nstudent[\"email\"] = \"amina@example.com\"       # add a new key\nprint(student)"),
            array('heading' => 'Checking if a Key Exists', 'body_html' => "<p>The <code>in</code> keyword — already used with strings and lists — also checks whether a key exists in a dictionary.</p>", 'code' => "if \"email\" in student:\n    print(\"Has an email on file\")"),
        ),
    ),
    'practical' => array(
        'brief' => "Given the dictionary student (already created), print the value for the \"course\" key. Then update \"age\" to 22, and print the whole dictionary.",
        'starter' => "student = {\"name\": \"Brian\", \"course\": \"Programming Principles\", \"age\": 21}\n\n# print student[\"course\"]\n\n# update student[\"age\"] to 22\n\n# print the whole dictionary\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Programming Principles', 'label' => 'Prints the course value correctly'),
            array('type' => 'output_contains', 'value' => "'age': 22", 'label' => 'Correctly updates the age key'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'How is a dictionary written in Python?', 'options' => array('In square brackets, comma-separated values', 'In curly braces, as key: value pairs', 'In parentheses, as key = value pairs', 'With no special syntax at all'), 'answer' => 1, 'explanation' => 'Dictionaries use curly braces with key: value pairs, e.g. {"name": "Amina"}.'),
        array('id' => 'q2', 'text' => 'Given student = {"name": "Amina", "age": 22}, how do you get the name?', 'options' => array('student[0]', 'student.name', 'student["name"]', 'student(name)'), 'answer' => 2, 'explanation' => 'Dictionary values are accessed using square brackets with the key: student["name"].'),
        array('id' => 'q3', 'text' => 'What happens when you assign to a dictionary key that already exists, e.g. student["age"] = 23?', 'options' => array('Python raises an error', 'It updates the existing value for that key', 'It creates a duplicate key', 'Nothing happens'), 'answer' => 1, 'explanation' => 'Assigning to an existing key overwrites its current value.'),
        array('id' => 'q4', 'text' => 'What happens when you assign to a key that does not exist yet, e.g. student["email"] = "x@x.com"?', 'options' => array('Python raises an error', 'It is ignored', 'It adds a brand-new key-value pair to the dictionary', 'It replaces the whole dictionary'), 'answer' => 2, 'explanation' => 'Assigning to a new key adds it to the dictionary.'),
        array('id' => 'q5', 'text' => 'What does "email" in student check?', 'options' => array('Whether "email" is one of the dictionary\'s keys', 'Whether "email" is one of the dictionary\'s values', 'Whether the dictionary is empty', 'Whether student is a valid variable name'), 'answer' => 0, 'explanation' => 'The in keyword, used on a dictionary, checks whether the given value is one of its keys.'),
        array('id' => 'q6', 'text' => 'What is the main difference between a list and a dictionary?', 'options' => array('A list stores items by position/index; a dictionary stores items by named key', 'A dictionary can only store numbers', 'A list cannot be looped over', 'There is no real difference'), 'answer' => 0, 'explanation' => 'Lists are ordered and accessed by numeric index; dictionaries are accessed by key, which can be any label like a string.'),
    ),
);

// Topic 6 — Mini-Project: Simple Student Record System
$unit4_topics[] = array(
    'title' => 'Mini-Project: Simple Student Record System',
    'overview' => array(
        'intro' => "This unit's capstone — combining lists and dictionaries into a list of records, the same shape real applications use to represent a table of data.",
        'points' => array("Storing multiple records as a list of dictionaries", "Looping through records to read and process their fields", "Reviewing everything covered in this unit"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'A List of Dictionaries', 'body_html' => "<p>Lists and dictionaries combine naturally: a <strong>list of dictionaries</strong> represents multiple records, each with the same set of fields — very close to how a table of student records would actually be modeled.</p>", 'code' => "students = [\n    {\"name\": \"Amina\", \"score\": 82},\n    {\"name\": \"Brian\", \"score\": 55},\n    {\"name\": \"Cynthia\", \"score\": 91}\n]"),
            array('heading' => 'Looping Through Records', 'body_html' => "<p>A for-in loop over this list gives you one dictionary per pass, and you access each record's fields with the usual key lookup.</p>", 'code' => "for student in students:\n    print(student[\"name\"], \"-\", student[\"score\"])"),
            array('heading' => 'Aggregating a Field Across All Records', 'body_html' => "<p>Combining a loop with the accumulator pattern from Unit 2 lets you compute a summary across every record — such as an average score.</p>", 'code' => "total = 0\nfor student in students:\n    total += student[\"score\"]\naverage = total / len(students)\nprint(\"Average score:\", round(average, 1))"),
        ),
    ),
    'practical' => array(
        'brief' => "Given the list of dictionaries students (already created), loop through and print each student as \"name: score\" (e.g. \"Amina: 82\"). Then calculate and print the average score of all students, rounded to 1 decimal place.",
        'starter' => "students = [\n    {\"name\": \"Amina\", \"score\": 82},\n    {\"name\": \"Brian\", \"score\": 55},\n    {\"name\": \"Cynthia\", \"score\": 91}\n]\n\n# loop through students, print each as \"name: score\"\n\n# calculate and print the average score, rounded to 1 decimal place\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Amina: 82', 'label' => 'Prints each student record correctly'),
            array('type' => 'output_contains', 'value' => '76.0', 'label' => 'Prints the correct average score (76.0)'),
            array('type' => 'source_contains', 'value' => 'for', 'label' => 'Loops through the list of dictionaries'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does a "list of dictionaries" represent well?', 'options' => array('A single value', 'Multiple records, each with the same set of named fields', 'A single dictionary with extra keys', 'A sorted list of numbers'), 'answer' => 1, 'explanation' => 'Each dictionary in the list is one record, with consistent keys/fields across all of them.'),
        array('id' => 'q2', 'text' => 'In for student in students:, what does student represent on each pass?', 'options' => array('The whole students list', 'One individual dictionary (one student record) at a time', 'Just the name field', 'The index number'), 'answer' => 1, 'explanation' => 'Each iteration gives you one dictionary from the list — one full student record.'),
        array('id' => 'q3', 'text' => 'Given students = [{"name":"Amina","score":82}, {"name":"Brian","score":55}, {"name":"Cynthia","score":91}], what is the average score?', 'options' => array('75.0', '76.0', '76.3', '80.0'), 'answer' => 1, 'explanation' => '(82+55+91)/3 = 228/3 = 76.0.'),
        array('id' => 'q4', 'text' => 'How would you access the score of one student record inside the loop?', 'options' => array('student.score', 'student["score"]', 'score(student)', 'student[1]'), 'answer' => 1, 'explanation' => 'Dictionary values are accessed by key using square brackets: student["score"].'),
        array('id' => 'q5', 'text' => 'Which data structures does this mini-project combine?', 'options' => array('Only strings', 'Lists and dictionaries', 'Only numbers', 'Only Boolean values'), 'answer' => 1, 'explanation' => 'A list of dictionaries combines both structures: the list holds order, each dictionary holds named fields.'),
        array('id' => 'q6', 'text' => 'What best summarizes what this unit covered overall?', 'options' => array('Only how to print text', 'Storing and working with collections of data using lists and dictionaries, including indexing, slicing, methods, and looping', 'Only function definitions', 'Only comparison operators'), 'answer' => 1, 'explanation' => "This unit's core theme is data structures: organizing multiple values with lists and dictionaries, and processing them with loops."),
    ),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PRP01_UNIT4_BUILD') && MTTI_RUN_PRP01_UNIT4_BUILD) {
    global $wpdb;
    $lessons_table = $wpdb->prefix . 'mtti_lessons';
    $unit4_id = 151; // Data Structures, created during the Unit 1 pass

    $order = 300;
    foreach ($unit4_topics as $topic) {
        $tag = 'Data Structures · ' . $topic['title'];

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit4_id,
            'title' => $topic['title'] . ' — Overview',
            'content' => mtti_objectives_fragment($topic['overview']['intro'], $topic['overview']['points']),
            'content_type' => 'objectives', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 4,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        echo "  Overview lesson_id={$wpdb->insert_id}: {$topic['title']}\n";
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit4_id,
            'title' => $topic['title'] . ' — Video',
            'content' => null, 'content_type' => 'video', 'content_url' => null,
            'interactive_role' => null,
            'order_number' => $order, 'release_week' => 4,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit4_id,
            'title' => $topic['title'] . ' — Lesson',
            'content' => mtti_lesson_theory_html(array('title' => $topic['title'], 'lesson_tag' => $tag, 'sections' => $topic['lesson']['sections'])),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 4,
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
            'course_id' => 9, 'unit_id' => $unit4_id,
            'title' => $topic['title'] . ' — Practical',
            'content' => mtti_python_lab_html($practical_config),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 4,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$lessons_table} WHERE lesson_id = %d", 460));
        $quiz_html = mtti_build_final_quiz_html($template, $topic['title'] . ' — Quiz', $topic['quiz']);
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit4_id,
            'title' => $topic['title'] . ' — Quiz',
            'content' => $quiz_html,
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 4,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $order = ceil($order / 10) * 10;
        echo "Finished topic: {$topic['title']}\n";
    }

    echo "Unit 4 build complete.\n";
}

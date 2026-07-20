<?php
/**
 * PRP-01 (Programming/Coding Principles) curriculum rebuild — Unit 3:
 * Functions and Modularity. Same granular pattern as Units 1-2, building
 * on control flow to teach code organization and reuse.
 *
 * Usage: wp eval-file build-prp01-unit3.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

require_once __DIR__ . '/build-prp01-unit2.php'; // cascades: mtti_lesson_theory_html(), mtti_python_lab_html(), mtti_objectives_fragment(), mtti_build_final_quiz_html()

$unit3_topics = array();

// Topic 1 — Defining and Calling Functions
$unit3_topics[] = array(
    'title' => 'Defining and Calling Functions',
    'overview' => array(
        'intro' => "Repeating the same code over and over is a sign you need a function — this lesson covers packaging code into a reusable, named block.",
        'points' => array("Defining a function with the def keyword", "Calling a function to run its code", "Why functions make programs easier to read and maintain"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'What is a Function?', 'body_html' => "<p>A function is a named, reusable block of code that performs a specific task. Instead of retyping the same lines every time you need them, you define the code once and <strong>call</strong> the function whenever you need it to run.</p>"),
            array('heading' => 'Defining a Function', 'body_html' => "<p>A function is defined with the <code>def</code> keyword, a name, parentheses, and a colon — the indented lines below belong to the function's body, exactly like an if statement or loop.</p>", 'code' => "def greet():\n    print(\"Hello, welcome to MTTI!\")"),
            array('heading' => 'Calling a Function', 'body_html' => "<p>Defining a function does not run it — the code inside only runs when you <strong>call</strong> the function by writing its name followed by parentheses.</p>", 'code' => "def greet():\n    print(\"Hello, welcome to MTTI!\")\n\ngreet()   # this actually runs the code\ngreet()   # can be called again, as many times as needed"),
        ),
    ),
    'practical' => array(
        'brief' => "Define a function called print_motto that prints \"Start Learning, Start Earning\". Call the function.",
        'starter' => "def print_motto():\n    # print the motto here\n\n\n# call print_motto() below\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Start Learning, Start Earning', 'label' => 'Prints the motto'),
            array('type' => 'source_contains', 'value' => 'def print_motto', 'label' => 'Defines the function correctly'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'Which keyword defines a function in Python?', 'options' => array('function', 'def', 'func', 'define'), 'answer' => 1, 'explanation' => 'Python uses the def keyword to define a function.'),
        array('id' => 'q2', 'text' => 'Does defining a function run its code immediately?', 'options' => array('Yes, always', 'No — the code only runs when the function is called', 'Only if it contains a print statement', 'Only the first line runs'), 'answer' => 1, 'explanation' => "Defining a function just creates it — the body only executes when you call the function with parentheses, e.g. greet()."),
        array('id' => 'q3', 'text' => 'How do you call a function named greet?', 'options' => array('greet', 'call greet()', 'greet()', 'run greet'), 'answer' => 2, 'explanation' => 'Calling a function requires its name followed by parentheses: greet().'),
        array('id' => 'q4', 'text' => 'Why use functions instead of repeating code?', 'options' => array('Functions make code run slower on purpose', 'They let you reuse a block of code without retyping it, and keep programs organized', 'Python requires at least one function in every program', 'Functions remove the need for variables'), 'answer' => 1, 'explanation' => 'Functions avoid repetition and make code easier to read, test, and maintain.'),
        array('id' => 'q5', 'text' => 'Can a function be called more than once?', 'options' => array('No, only once per program', 'Yes, as many times as needed', 'Only twice', 'Only if it has no parameters'), 'answer' => 1, 'explanation' => 'A defined function can be called any number of times.'),
        array('id' => 'q6', 'text' => 'What must follow the function name when defining it?', 'options' => array('A semicolon', 'Parentheses and a colon', 'Curly braces', 'Square brackets'), 'answer' => 1, 'explanation' => "A function definition looks like: def name():, with parentheses and a colon."),
    ),
);

// Topic 2 — Parameters and Arguments
$unit3_topics[] = array(
    'title' => 'Parameters and Arguments',
    'overview' => array(
        'intro' => "A function becomes far more useful when it can work with different values each time it's called — this lesson covers passing data into a function.",
        'points' => array("The difference between a parameter and an argument", "Defining a function with one or more parameters", "Passing multiple arguments in order"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Parameters vs Arguments', 'body_html' => "<p>A <strong>parameter</strong> is the placeholder name listed in a function's definition. An <strong>argument</strong> is the actual value you pass in when calling it. They're closely related terms for the same mechanism, viewed from two sides.</p>", 'code' => "def greet(name):        # name is a parameter\n    print(\"Hello, \" + name + \"!\")\n\ngreet(\"Amina\")           # \"Amina\" is the argument"),
            array('heading' => 'Multiple Parameters', 'body_html' => "<p>A function can take more than one parameter, separated by commas. Arguments are matched to parameters by their position — the first argument fills the first parameter, and so on.</p>", 'code' => "def add(a, b):\n    print(a + b)\n\nadd(3, 4)   # a=3, b=4 -> prints 7"),
        ),
    ),
    'practical' => array(
        'brief' => "Define a function called calculate_total that takes price and quantity as parameters, and prints the total (price times quantity). Call it with price=250 and quantity=3.",
        'starter' => "def calculate_total(price, quantity):\n    # calculate and print the total\n\n\n# call calculate_total(250, 3)\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '750', 'label' => 'Prints the correct total (750)'),
            array('type' => 'source_contains', 'value' => 'def calculate_total', 'label' => 'Defines the function with two parameters'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'In def greet(name):, what is name?', 'options' => array('An argument', 'A parameter', 'A return value', 'A function call'), 'answer' => 1, 'explanation' => 'name is the parameter — the placeholder defined in the function signature.'),
        array('id' => 'q2', 'text' => 'In greet("Amina"), what is "Amina"?', 'options' => array('A parameter', 'An argument', 'A keyword', 'A default value'), 'answer' => 1, 'explanation' => '"Amina" is the argument — the actual value passed in when calling the function.'),
        array('id' => 'q3', 'text' => 'Given def add(a, b): print(a + b), what does add(3, 4) print?', 'options' => array('34', '7', '3 4', 'An error'), 'answer' => 1, 'explanation' => 'a=3 and b=4 are matched by position, and a + b = 7.'),
        array('id' => 'q4', 'text' => 'How are arguments matched to parameters by default?', 'options' => array('Alphabetically', 'By position — the first argument fills the first parameter', 'Randomly', 'They are not matched at all'), 'answer' => 1, 'explanation' => 'Positional matching means the order of arguments corresponds to the order of parameters.'),
        array('id' => 'q5', 'text' => 'Can a function have more than one parameter?', 'options' => array('No, only one is allowed', 'Yes, separated by commas', 'Only two, never more', 'Only if they are the same data type'), 'answer' => 1, 'explanation' => 'A function can take any number of parameters, separated by commas.'),
        array('id' => 'q6', 'text' => 'What happens if you call add(3, 4) but add(a, b) expects exactly two arguments and you only pass one?', 'options' => array('Python fills the missing one with 0', 'Python raises an error (missing argument)', 'The function silently does nothing', 'Python guesses the missing value'), 'answer' => 1, 'explanation' => 'Calling a function without providing all its required parameters raises a TypeError in Python.'),
    ),
);

// Topic 3 — Return Values
$unit3_topics[] = array(
    'title' => 'Return Values',
    'overview' => array(
        'intro' => "Printing a result inside a function isn't the same as giving that result back to whoever called it — this lesson covers the return keyword.",
        'points' => array("Using return to send a value back from a function", "Storing a function's returned value in a variable", "Why a function with no return gives back None"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'The return Keyword', 'body_html' => "<p><code>return</code> sends a value back out of a function to wherever it was called — the caller can then store that value in a variable and reuse it, unlike a value that's only printed.</p>", 'code' => "def add(a, b):\n    return a + b\n\nresult = add(3, 4)\nprint(result)   # 7"),
            array('heading' => 'Functions Without return', 'body_html' => "<p>A function that never hits a <code>return</code> statement automatically gives back <code>None</code> — Python's way of representing \"nothing\". This is different from printing, which only displays a value and doesn't hand anything back to the caller.</p>", 'code' => "def greet(name):\n    print(\"Hi \" + name)\n\nx = greet(\"Amina\")   # prints \"Hi Amina\"\nprint(x)               # None — greet() never returned anything"),
        ),
    ),
    'practical' => array(
        'brief' => "Define a function called calculate_discount that takes a price and returns the price after a 10% discount (do not print inside the function). Call it with price=2000, store the result in a variable, and print that variable.",
        'starter' => "def calculate_discount(price):\n    # return the price after a 10% discount\n\n\n# call calculate_discount(2000), store the result, and print it\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '1800', 'label' => 'Prints the correct discounted price (1800)'),
            array('type' => 'source_contains', 'value' => 'return', 'label' => 'Uses return inside the function'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What does the return keyword do?', 'options' => array('Prints a value to the screen', 'Sends a value back from the function to the caller', 'Ends the entire program', 'Deletes the function'), 'answer' => 1, 'explanation' => 'return hands a value back to wherever the function was called from.'),
        array('id' => 'q2', 'text' => 'What does a function return if it has no return statement at all?', 'options' => array('0', '"" (empty string)', 'None', 'It causes an error'), 'answer' => 2, 'explanation' => "A function with no return statement implicitly returns None."),
        array('id' => 'q3', 'text' => 'Given def add(a,b): return a+b then result = add(3,4), what does result hold?', 'options' => array('Nothing, result is undefined', '7', 'None', 'The text "a+b"'), 'answer' => 1, 'explanation' => 'The returned value 7 is stored in result.'),
        array('id' => 'q4', 'text' => 'What is the key difference between printing a value inside a function and returning it?', 'options' => array('There is no difference', 'print() only displays the value; return hands it back so the caller can store and reuse it', 'return only works with numbers', 'print() is faster than return'), 'answer' => 1, 'explanation' => 'A printed value is just shown on screen — it cannot be captured into a variable the way a returned value can.'),
        array('id' => 'q5', 'text' => 'Can code after a return statement inside the same function still run?', 'options' => array('Yes, always', 'No — return immediately exits the function', 'Only if it is another return', 'Only in loops'), 'answer' => 1, 'explanation' => 'return exits the function immediately; any code after it in that same block never runs.'),
        array('id' => 'q6', 'text' => 'Why did the discount function use return instead of print?', 'options' => array('So the discounted price could be stored in a variable and reused, not just displayed', 'return is required in every function', 'print() cannot handle numbers', 'There is no real reason'), 'answer' => 0, 'explanation' => 'Returning the value lets the caller decide what to do with it — store it, print it, or use it in further calculations.'),
    ),
);

// Topic 4 — Default Parameters and Keyword Arguments
$unit3_topics[] = array(
    'title' => 'Default Parameters and Keyword Arguments',
    'overview' => array(
        'intro' => "Not every argument needs to be supplied every time — this lesson covers giving parameters a fallback value and calling functions more clearly by name.",
        'points' => array("Giving a parameter a default value", "Calling a function while skipping a defaulted parameter", "Using keyword arguments for clarity"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Default Parameter Values', 'body_html' => "<p>A parameter can be given a default value in the function definition. If the caller doesn't supply that argument, the default is used automatically.</p>", 'code' => "def greet(name, greeting=\"Hello\"):\n    print(greeting + \", \" + name + \"!\")\n\ngreet(\"Amina\")            # Hello, Amina! (uses the default)\ngreet(\"Amina\", \"Hi\")      # Hi, Amina! (overrides the default)"),
            array('heading' => 'Keyword Arguments', 'body_html' => "<p>Instead of relying on position, you can name the parameter directly in the call — this is a <strong>keyword argument</strong>. It makes calls clearer and lets you supply arguments in any order.</p>", 'code' => "greet(name=\"Brian\", greeting=\"Karibu\")\ngreet(greeting=\"Karibu\", name=\"Brian\")   # same result — order doesn't matter with keywords"),
        ),
    ),
    'practical' => array(
        'brief' => "Define a function called enroll that takes student_name and course, where course defaults to \"Computer Applications\". Call enroll(\"Faith\") using just the name (relying on the default), then call enroll(\"Brian\", \"Nursing Assistant\") with both arguments.",
        'starter' => "def enroll(student_name, course=\"Computer Applications\"):\n    print(student_name + \" enrolled in \" + course)\n\n\n# call enroll(\"Faith\")\n\n# call enroll(\"Brian\", \"Nursing Assistant\")\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Faith enrolled in Computer Applications', 'label' => 'Uses the default course correctly'),
            array('type' => 'output_contains', 'value' => 'Brian enrolled in Nursing Assistant', 'label' => 'Overrides the default course correctly'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What is a default parameter value?', 'options' => array('A value that is always ignored', 'A fallback value used automatically when the caller doesn\'t supply that argument', 'The first argument in every function', 'A value that can never be changed'), 'answer' => 1, 'explanation' => 'A default value is used only when the corresponding argument is omitted from the call.'),
        array('id' => 'q2', 'text' => 'Given def greet(name, greeting="Hello"):, what does greet("Amina") print?', 'options' => array('Hello, Amina!', 'An error, since greeting was not supplied', 'None, Amina!', 'Amina, Hello!'), 'answer' => 0, 'explanation' => 'greeting defaults to "Hello" since it was not supplied in the call.'),
        array('id' => 'q3', 'text' => 'What is a keyword argument?', 'options' => array('An argument passed by naming the parameter directly, e.g. name="Brian"', 'A special Python reserved word', 'An argument that must be a string', 'The same thing as a default parameter'), 'answer' => 0, 'explanation' => 'A keyword argument names the parameter explicitly in the call, e.g. greet(name="Brian").'),
        array('id' => 'q4', 'text' => 'Does the order of keyword arguments in a call matter?', 'options' => array('Yes, they must always match the definition order', 'No — naming them means order does not matter', 'Only for the first argument', 'Keyword arguments are not allowed in Python'), 'answer' => 1, 'explanation' => 'Because keyword arguments are matched by name, not position, their order in the call is flexible.'),
        array('id' => 'q5', 'text' => 'Why are default parameters useful?', 'options' => array('They make a parameter optional, with a sensible fallback if not provided', 'They are required by Python for every function', 'They prevent a function from being called more than once', 'They automatically print the function\'s result'), 'answer' => 0, 'explanation' => 'Defaults let callers skip arguments that usually have the same value, simplifying most calls.'),
        array('id' => 'q6', 'text' => 'Given def enroll(student_name, course="Computer Applications"):, what does enroll("Brian", "Nursing Assistant") do?', 'options' => array('Uses the default course, ignoring "Nursing Assistant"', 'Overrides the default, enrolling Brian in Nursing Assistant', 'Raises an error, since course already has a default', 'Enrolls two separate students'), 'answer' => 1, 'explanation' => 'Supplying a second argument overrides the default value for that call.'),
    ),
);

// Topic 5 — Variable Scope: Local vs Global
$unit3_topics[] = array(
    'title' => 'Variable Scope: Local vs Global',
    'overview' => array(
        'intro' => "A variable created inside a function doesn't automatically exist everywhere — this lesson covers where a variable can and can't be used.",
        'points' => array("What a local variable is, and where it can be used", "What a global variable is, and how functions can read it", "Why relying on global variables inside functions is usually avoided"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Local Variables', 'body_html' => "<p>A variable created inside a function is <strong>local</strong> to that function — it only exists while the function is running, and cannot be accessed from outside it.</p>", 'code' => "def greet():\n    message = \"Hello!\"   # local variable\n    print(message)\n\ngreet()\nprint(message)   # NameError: message is not defined"),
            array('heading' => 'Global Variables', 'body_html' => "<p>A variable created outside any function, at the top level of your program, is <strong>global</strong> — it can be read from inside any function without needing to be passed in as a parameter.</p>", 'code' => "school_name = \"MTTI\"   # global variable\n\ndef show_school():\n    print(school_name)     # functions can read global variables directly\n\nshow_school()"),
            array('heading' => 'Prefer Parameters Over Globals', 'body_html' => "<p>While functions can read global variables, relying on them heavily makes code harder to follow and debug. Passing data in as parameters (as in the earlier lessons) and getting it back with return keeps a function self-contained and predictable — generally the better habit.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "A global variable course_name is already set to \"Programming Principles\". Define a function called show_course that prints the value of course_name (reading the existing global variable — do not redefine it inside the function). Call the function.",
        'starter' => "course_name = \"Programming Principles\"\n\ndef show_course():\n    # print course_name here (it's already available as a global variable)\n\n\n# call show_course()\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => 'Programming Principles', 'label' => 'Prints the global variable correctly'),
            array('type' => 'source_contains', 'value' => 'def show_course', 'label' => 'Defines the function correctly'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'What is a local variable?', 'options' => array('A variable available everywhere in the program', 'A variable that only exists inside the function where it was created', 'A variable that never changes', 'A variable created before the program starts'), 'answer' => 1, 'explanation' => 'A local variable is scoped to the function it was created in and is not accessible outside it.'),
        array('id' => 'q2', 'text' => 'What happens if you try to access a function\'s local variable from outside that function?', 'options' => array('It works normally', 'Python raises a NameError', 'The variable becomes global automatically', 'It returns None'), 'answer' => 1, 'explanation' => 'A local variable does not exist outside its function, so referencing it there raises a NameError.'),
        array('id' => 'q3', 'text' => 'Can a function read a global variable without it being passed in as a parameter?', 'options' => array('No, never', 'Yes — functions can read global variables directly', 'Only if the function has no parameters at all', 'Only inside a loop'), 'answer' => 1, 'explanation' => 'Functions can read (though not directly reassign, without the global keyword) variables defined at the top level.'),
        array('id' => 'q4', 'text' => 'Where is a global variable defined?', 'options' => array('Inside any function', 'At the top level of the program, outside any function', 'Only inside an if statement', 'Only inside a loop'), 'answer' => 1, 'explanation' => 'A global variable lives at the top level, outside all functions, making it accessible throughout the file.'),
        array('id' => 'q5', 'text' => 'Why is it generally considered better practice to use parameters and return values instead of relying on global variables inside functions?', 'options' => array('Global variables are not allowed in Python', 'It keeps functions self-contained and predictable, easier to follow and debug', 'Parameters run faster than global variables', 'There is no real benefit either way'), 'answer' => 1, 'explanation' => 'Functions that only depend on their parameters (rather than reaching out to global state) are easier to reason about and reuse.'),
        array('id' => 'q6', 'text' => 'In the example def greet(): message = "Hello!"; print(message), is message local or global?', 'options' => array('Global', 'Local — it is created inside the function', 'Neither — it does not have a scope', 'It depends on where greet() is called from'), 'answer' => 1, 'explanation' => 'message is created inside greet(), so it is local to that function.'),
    ),
);

// Topic 6 — Mini-Project: BMI Calculator with Functions
$unit3_topics[] = array(
    'title' => 'Mini-Project: BMI Calculator with Functions',
    'overview' => array(
        'intro' => "This unit's capstone — splitting a real task into two cooperating functions, the same way larger programs are organized.",
        'points' => array("Breaking a task into smaller, focused functions", "Having one function call the result of another", "Reviewing everything covered in this unit"),
    ),
    'lesson' => array(
        'sections' => array(
            array('heading' => 'Breaking a Task Into Functions', 'body_html' => "<p>Real programs are built from many small functions, each responsible for one clear task, rather than one giant block of code. A BMI (Body Mass Index) calculator naturally splits into two jobs: <strong>calculating</strong> the BMI number, and <strong>categorizing</strong> what that number means.</p>"),
            array('heading' => 'Using One Function\'s Result in Another', 'body_html' => "<p>The value returned by one function can be passed straight into another — this is exactly how small functions combine into a bigger program.</p>", 'code' => "def calculate_bmi(weight, height):\n    return weight / (height ** 2)\n\ndef get_bmi_category(bmi):\n    if bmi < 18.5:\n        return \"Underweight\"\n    elif bmi < 25:\n        return \"Normal\"\n    else:\n        return \"Overweight\"\n\nweight = 65\nheight = 1.7\nbmi = calculate_bmi(weight, height)\ncategory = get_bmi_category(bmi)\nprint(\"BMI:\", round(bmi, 1))\nprint(\"Category:\", category)"),
            array('heading' => 'A New Built-in: round()', 'body_html' => "<p><code>round(value, digits)</code> rounds a number to the given number of decimal places — useful for displaying calculation results cleanly, as in <code>round(bmi, 1)</code> above.</p>"),
        ),
    ),
    'practical' => array(
        'brief' => "Define calculate_bmi(weight, height) which returns weight / height ** 2, and get_bmi_category(bmi) which returns \"Underweight\" if bmi < 18.5, \"Normal\" if bmi < 25, otherwise \"Overweight\". Using weight=65 and height=1.7, call both functions and print the BMI (rounded to 1 decimal place with round()) and the category.",
        'starter' => "def calculate_bmi(weight, height):\n    # return weight / height ** 2\n\n\ndef get_bmi_category(bmi):\n    # return \"Underweight\", \"Normal\", or \"Overweight\" based on bmi\n\n\nweight = 65\nheight = 1.7\n\n# call calculate_bmi(), then get_bmi_category(), and print both results\n",
        'checks' => array(
            array('type' => 'output_contains', 'value' => '22.5', 'label' => 'Prints the correct BMI (22.5)'),
            array('type' => 'output_contains', 'value' => 'Normal', 'label' => 'Prints the correct category (Normal)'),
            array('type' => 'source_contains', 'value' => 'def calculate_bmi', 'label' => 'Defines calculate_bmi correctly'),
            array('type' => 'source_contains', 'value' => 'def get_bmi_category', 'label' => 'Defines get_bmi_category correctly'),
            array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
        ),
    ),
    'quiz' => array(
        array('id' => 'q1', 'text' => 'Why does this project use two separate functions instead of one big block of code?', 'options' => array('Python requires at least two functions per program', 'Splitting the task into focused, single-purpose functions makes the program easier to read and reuse', 'It makes the program run faster', 'There is no real reason'), 'answer' => 1, 'explanation' => 'Breaking a task into smaller functions, each doing one clear job, is a core habit of well-organized code.'),
        array('id' => 'q2', 'text' => 'What does round(22.4913, 1) return?', 'options' => array('22', '22.4', '22.5', '22.49'), 'answer' => 2, 'explanation' => 'round(value, 1) rounds to 1 decimal place — 22.4913 rounds to 22.5.'),
        array('id' => 'q3', 'text' => 'In the BMI program, how does get_bmi_category(bmi) receive the BMI value?', 'options' => array('It recalculates it independently', 'It reads it as a global variable', 'The value returned by calculate_bmi() is passed to it as an argument', 'It asks the user with input()'), 'answer' => 2, 'explanation' => 'bmi = calculate_bmi(weight, height) is returned, then passed as the argument to get_bmi_category(bmi).'),
        array('id' => 'q4', 'text' => 'For weight=65 and height=1.7, is the BMI (~22.5) categorized as Underweight, Normal, or Overweight?', 'options' => array('Underweight', 'Normal', 'Overweight', 'None of these'), 'answer' => 1, 'explanation' => '22.5 is less than 25 and not less than 18.5, so it falls into the "Normal" branch.'),
        array('id' => 'q5', 'text' => 'Which topics from this unit does the BMI mini-project combine?', 'options' => array('Only variables', 'Function definitions, parameters, return values, and conditionals (from Unit 2)', 'Only loops', 'Only default parameters'), 'answer' => 1, 'explanation' => 'The project combines defining functions with parameters, returning values, and using if/elif/else inside a function.'),
        array('id' => 'q6', 'text' => 'What best summarizes what this unit covered overall?', 'options' => array('Only printing text to the screen', 'Defining reusable functions with parameters and return values, defaults, keyword arguments, and variable scope', 'Only mathematical operators', 'Only how to write comments'), 'answer' => 1, 'explanation' => "This unit's core theme is functions: how to define, call, parameterize, and return values from them, plus how scope affects variables."),
    ),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PRP01_UNIT3_BUILD') && MTTI_RUN_PRP01_UNIT3_BUILD) {
    global $wpdb;
    $lessons_table = $wpdb->prefix . 'mtti_lessons';
    $unit3_id = 150; // Functions and Modularity, created during the Unit 1 pass

    $order = 200;
    foreach ($unit3_topics as $topic) {
        $tag = 'Functions and Modularity · ' . $topic['title'];

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit3_id,
            'title' => $topic['title'] . ' — Overview',
            'content' => mtti_objectives_fragment($topic['overview']['intro'], $topic['overview']['points']),
            'content_type' => 'objectives', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 3,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        echo "  Overview lesson_id={$wpdb->insert_id}: {$topic['title']}\n";
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit3_id,
            'title' => $topic['title'] . ' — Video',
            'content' => null, 'content_type' => 'video', 'content_url' => null,
            'interactive_role' => null,
            'order_number' => $order, 'release_week' => 3,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit3_id,
            'title' => $topic['title'] . ' — Lesson',
            'content' => mtti_lesson_theory_html(array('title' => $topic['title'], 'lesson_tag' => $tag, 'sections' => $topic['lesson']['sections'])),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 3,
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
            'course_id' => 9, 'unit_id' => $unit3_id,
            'title' => $topic['title'] . ' — Practical',
            'content' => mtti_python_lab_html($practical_config),
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 3,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$lessons_table} WHERE lesson_id = %d", 460));
        $quiz_html = mtti_build_final_quiz_html($template, $topic['title'] . ' — Quiz', $topic['quiz']);
        $wpdb->insert($lessons_table, array(
            'course_id' => 9, 'unit_id' => $unit3_id,
            'title' => $topic['title'] . ' — Quiz',
            'content' => $quiz_html,
            'content_type' => 'html_interactive', 'interactive_role' => null,
            'order_number' => $order, 'release_week' => 3,
            'is_locked' => 1, 'is_free_preview' => 0, 'status' => 'Published', 'deleted_at' => null,
        ));
        $order++;

        $order = ceil($order / 10) * 10;
        echo "Finished topic: {$topic['title']}\n";
    }

    echo "Unit 3 build complete.\n";
}

<?php
/**
 * WDD-01 content completion — replaces the 37 placeholder "Overview"
 * lessons with real, topic-specific content (derived from each topic's
 * actual Lesson headings) and rebuilds the Final Assessment quiz.
 *
 * Video lessons are deliberately untouched — the existing render code
 * already shows an honest "Video Coming Soon" state for those.
 *
 * Usage: wp eval-file complete-wdd01-overviews.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

function mtti_objectives_fragment($intro, array $learn_points) {
    $li = '';
    foreach ($learn_points as $point) {
        $li .= '<li>' . esc_html($point) . '</li>';
    }
    return '<p class="mtti-obj-intro">' . esc_html($intro) . '</p>'
         . '<h3 class="mtti-obj-heading">What You\'ll Learn</h3>'
         . '<ul class="mtti-obj-list">' . $li . '</ul>';
}

/**
 * Splices a new `exercises` array into an existing, proven quiz
 * document's CSS/HTML/JS shell — reuses the tested rendering/scoring
 * engine untouched, only the data and title change.
 */
function mtti_build_final_quiz_html($template_content, $new_title, array $exercises) {
    $start = strpos($template_content, 'const exercises');
    $end   = strpos($template_content, '];', $start);
    if ($start === false || $end === false) {
        return false;
    }
    $before = substr($template_content, 0, $start);
    $after  = substr($template_content, $end + 2);

    // Swap the <title> and any visible header heading text that matches
    // the template's own title, without touching the CSS/JS engine.
    $before = preg_replace('/<title>.*?<\/title>/s', '<title>' . esc_html($new_title) . '</title>', $before, 1);

    $exercises_js = 'const exercises = ' . wp_json_encode($exercises, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ';';

    return $before . $exercises_js . $after;
}

// ---------------------------------------------------------------------
// 37 Overview fragments — intro + "What You'll Learn" derived from each
// topic's real Lesson headings (extracted and verified beforehand).
// ---------------------------------------------------------------------

$overviews = array(
    // Unit 28 — HTML Fundamentals
    456 => array(
        'intro'  => "Before writing a single line of HTML, it helps to know what actually happens when someone visits a website — this lesson covers the journey from typing an address to seeing a page on screen.",
        'points' => array("How your browser (the client) and a web server talk to each other", "What happens during a request and its response", "How domain names get translated into IP addresses"),
    ),
    461 => array(
        'intro'  => "Time to write real HTML — this lesson walks through the minimum structure every web page needs and gets your first page on screen.",
        'points' => array("The basic skeleton every HTML document needs", "The difference between the head and the body", "Why most HTML tags come in opening/closing pairs"),
    ),
    466 => array(
        'intro'  => "Most of the web is still just text, organized well — this lesson covers the everyday tags you'll use constantly to structure content and connect pages together.",
        'points' => array("Structuring content with headings and paragraphs", "Building ordered and unordered lists", "Linking between pages with anchor tags"),
    ),
    471 => array(
        'intro'  => "Words alone rarely tell the whole story — this lesson covers adding images to a page the right way, including why alt text isn't optional.",
        'points' => array("Adding images with the img element", "Why alt text matters for accessibility and SEO", "Choosing the right image file format for the job"),
    ),
    476 => array(
        'intro'  => "Tables are for tabular data, not page layout — this lesson covers building tables properly and knowing when they're actually the right tool.",
        'points' => array("The structure of an HTML table", "Why header cells matter for accessibility", "When to use a table — and when not to"),
    ),
    481 => array(
        'intro'  => "Forms are how websites collect information from visitors — this lesson covers building one that's both functional and accessible.",
        'points' => array("Setting up the form element correctly", "The most common input types you'll use", "Why every input needs a proper label"),
    ),
    486 => array(
        'intro'  => 'HTML5 gives you tags that describe what content actually is, not just how it looks — this lesson covers writing markup that means something.',
        'points' => array('What "semantic" HTML actually means', "Why it matters for accessibility and SEO, not just appearance", "When a plain div is still the right choice"),
    ),
    491 => array(
        'intro'  => "Clean, valid HTML is easier to maintain and works for more people — this lesson covers checking your work and building accessibility in from the start, using the Nakuru Hardware Hub example site as a real case study.",
        'points' => array("Validating your HTML to catch mistakes early", "Everyday accessibility habits worth building now", "Why this matters in practice, using the Nakuru Hardware Hub case study"),
    ),

    // Unit 29 — CSS Styling and Layouts
    496 => array(
        'intro'  => "CSS starts with being able to target exactly the element you want to style — this lesson covers selectors and the box model that governs every element's size and spacing.",
        'points' => array("Using selectors to target exactly what you want to style", "The difference between classes and IDs", "How the CSS box model controls size and spacing"),
    ),
    501 => array(
        'intro'  => "Good typography makes a page easier to read before a visitor even processes the words — this lesson covers the CSS properties that control it.",
        'points' => array("Setting font families with sensible fallbacks", "Sizing and spacing text so it's actually readable", "Using font weight and style for emphasis"),
    ),
    506 => array(
        'intro'  => "Colour affects both how a site looks and whether people can actually use it — this lesson covers the formats CSS supports and how to use colour responsibly.",
        'points' => array("The different colour formats CSS supports", "Why contrast matters for readability and accessibility", "Keeping colour use consistent across a site"),
    ),
    511 => array(
        'intro'  => "Flexbox solves layout problems that used to require hacks — this lesson covers how it works and when to reach for it.",
        'points' => array("The layout problems Flexbox was built to solve", "Working with the main axis and alignment", "Recognizing when Flexbox is the right tool"),
    ),
    516 => array(
        'intro'  => "Grid handles layouts Flexbox can't — this lesson covers building true two-dimensional layouts and knowing which tool fits which job.",
        'points' => array("Building two-dimensional layouts with Grid", "Placing items precisely within a grid", "Choosing between Grid and Flexbox for a given layout"),
    ),
    521 => array(
        'intro'  => "Most visitors won't be on a desktop screen — this lesson covers making a site work well at any size using media queries.",
        'points' => array("Why responsive design matters for real visitors", "Writing media queries that adapt a layout", "The difference between mobile-first and desktop-first approaches"),
    ),
    526 => array(
        'intro'  => "A little motion can make an interface feel more polished — this lesson covers CSS transitions and using animation with restraint.",
        'points' => array("Adding smooth CSS transitions", "Deciding what's actually worth animating", "Using motion purposefully instead of just for show"),
    ),
    531 => array(
        'intro'  => "As a stylesheet grows, structure starts to matter as much as the styles themselves — this lesson covers CSS custom properties and keeping things organized.",
        'points' => array("Using CSS custom properties (variables)", "Why this matters once a project grows past one page", "Organizing a stylesheet so it stays manageable"),
    ),

    // Unit 30 — JavaScript Basics
    536 => array(
        'intro'  => "Every JavaScript program starts with storing and working with data — this lesson covers declaring variables and the basic types you'll use constantly.",
        'points' => array("Declaring variables the right way", "JavaScript's basic data types", "Why a value's type affects how it behaves"),
    ),
    541 => array(
        'intro'  => "Functions let you package up logic and reuse it — this lesson covers writing them properly and understanding where variables are visible.",
        'points' => array("Defining and calling functions", "Working with parameters and return values", "How scope determines where a variable is accessible"),
    ),
    546 => array(
        'intro'  => "JavaScript's real power on the web is changing what's on the page — this lesson covers the DOM, the structure that makes that possible.",
        'points' => array("What the DOM actually is", "Selecting elements on the page", "Changing content dynamically with JavaScript"),
    ),
    551 => array(
        'intro'  => "Interactive pages respond to what a visitor does — this lesson covers listening for events like clicks and inputs, and reading what happened.",
        'points' => array("What an event is in the browser", "Attaching event listeners to elements", "Reading useful information from the event object"),
    ),
    556 => array(
        'intro'  => "Real programs make decisions and repeat work — this lesson covers the conditionals and loops that let your code do both.",
        'points' => array("Writing if/else conditionals", "Using comparison and logical operators", "Repeating work with loops"),
    ),
    561 => array(
        'intro'  => "Most real data doesn't fit in a single variable — this lesson covers arrays and objects, and combining them to model real-world data.",
        'points' => array("Working with arrays", "Working with objects", "Combining both as arrays of objects"),
    ),
    566 => array(
        'intro'  => "Modern web pages pull in data without reloading — this lesson covers the Fetch API and handling its asynchronous responses.",
        'points' => array("What the Fetch API does", "Why fetch is asynchronous and what that means for your code", "Working with the data fetch returns"),
    ),
    571 => array(
        'intro'  => "JavaScript can make forms smarter before data ever reaches a server — this lesson covers reading input and validating it on the client side.",
        'points' => array("Reading values from form input", "Validating input on the client side", "Preventing a form's default submission when needed"),
    ),
    576 => array(
        'intro'  => "Sometimes a page needs to remember something between visits — this lesson covers local storage and its real limits.",
        'points' => array("What local storage is and when to use it", "Reading and writing values to local storage", "What local storage should never be used for"),
    ),
    581 => array(
        'intro'  => "Every developer spends real time debugging — this lesson covers the browser tools that make finding and fixing problems far less painful.",
        'points' => array("Using the browser console effectively", "Reading and understanding error messages", "Inspecting elements to see what's really happening on the page"),
    ),

    // Unit 31 — Version Control and Deployment
    586 => array(
        'intro'  => 'Version control is what stops a project from turning into a mess of "final_v2_FINAL" files — this lesson covers Git and the workflow you\'ll use on every project from here on.',
        'points' => array("The problem version control actually solves", "The core Git workflow you'll use daily", "Undoing mistakes safely instead of losing work"),
    ),
    591 => array(
        'intro'  => "Git and GitHub aren't the same thing — this lesson covers the difference and getting your code hosted online.",
        'points' => array("The difference between Git and GitHub", "Pushing and pulling code to a remote repository", "Why hosting your code on GitHub is worth doing"),
    ),
    596 => array(
        'intro'  => "You don't need paid hosting to put a real site online — this lesson covers GitHub Pages and what it can and can't do.",
        'points' => array("What free static hosting via GitHub Pages offers", "Enabling GitHub Pages for a repository", "The real limits of static-only hosting"),
    ),
    601 => array(
        'intro'  => "A real project usually needs a real domain and proper hosting — this lesson covers domain names, DNS, and cPanel hosting.",
        'points' => array("How domain names work", "Connecting a domain to a host through DNS", "Working with cPanel-based hosting"),
    ),
    606 => array(
        'intro'  => "A slow site loses visitors before they see anything — this lesson covers practical performance wins and measuring them properly.",
        'points' => array("Why site speed matters for visitors and search ranking", "Common, practical performance wins", "Measuring performance instead of guessing"),
    ),
    611 => array(
        'intro'  => "Launching a site is more than uploading files — this lesson covers the checklist worth running through before and after going live.",
        'points' => array("A practical pre-launch checklist", "Uploading the final files correctly", "What to check once a site is actually live"),
    ),

    // Unit 133 — AI for Web Developers
    616 => array(
        'intro'  => "AI coding tools are now part of most developers' workflow — this lesson covers what they actually do and where they genuinely help.",
        'points' => array("What AI coding assistants actually do", "Where they realistically fit into a developer's workflow", "Why this is a fast-moving space worth staying current on"),
    ),
    620 => array(
        'intro'  => "The quality of AI-generated code depends heavily on how you ask for it — this lesson covers writing prompts that actually get useful results.",
        'points' => array("Why vague prompts produce vague, unreliable code", "Providing the context an AI assistant actually needs", "Iterating on AI output instead of accepting the first result"),
    ),
    624 => array(
        'intro'  => "Most AI coding help now happens right inside the editor — this lesson covers using it deliberately rather than on autopilot.",
        'points' => array("How AI assistance works inside a code editor", "Accepting suggestions deliberately, not automatically", "Building a practical, sustainable habit around it"),
    ),
    628 => array(
        'intro'  => "AI-generated code can look correct and still be wrong — this lesson covers the pitfalls worth knowing before you ship anything it wrote.",
        'points' => array("Why AI can be confidently wrong", "Security and sensitive-data risks to watch for", "Why you must understand any code you ship, AI-written or not"),
    ),

    // Unit 134 — Final Assessment (no source Lesson content to draw from)
    632 => array(
        'intro'  => "This is your final assessment for the course — a comprehensive exam covering everything from HTML fundamentals through CSS layouts, JavaScript, Git and deployment, to working with AI coding tools.",
        'points' => array("HTML structure, forms, and accessibility", "CSS layout with Flexbox, Grid, and responsive design", "JavaScript fundamentals, the DOM, and events", "Version control, deployment, and working effectively with AI coding assistants"),
    ),
);

// ---------------------------------------------------------------------
// Final Assessment exam — 18 questions spanning all 5 real content units
// ---------------------------------------------------------------------

$final_exercises = array(
    array('id' => 'q1', 'text' => 'What is the purpose of DNS when you type a website address into your browser?', 'options' => array('It encrypts the connection', 'It translates the domain name into an IP address', 'It compresses the HTML file', 'It validates your HTML'), 'answer' => 1, 'explanation' => 'DNS resolves a human-readable domain name into the numeric IP address the browser needs to actually connect to a server.'),
    array('id' => 'q2', 'text' => 'Why should you add alt text to an img element?', 'options' => array("It changes the image's file size", 'It provides a text alternative for screen readers and when the image fails to load', 'It makes the image load faster', "It's required by every browser to display the image"), 'answer' => 1, 'explanation' => 'Alt text describes the image for screen-reader users and displays as a fallback if the image cannot load — it has no effect on load speed or file size.'),
    array('id' => 'q3', 'text' => "Why is it important to associate a <label> with its <input> in an HTML form?", 'options' => array('It is purely decorative', 'It improves accessibility and lets clicking the label focus the input', 'It is required for the form to submit', "It changes the input's data type"), 'answer' => 1, 'explanation' => 'A properly associated label is read out by screen readers and expands the clickable/tappable area for the input, which matters for both accessibility and usability.'),
    array('id' => 'q4', 'text' => 'What is one practical benefit of validating your HTML?', 'options' => array('It automatically improves page speed', 'It catches structural mistakes that can cause unpredictable rendering across browsers', 'It removes the need for CSS', 'It encrypts the page content'), 'answer' => 1, 'explanation' => "Invalid HTML (like unclosed tags or incorrect nesting) can render inconsistently between browsers — validation catches these mistakes early."),
    array('id' => 'q5', 'text' => "In the CSS box model, which property adds space INSIDE an element's border, between the border and the content?", 'options' => array('margin', 'padding', 'border-spacing', 'gap'), 'answer' => 1, 'explanation' => 'Padding sits between the content and the border, inside the element. Margin is the space outside the border, between elements.'),
    array('id' => 'q6', 'text' => 'You need to lay out a page with distinct rows AND columns that align together, like a photo gallery grid. Which CSS layout tool is generally the better fit?', 'options' => array('Flexbox', 'CSS Grid', 'Float', 'Table layout'), 'answer' => 1, 'explanation' => 'Grid is built for two-dimensional layouts (rows and columns together); Flexbox is generally better suited to one-dimensional layouts along a single axis.'),
    array('id' => 'q7', 'text' => "What does a 'mobile-first' approach to media queries mean?", 'options' => array('Only mobile devices are supported', 'Base styles target small screens, with media queries adding styles for larger screens', 'Desktop styles are written first, then overridden for mobile', 'It means using vw units exclusively'), 'answer' => 1, 'explanation' => 'Mobile-first means your unqualified CSS rules are the small-screen baseline, and min-width media queries progressively add complexity for larger viewports.'),
    array('id' => 'q8', 'text' => 'What is one advantage of using CSS custom properties (variables) in a growing stylesheet?', 'options' => array('They make the CSS file load faster over the network', 'Changing a value in one place updates every rule that uses it', 'They replace the need for a box model', 'They are required for responsive design to work'), 'answer' => 1, 'explanation' => "Custom properties centralize a value (like a brand colour) so updating it in one place cascades everywhere it's used — a real maintainability win as a project grows."),
    array('id' => 'q9', 'text' => "Which of these is a valid reason to prefer const over var when declaring a variable you won't reassign?", 'options' => array('const is faster to type', 'const prevents the variable from being accidentally reassigned, catching bugs earlier', 'const variables are automatically global', 'var no longer works in modern browsers'), 'answer' => 1, 'explanation' => 'const signals intent and causes an error if the variable is accidentally reassigned later, which catches a class of bugs before they cause problems.'),
    array('id' => 'q10', 'text' => 'A variable declared inside a function with let is generally NOT accessible...', 'options' => array('...anywhere in the same function', '...outside that function, due to scope', '...within the same block', '...to other statements in the same function'), 'answer' => 1, 'explanation' => "Variables declared inside a function are scoped to that function (and its blocks) — they aren't visible to code outside it."),
    array('id' => 'q11', 'text' => "What does document.querySelector('.card') do?", 'options' => array("Creates a new element with the class 'card'", "Selects and returns the first element in the page matching the CSS class 'card'", "Deletes all elements with the class 'card'", "Applies the 'card' CSS class to the whole document"), 'answer' => 1, 'explanation' => 'querySelector returns the first matching element for the given CSS selector — it reads the DOM, it does not create, delete, or apply anything.'),
    array('id' => 'q12', 'text' => "What is the purpose of event.preventDefault() inside a form's submit event handler?", 'options' => array('It deletes the form from the page', "It stops the browser's default action, e.g. reloading the page on submit", 'It clears all input values', 'It prevents the event listener from ever running again'), 'answer' => 1, 'explanation' => "preventDefault() stops the browser's built-in behaviour for that event (like a full page reload on form submit) so your own JavaScript can handle it instead."),
    array('id' => 'q13', 'text' => 'Why does working with the Fetch API typically involve async/await or .then()?', 'options' => array('Because fetch always fails on the first attempt', 'Because fetch is asynchronous — the response is not available immediately', 'Because fetch only works with images', 'Because it is required by CSS'), 'answer' => 1, 'explanation' => 'Fetch returns a Promise because the network request takes time to complete — async/await or .then() lets your code wait for that response without blocking everything else.'),
    array('id' => 'q14', 'text' => 'In Git, what does git commit do that git add does not?', 'options' => array('It uploads your code to GitHub', "It saves a snapshot of your staged changes to the project's history", 'It deletes old versions of your files', 'It creates a new branch automatically'), 'answer' => 1, 'explanation' => 'git add stages changes for the next commit; git commit actually records those staged changes as a permanent snapshot in the local history. Pushing to GitHub is a separate step (git push).'),
    array('id' => 'q15', 'text' => 'What is a key limitation of GitHub Pages as a hosting option?', 'options' => array('It cannot host HTML at all', 'It only serves static files — no server-side code like PHP can run there', 'It requires a paid GitHub account', 'It only works for private repositories'), 'answer' => 1, 'explanation' => 'GitHub Pages serves static files (HTML/CSS/JS) directly — it has no server-side runtime, so PHP, databases, or other backend code cannot run there.'),
    array('id' => 'q16', 'text' => "Which of these is a genuine, measurable way to improve a page's load performance?", 'options' => array('Adding more images without optimizing them', 'Compressing and properly sizing images before uploading them', 'Removing all CSS', 'Using inline styles everywhere instead of a stylesheet'), 'answer' => 1, 'explanation' => 'Unoptimized images are one of the most common causes of slow page loads — compressing and correctly sizing them is one of the highest-impact, easiest performance wins.'),
    array('id' => 'q17', 'text' => "Why does a vague prompt like 'make a button' typically produce less useful AI-generated code than a detailed one?", 'options' => array('Vague prompts always cause errors', 'The AI has to guess at requirements like styling, behaviour, and context it was not given', 'AI assistants can only respond to exact code syntax', 'Vague prompts are automatically rejected by AI tools'), 'answer' => 1, 'explanation' => "Without details on styling, behaviour, and context, an AI assistant fills the gaps with generic guesses — the more relevant context you provide, the more useful the result."),
    array('id' => 'q18', 'text' => 'Why is it important to review and understand AI-generated code before shipping it, rather than trusting it outright?', 'options' => array('AI-generated code is always slower', 'AI can produce code that looks correct but contains bugs, security issues, or does not fit the actual requirement', 'AI-generated code cannot be edited afterward', 'It is a legal requirement in every country'), 'answer' => 1, 'explanation' => "AI-generated code can be confidently wrong — syntactically valid and plausible-looking while still containing real bugs or security issues, so it needs the same scrutiny as any other code before shipping."),
);

// ---------------------------------------------------------------------
// Apply (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_WDD01_OVERVIEW_IMPORT') && MTTI_RUN_WDD01_OVERVIEW_IMPORT) {
    global $wpdb;
    $table = $wpdb->prefix . 'mtti_lessons';

    foreach ($overviews as $lesson_id => $ov) {
        $html = mtti_objectives_fragment($ov['intro'], $ov['points']);
        $result = $wpdb->update($table, array('content' => $html), array('lesson_id' => $lesson_id, 'course_id' => 6));
        echo "Overview lesson_id={$lesson_id}: " . ($result !== false ? 'updated' : 'FAILED') . "\n";
    }

    $template = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$table} WHERE lesson_id = %d", 460));
    if (!$template) {
        echo "ERROR: could not load template lesson 460\n";
    } else {
        $final_html = mtti_build_final_quiz_html($template, 'WDD-01 Final Assessment', $final_exercises);
        if ($final_html === false) {
            echo "ERROR: could not splice exercises array into template\n";
        } else {
            $result = $wpdb->update($table, array('content' => $final_html), array('lesson_id' => 633, 'course_id' => 6));
            echo "Final Assessment quiz lesson_id=633: " . ($result !== false ? 'updated' : 'FAILED') . "\n";
        }
    }
}

<?php
/**
 * Python Hands-On Labs — generator + one-time importer for PRP-01
 * (Programming/Coding Principles, course_id=9).
 *
 * Builds a self-contained "PyLab" lesson document (real Python execution
 * in-browser via Pyodide/WebAssembly — no backend needed) and inserts the
 * 3 pilot lab lessons. Model: import-interactives.php's array-driven
 * insert pattern. Usage: wp eval-file generate-python-labs.php
 */

if (!defined('ABSPATH')) {
    die('This script must be run from WordPress context');
}

/**
 * Builds the complete standalone HTML document for one Python lab lesson.
 *
 * $config: [
 *   'title'           => string,
 *   'lesson_tag'      => string,
 *   'brief'           => string,
 *   'starter'         => string (Python source),
 *   'checks'          => [ ['type' => 'output_equals'|'output_contains'|'no_error'|'source_contains', 'value' => string|null, 'label' => string], ... ],
 *   'pyodide_version' => string, e.g. 'v0.26.4',
 *   'simulated_inputs' => string[] (optional) — pre-set values fed to input() calls in order,
 * ]
 */
function mtti_python_lab_html(array $config) {
    $title       = $config['title'] ?? 'Python Lab';
    $lesson_tag  = $config['lesson_tag'] ?? 'Coding Principles · Lab';
    $brief       = $config['brief'] ?? '';
    $pyver       = $config['pyodide_version'] ?? 'v0.26.4';

    $js_config_data = array(
        'title'   => $title,
        'brief'   => $brief,
        'starter' => $config['starter'] ?? '',
        'checks'  => $config['checks'] ?? array(),
        'pyodide_version' => $pyver,
    );
    if (!empty($config['simulated_inputs'])) {
        $js_config_data['simulated_inputs'] = $config['simulated_inputs'];
    }
    $js_config = wp_json_encode($js_config_data);

    $title_esc = esc_html($title);
    $tag_esc   = esc_html($lesson_tag);
    $brief_esc = esc_html($brief);

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title_esc} | MTTI Coding Principles</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Fredoka:wght@500;600&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Space Mono', monospace;
    background: linear-gradient(135deg, #2E7D32 0%, #1565C0 100%);
    min-height: 100vh; padding: 20px; color: #fff;
}
.container { max-width: 1000px; margin: 0 auto; }
.header { text-align: center; margin-bottom: 26px; }
.lesson-tag { display: inline-block; background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 999px; font-size: 0.85em; margin-bottom: 12px; }
.header h1 { font-family: 'Fredoka', sans-serif; font-size: 2.2em; margin-bottom: 8px; text-shadow: 3px 3px 0 rgba(0,0,0,0.2); }
.brief {
    background: rgba(255,255,255,0.12); backdrop-filter: blur(10px);
    border-radius: 18px; padding: 18px 22px; margin-bottom: 20px;
    border-left: 5px solid #ffd54f;
}
.brief h3 { font-family: 'Fredoka', sans-serif; font-size: 1.05em; margin-bottom: 8px; color: #ffd54f; }
.brief p { font-size: 0.92em; line-height: 1.55; }
.task {
    background: white; color: #333; border-radius: 22px;
    padding: 26px; margin-bottom: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.wg-code-split { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 10px 0 16px; }
.wg-code-editor-wrap, .wg-py-output-wrap { display: flex; flex-direction: column; gap: 6px; }
.wg-code-lang-badge, .wg-py-output-label { font-size: 0.75em; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; }
.wg-code-editor {
    font-family: 'Courier New', monospace; font-size: 0.85em; line-height: 1.6;
    min-height: 280px; resize: vertical; border: 2px solid #e0e0e0; border-radius: 8px;
    padding: 12px; background: #1e1e2e; color: #cdd6f4; outline: none; tab-size: 4;
}
.wg-code-editor:focus { border-color: #2E7D32; box-shadow: 0 0 0 3px rgba(46,125,50,0.15); }
.wg-py-output {
    border: 2px solid #e0e0e0; border-radius: 8px; min-height: 280px;
    background: #1e1e2e; color: #a6e3a1; width: 100%; padding: 12px;
    font-family: 'Courier New', monospace; font-size: 0.85em; line-height: 1.6;
    white-space: pre-wrap; word-break: break-word; overflow-y: auto; margin: 0;
}
.wg-py-output.has-error { color: #f38ba8; }
.wg-code-checks { list-style: none; padding: 0; margin: 4px 0 0; display: flex; flex-direction: column; gap: 6px; }
.wg-code-check { display: flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 8px; font-size: 0.88em; background: #f5f7fb; border: 1.5px solid #e0e0e0; transition: all 0.2s; }
.wg-code-check.ok { background: #d4edda; border-color: #28a745; color: #155724; }
.wg-code-check.fail { background: #f8d7da; border-color: #dc3545; color: #721c24; }
.wg-code-check.no { color: #555; }
.wg-code-check-icon { font-weight: bold; min-width: 16px; }
.wg-py-controls { display: flex; gap: 10px; align-items: center; margin-bottom: 14px; flex-wrap: wrap; }
.wg-py-run-btn, .wg-py-reset-btn {
    border: none; padding: 12px 28px; border-radius: 10px; font-size: 0.95em;
    cursor: pointer; font-family: 'Fredoka', sans-serif; font-weight: 600;
    transition: transform 0.15s ease;
}
.wg-py-run-btn { background: linear-gradient(135deg, #2E7D32 0%, #1565C0 100%); color: white; box-shadow: 0 6px 18px rgba(0,0,0,0.2); }
.wg-py-run-btn:hover:not(:disabled) { transform: scale(1.04); }
.wg-py-run-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.wg-py-reset-btn { background: #f0f2f5; color: #555; }
.wg-py-reset-btn:hover { background: #e2e6ea; }
.wg-py-status { font-size: 0.82em; padding: 6px 12px; border-radius: 999px; background: #f0f2f5; color: #666; }
.wg-py-status.loading { background: #fff8e1; color: #b8860b; }
.wg-py-status.ready { background: #d4edda; color: #155724; }
.wg-py-status.error { background: #f8d7da; color: #721c24; }
.wg-py-input-note { font-size: 0.82em; color: #1565C0; background: #e3f2fd; border-radius: 8px; padding: 8px 12px; margin: -6px 0 14px; }
@media (max-width: 640px) { .wg-code-split { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="lesson-tag">{$tag_esc}</div>
        <h1>{$title_esc}</h1>
    </div>
    <div class="brief">
        <h3>📋 Your Task</h3>
        <p>{$brief_esc}</p>
    </div>
    <div class="task" id="root"></div>
</div>
<script>
/* ---------- PyLab: real in-browser Python via Pyodide ---------- */
class PyLab {
    constructor(cfg) {
        this.cfg = cfg;
        this.code = cfg.starter || '';
        this.pyodide = null;
        this.running = false;
        this._stdout = [];
        this._lastError = null;
        this._inputQueue = [];
    }

    render(container) {
        this.container = container;

        const controls = document.createElement('div');
        controls.className = 'wg-py-controls';
        this.runBtn = document.createElement('button');
        this.runBtn.className = 'wg-py-run-btn';
        this.runBtn.textContent = 'Run ▶';
        this.runBtn.addEventListener('click', () => this.run());
        this.resetBtn = document.createElement('button');
        this.resetBtn.className = 'wg-py-reset-btn';
        this.resetBtn.textContent = 'Reset';
        this.resetBtn.addEventListener('click', () => this.resetCode());
        this.statusEl = document.createElement('span');
        this.statusEl.className = 'wg-py-status loading';
        this.statusEl.textContent = '⏳ Loading Python engine… (first time only, ~6-10MB)';
        controls.appendChild(this.runBtn);
        controls.appendChild(this.resetBtn);
        controls.appendChild(this.statusEl);
        container.appendChild(controls);

        if (this.cfg.simulated_inputs && this.cfg.simulated_inputs.length) {
            const inputNote = document.createElement('p');
            inputNote.className = 'wg-py-input-note';
            inputNote.textContent = 'This exercise uses pre-set input() values, in order: ' + this.cfg.simulated_inputs.map(v => '"' + v + '"').join(', ') + '.';
            container.appendChild(inputNote);
        }

        const split = document.createElement('div');
        split.className = 'wg-code-split';

        const editorWrap = document.createElement('div');
        editorWrap.className = 'wg-code-editor-wrap';
        const badge = document.createElement('div');
        badge.className = 'wg-code-lang-badge';
        badge.textContent = 'PYTHON';
        this.editor = document.createElement('textarea');
        this.editor.className = 'wg-code-editor';
        this.editor.spellcheck = false;
        this.editor.value = this.code;
        this.bindEditorEvents();
        editorWrap.appendChild(badge);
        editorWrap.appendChild(this.editor);
        split.appendChild(editorWrap);

        const outputWrap = document.createElement('div');
        outputWrap.className = 'wg-py-output-wrap';
        const outLabel = document.createElement('div');
        outLabel.className = 'wg-py-output-label';
        outLabel.textContent = 'Output';
        this.output = document.createElement('pre');
        this.output.className = 'wg-py-output';
        this.output.textContent = 'Click Run to see your output here…';
        outputWrap.appendChild(outLabel);
        outputWrap.appendChild(this.output);
        split.appendChild(outputWrap);

        container.appendChild(split);

        this.checkList = document.createElement('ul');
        this.checkList.className = 'wg-code-checks';
        (this.cfg.checks || []).forEach((c, i) => {
            const li = document.createElement('li');
            li.className = 'wg-code-check no';
            li.dataset.i = String(i);
            li.innerHTML = '<span class="wg-code-check-icon">○</span> ' + this._esc(c.label || '');
            this.checkList.appendChild(li);
        });
        container.appendChild(this.checkList);

        this.ensurePyodide();
    }

    _esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    bindEditorEvents() {
        this.editor.addEventListener('keydown', e => {
            if (e.key === 'Tab') {
                e.preventDefault();
                const s = this.editor.selectionStart;
                const v = this.editor.value;
                this.editor.value = v.slice(0, s) + '    ' + v.slice(this.editor.selectionEnd);
                this.editor.selectionStart = this.editor.selectionEnd = s + 4;
                this.code = this.editor.value;
            }
        });
        this.editor.addEventListener('input', () => { this.code = this.editor.value; });
    }

    resetCode() {
        this.code = this.cfg.starter || '';
        this.editor.value = this.code;
    }

    async ensurePyodide() {
        if (window.__mttiPyodideReady) {
            try {
                this.pyodide = await window.__mttiPyodideReady;
                this._setStatus('ready', '✓ Python ready');
            } catch (e) {
                this._setStatus('error', '⚠ Failed to load — reload the page');
            }
            return;
        }

        const version = this.cfg.pyodide_version || 'v0.26.4';
        window.__mttiPyodideReady = (async () => {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/pyodide/' + version + '/full/pyodide.js';
                s.onload = resolve;
                s.onerror = () => reject(new Error('Failed to load Pyodide script'));
                document.head.appendChild(s);
            });
            const pyodide = await loadPyodide({
                indexURL: 'https://cdn.jsdelivr.net/pyodide/' + version + '/full/'
            });
            const capturedOut = [];
            const capturedErr = [];
            pyodide.__mttiOut = capturedOut;
            pyodide.__mttiErr = capturedErr;
            pyodide.setStdout({ batched: (msg) => capturedOut.push(msg) });
            pyodide.setStderr({ batched: (msg) => capturedErr.push(msg) });
            // Step-count guard against infinite loops — a real, catchable
            // interrupt (fires on every line/call/return event), not a
            // cosmetic timer. See plan notes: setTimeout can't fire while
            // Pyodide is running synchronously on the main thread.
            pyodide.runPython(`
import sys
_MTTI_STEP_LIMIT = 4_000_000
_mtti_steps = 0
class MttiTimeout(Exception):
    pass
def _mtti_trace(frame, event, arg):
    global _mtti_steps
    _mtti_steps += 1
    if _mtti_steps > _MTTI_STEP_LIMIT:
        raise MttiTimeout("Execution stopped: your program ran too long. Check for an infinite loop (e.g. a while-loop condition that never becomes False).")
    return _mtti_trace
def _mtti_reset_guard():
    global _mtti_steps
    _mtti_steps = 0
    sys.settrace(_mtti_trace)
`);
            return pyodide;
        })();

        try {
            this.pyodide = await window.__mttiPyodideReady;
            this._setStatus('ready', '✓ Python ready');
        } catch (e) {
            this._setStatus('error', '⚠ Failed to load — reload the page');
        }
    }

    _setStatus(cls, text) {
        this.statusEl.className = 'wg-py-status ' + cls;
        this.statusEl.textContent = text;
    }

    async run() {
        if (this.running) return;
        this.running = true;
        this.runBtn.disabled = true;
        this.runBtn.textContent = 'Running…';
        this.output.classList.remove('has-error');
        this.output.textContent = '';
        this._lastError = null;

        if (!this.pyodide) {
            await this.ensurePyodide();
        }
        if (!this.pyodide) {
            this.output.classList.add('has-error');
            this.output.textContent = 'Python engine failed to load. Please reload the page and try again.';
            this.running = false;
            this.runBtn.disabled = false;
            this.runBtn.textContent = 'Run ▶';
            return;
        }

        this.pyodide.__mttiOut.length = 0;
        this.pyodide.__mttiErr.length = 0;

        // Feed pre-configured values to input() calls, in order — Pyodide
        // has no interactive stdin by default, and a live blocking prompt
        // wouldn't work with automated checks anyway. Re-wired fresh each
        // run since the shared pyodide instance may be reused by other
        // PyLab widgets with their own simulated_inputs.
        this._inputQueue = (this.cfg.simulated_inputs || []).slice();
        const inputQueue = this._inputQueue;
        this.pyodide.setStdin({
            stdin: () => (inputQueue.length ? inputQueue.shift() : '')
        });

        try {
            this.pyodide.runPython('_mtti_reset_guard()');
            await this.pyodide.runPythonAsync(this.code);
        } catch (e) {
            this._lastError = this._formatPyError(e);
        }

        this.renderOutput();
        this.evaluateChecks();

        this.running = false;
        this.runBtn.disabled = false;
        this.runBtn.textContent = 'Run ▶';
    }

    _formatPyError(e) {
        const msg = (e && e.message) ? e.message : String(e);
        // Pyodide's PythonError message includes the full traceback; keep
        // just the last few lines (the actual error) for a clean display.
        const lines = msg.split('\\n').filter(Boolean);
        return lines.slice(-4).join('\\n');
    }

    renderOutput() {
        const stdout = this.pyodide.__mttiOut.join('');
        if (this._lastError) {
            this.output.classList.add('has-error');
            this.output.textContent = (stdout ? stdout + '\\n' : '') + this._lastError;
        } else {
            this.output.classList.remove('has-error');
            this.output.textContent = stdout || '(no output — did your code print anything?)';
        }
    }

    evaluateChecks() {
        const stdout = this.pyodide.__mttiOut.join('').replace(/\\n$/, '');
        const checks = this.cfg.checks || [];
        checks.forEach((c, i) => {
            let pass = false;
            if (c.type === 'output_equals') {
                pass = stdout === c.value;
            } else if (c.type === 'output_contains') {
                pass = stdout.includes(c.value);
            } else if (c.type === 'no_error') {
                pass = !this._lastError;
            } else if (c.type === 'source_contains') {
                pass = this.code.toLowerCase().includes(String(c.value || '').toLowerCase());
            }
            const li = this.checkList.querySelector('[data-i="' + i + '"]');
            if (!li) return;
            li.className = 'wg-code-check ' + (pass ? 'ok' : 'fail');
            li.querySelector('.wg-code-check-icon').textContent = pass ? '✓' : '✗';
        });
    }
}

const CONFIG = {$js_config};
new PyLab(CONFIG).render(document.getElementById('root'));
</script>
</body>
</html>
HTML;
}

// ---------------------------------------------------------------------
// Pilot lab configs
// ---------------------------------------------------------------------

$labs = array(
    array(
        'order_number' => 105,
        'release_week' => 1,
        'title'        => 'Lab: Your First Python Program',
        'config'       => array(
            'title'      => 'Lab: Your First Python Program',
            'lesson_tag' => 'Coding Principles · Lab · Companion to "Python Intro Lesson"',
            'brief'      => 'Sam introduces themselves online. Store their name and age in variables, then print a one-line introduction that uses both. This is the same pattern from "Your First Python Program" and "Variables & Data Types" — just running for real.',
            'starter'    => "name = \"Asha\"\nage = 20\n# Print a one-line introduction using both variables\n",
            'checks'     => array(
                array('type' => 'output_contains', 'value' => 'Asha', 'label' => 'Uses the name variable in the output'),
                array('type' => 'output_contains', 'value' => '20', 'label' => 'Uses the age variable in the output'),
                array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
            ),
            'pyodide_version' => 'v0.26.4',
        ),
    ),
    array(
        'order_number' => 145,
        'release_week' => 1,
        'title'        => 'Lab: Rectangle Area Algorithm',
        'config'       => array(
            'title'      => 'Lab: Rectangle Area Algorithm',
            'lesson_tag' => 'Coding Principles · Lab · Companion to "Algorithms Variables Lesson"',
            'brief'      => "Write the algorithm as a clear sequence of steps: store a rectangle's length and width in variables, calculate its area and perimeter, then print both — one calculation per step, exactly like the sequencing idea from this lesson's Algorithms section.",
            'starter'    => "length = 8\nwidth = 5\n# Step 1: calculate area\n# Step 2: calculate perimeter\n# Step 3: print both results\n",
            'checks'     => array(
                array('type' => 'output_equals', 'value' => "Area: 40\nPerimeter: 26", 'label' => 'Prints the exact area and perimeter'),
                array('type' => 'output_contains', 'value' => 'Area', 'label' => 'Output labels the area'),
                array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
            ),
            'pyodide_version' => 'v0.26.4',
        ),
    ),
    array(
        'order_number' => 165,
        'release_week' => 1,
        'title'        => 'Lab: Write the calculate_fee Function',
        'config'       => array(
            'title'      => 'Lab: Write the calculate_fee Function',
            'lesson_tag' => 'Coding Principles · Lab · Companion to "Coding Principles Lesson"',
            'brief'      => 'Finish the calculate_fee function from this lesson\'s example. It should apply a discount to a base fee and return the result. Call it exactly like the lesson does — calculate_fee(5000, 0.1) — and print the result.',
            'starter'    => "def calculate_fee(base_fee, discount_rate):\n    # TODO: return the discounted fee\n    pass\n\nresult = calculate_fee(5000, 0.1)\nprint(\"Fee after discount:\", result)\n",
            'checks'     => array(
                array('type' => 'output_equals', 'value' => 'Fee after discount: 4500.0', 'label' => 'Prints the correct discounted fee'),
                array('type' => 'source_contains', 'value' => 'return', 'label' => 'Function returns a value, not just prints'),
                array('type' => 'no_error', 'value' => null, 'label' => 'Code runs without errors'),
            ),
            'pyodide_version' => 'v0.26.4',
        ),
    ),
);

// ---------------------------------------------------------------------
// Insert (only runs when explicitly executed — never auto-included)
// ---------------------------------------------------------------------

if (defined('MTTI_RUN_PYTHON_LAB_IMPORT') && MTTI_RUN_PYTHON_LAB_IMPORT) {
    global $wpdb;
    $table = $wpdb->prefix . 'mtti_lessons';

    $admin_id = (int) $wpdb->get_var(
        "SELECT u.ID FROM {$wpdb->prefix}users u
         JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID
         WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%administrator%'
         LIMIT 1"
    );

    foreach ($labs as $lab) {
        $wpdb->insert($table, array(
            'course_id'        => 9,
            'unit_id'          => null,
            'title'            => $lab['title'],
            'content'          => mtti_python_lab_html($lab['config']),
            'content_type'     => 'html_interactive',
            'interactive_role' => null,
            'order_number'     => $lab['order_number'],
            'release_week'     => $lab['release_week'],
            'is_locked'        => 1,
            'is_free_preview'  => 0,
            'status'           => 'Published',
            'deleted_at'       => null,
            'created_by'       => $admin_id ?: null,
        ), array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d'));

        echo "Inserted lesson_id={$wpdb->insert_id} — {$lab['title']}\n";
    }
}

<?php
// One-time git deploy — self-deletes after run
$repo  = 'https://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@github.com/kenyaone/mtti-lms.git';
$dir   = __DIR__;

echo "<pre>";

// Check if git repo already initialised
if (!is_dir("$dir/.git")) {
    echo "Initialising git...\n";
    echo shell_exec("cd $dir && git init 2>&1");
    echo shell_exec("cd $dir && git remote add origin $repo 2>&1");
    echo shell_exec("cd $dir && git fetch origin main 2>&1");
    echo shell_exec("cd $dir && git reset --hard origin/main 2>&1");
} else {
    echo "Pulling latest...\n";
    echo shell_exec("cd $dir && git pull origin main 2>&1");
}

echo "\nDone.\n</pre>";
@unlink(__FILE__);

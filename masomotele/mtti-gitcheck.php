<?php
echo "<pre>";
echo shell_exec("which git 2>&1") ?: "git not found\n";
echo shell_exec("git --version 2>&1");
echo shell_exec("echo $HOME 2>&1");
echo "</pre>";
@unlink(__FILE__);

<?php

// Compiling a source file of the library raises no deprecation on the running PHP (8.4 made
// implicitly nullable parameters one): a notice that reaches the output breaks the caller's headers.
test('no source file raises a deprecation when compiled', function () {
    foreach (glob(dirname(__DIR__) . '/src/*.php') as $file) {
        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 -l ' . escapeshellarg($file) . ' 2>&1');
        expect($output)->not->toContain('Deprecated', basename($file));
    }
});

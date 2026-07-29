<?php
$target = dirname(__DIR__) . '/storage/app/public';
$link   = __DIR__ . '/storage';

if (file_exists($link) || is_link($link)) {
    echo 'Symlink already exists at: ' . $link;
} elseif (symlink($target, $link)) {
    echo 'Storage symlink created successfully!';
} else {
    echo 'Failed to create symlink. Try running as Administrator or run: php artisan storage:link';
}

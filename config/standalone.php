<?php

$enabled = app('pos.standalone.enabled') === true;
$root = $enabled ? dirname(app()->environmentPath()) : false;

return [
    'enabled' => $enabled,
    'data_root' => $root ?: null,
    'backup_path' => $root ? $root.'/backups' : base_path('backups'),
    'mysql_binary' => $enabled ? env('POS_MYSQL_PATH', '') : 'mysql',
    'mysqldump_binary' => $enabled ? env('POS_MYSQLDUMP_PATH', '') : 'mysqldump',
];

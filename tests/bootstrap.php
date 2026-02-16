<?php

/*
 * Force test database to SQLite in-memory so tests work the same in CLI and Docker.
 * Without this, .env in the container can override phpunit.xml and break tests.
 */
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

require_once __DIR__ . '/../vendor/autoload.php';

<?php
require_once __DIR__ . '/bootstrap.php';

session_start();
session_destroy();

header('Location: /');
exit;

<?php
// Simple harness to test member login via login_process.php
$_POST['email'] = 'demo'; // try username
$_POST['password'] = 'password123';
include 'login_process.php';

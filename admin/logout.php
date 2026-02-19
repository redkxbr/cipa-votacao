<?php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['admin_id'], $_SESSION['admin_username']);
flash('success', 'Logout realizado.');
redirect(url('admin/login.php'));

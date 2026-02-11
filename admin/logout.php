<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

adminLogout();
setFlash('success', 'Logout realizado.');
redirect(url('admin/login.php'));

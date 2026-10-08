<?php

/**
 * ============================================================
 * PropertyPro - Admin: Lock System Section
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

unset($_SESSION['system_verified_at']);

header('Location: verify-system.php');
exit;
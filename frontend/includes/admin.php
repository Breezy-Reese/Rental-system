<?php

require_once __DIR__ . "/auth.php";

function require_admin(): void
{
    if (!is_logged_in()) {
        header("Location: ../login.php");
        exit;
    }

    if (current_role() !== 'Administrator') {
        header("Location: ../customer/dashboard.php");
        exit;
    }
}

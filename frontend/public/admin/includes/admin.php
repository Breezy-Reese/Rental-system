<?php

require_once __DIR__ . "/auth.php";

function require_admin(): void
{
    require_login();

    if (current_role() !== 'Administrator') {
        header("Location: ../customer/dashboard.php");
        exit;
    }
}
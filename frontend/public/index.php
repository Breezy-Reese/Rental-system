<?php

require_once "../includes/auth.php";

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

header("Location: login.php");
exit;
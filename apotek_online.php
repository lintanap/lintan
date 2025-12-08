<?php
// Redirect to new modular structure
// This file now redirects to the new index.php for better organization

// Check if this is a direct access to the old file
if (basename($_SERVER['PHP_SELF']) === 'apotek_online.php') {
    // Redirect to the new index.php
    header('Location: index.php' . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// If accessed through include, just include the new index.php
include 'index.php';
?>
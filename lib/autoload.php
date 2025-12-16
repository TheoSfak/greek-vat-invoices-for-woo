<?php
/**
 * Simple autoloader for mPDF
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register(function ($class) {
    // Only handle Mpdf classes
    if (strpos($class, 'Mpdf\\') !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $file = __DIR__ . '/mpdf-8.2.4/src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

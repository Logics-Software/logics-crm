<?php
/**
 * Layout Helper Functions
 * Helper functions untuk menggunakan layout system baru
 */

/**
 * Start layout dengan header dan sidebar
 * @param string $page_title - Judul halaman
 * @param string $content - Konten halaman (akan di-output di main content)
 */
function startLayout($page_title = 'Logics Software', $content = '') {
    global $db, $message, $message_type;
    
    // Set page title and content
    $GLOBALS['page_title'] = $page_title;
    $GLOBALS['content'] = $content;
    
    // Pass database connection to layout
    $GLOBALS['db'] = $db;
    
    // Include layout
    include 'includes/layout.php';
    exit; // Stop execution after layout is rendered
}

/**
 * Start layout dengan buffer untuk konten
 * @param string $page_title - Judul halaman
 */
function startLayoutBuffer($page_title = 'Logics Software') {
    global $db, $message, $message_type;
    
    // Set page title
    $GLOBALS['page_title'] = $page_title;
    
    // Pass database connection to layout
    $GLOBALS['db'] = $db;
    
    // Start output buffering
    ob_start();
}

/**
 * End layout dengan buffer
 */
function endLayout() {
    global $db;
    
    // Get buffered content
    $content = ob_get_clean();
    
    // Set content
    $GLOBALS['content'] = $content;
    
    // Pass database connection to layout
    $GLOBALS['db'] = $db;
    
    // Include layout
    include 'includes/layout.php';
    exit; // Stop execution after layout is rendered
}

/**
 * Render layout dengan konten yang sudah ada
 * @param string $page_title - Judul halaman
 * @param string $content - Konten halaman
 */
function renderLayout($page_title = 'Logics Software', $content = '') {
    startLayout($page_title, $content);
}
?>

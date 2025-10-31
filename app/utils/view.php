<?php

/**
 * Renders a view file with the provided data
 * 
 * This function eliminates the need for $GLOBALS by using proper variable extraction
 * and output buffering to render views cleanly.
 * 
 * @param string $viewPath The absolute path to the view file
 * @param array $data An associative array of data to pass to the view
 * @return string The rendered view content
 * @throws Exception If the view file does not exist
 */
function renderView(string $viewPath, array $data = []): string
{
    // Check if the view file exists
    if (!file_exists($viewPath)) {
        throw new Exception("View file not found: {$viewPath}");
    }
    
    // Start output buffering
    ob_start();
    
    try {
        // Extract data array into variables
        // EXTR_SKIP prevents overwriting existing variables for security
        extract($data, EXTR_SKIP);
        
        // Include the view file
        require $viewPath;
        
        // Get the buffered content
        $content = ob_get_clean();
        
        return $content;
    } catch (Exception $e) {
        // Clean the buffer in case of error
        ob_end_clean();
        throw $e;
    }
}

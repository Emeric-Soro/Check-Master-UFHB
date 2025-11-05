<?php

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * HTMLPurifierService
 * 
 * Service for sanitizing HTML content from rich text editors (CKEditor)
 * Prevents XSS attacks while preserving safe formatting
 */
class HTMLPurifierService
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Get configured HTMLPurifier instance (singleton)
     * 
     * @return HTMLPurifier
     */
    private static function getPurifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            
            // Allow common HTML elements used in document editing
            $config->set('HTML.Allowed', 
                'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],' .
                'p[style],br,strong,em,u,s,strike,sub,sup,' .
                'ul[style],ol[style],li[style],' .
                'table[style,border,cellpadding,cellspacing],thead,tbody,tr[style],th[style],td[style,colspan,rowspan],' .
                'a[href,title,target],' .
                'img[src,alt,width,height,style],' .
                'div[style,id],span[style],' .
                'blockquote[style]'
            );
            
            // Allow safe CSS properties for formatting
            $config->set('CSS.AllowedProperties', 
                'font-size,font-family,font-weight,font-style,' .
                'color,background-color,background,' .
                'text-align,text-decoration,line-height,' .
                'margin,margin-top,margin-bottom,margin-left,margin-right,' .
                'padding,padding-top,padding-bottom,padding-left,padding-right,' .
                'border,border-top,border-bottom,border-left,border-right,' .
                'border-width,border-style,border-color,' .
                'width,height,max-width,max-height,min-width,min-height,' .
                'display,flex,flex-direction,justify-content,align-items,' .
                'position,top,bottom,left,right'
            );
            
            // Enable CSS styling
            $config->set('HTML.AllowedAttributes', 'style,href,src,alt,title,width,height,colspan,rowspan,border,cellpadding,cellspacing,target,id');
            
            // Allow target="_blank" for links
            $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
            
            // Cache directory for better performance
            $cacheDir = sys_get_temp_dir() . '/htmlpurifier';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cacheDir);
            
            // UTF-8 encoding
            $config->set('Core.Encoding', 'UTF-8');
            
            // Disable cache in development for easier testing
            if (getenv('APP_ENV') !== 'production') {
                $config->set('Cache.DefinitionImpl', null);
            }
            
            self::$purifier = new HTMLPurifier($config);
        }
        
        return self::$purifier;
    }

    /**
     * Purify HTML content from CKEditor or similar rich text editors
     * 
     * @param string $html Raw HTML content
     * @return string Sanitized HTML content safe for storage and display
     */
    public static function purifyHTML(string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        $purifier = self::getPurifier();
        return $purifier->purify($html);
    }

    /**
     * Purify HTML content and log the operation
     * 
     * @param string $html Raw HTML content
     * @param string $context Context for logging (e.g., 'rapport', 'compte_rendu')
     * @return string Sanitized HTML content
     */
    public static function purifyWithLogging(string $html, string $context = 'unknown'): string
    {
        $originalLength = strlen($html);
        $purified = self::purifyHTML($html);
        $purifiedLength = strlen($purified);
        
        // Log if content was significantly modified (potential XSS attempt)
        if ($originalLength > 0 && $purifiedLength < $originalLength * 0.8) {
            error_log(sprintf(
                "HTMLPurifier removed significant content in context '%s': %d bytes -> %d bytes (%.1f%% reduction)",
                $context,
                $originalLength,
                $purifiedLength,
                (($originalLength - $purifiedLength) / $originalLength) * 100
            ));
        }
        
        return $purified;
    }

    /**
     * Validate if HTML content is safe without modification
     * 
     * @param string $html HTML content to validate
     * @return bool True if HTML is already safe, false otherwise
     */
    public static function isCleanHTML(string $html): bool
    {
        if (empty($html)) {
            return true;
        }
        
        $purified = self::purifyHTML($html);
        return $html === $purified;
    }
}

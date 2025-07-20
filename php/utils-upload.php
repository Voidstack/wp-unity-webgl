<?php

class UploadUtils {
    /** 
     * Initializes the WordPress filesystem.
     * @return bool True on success, false on failure.
     */
    public static function unity_webgl_init_filesystem(): bool {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        if (!function_exists('WP_Filesystem')) return false;
        return WP_Filesystem();
    }
}
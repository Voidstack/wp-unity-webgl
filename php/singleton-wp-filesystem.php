<?php

class WPFilesystemSingleton {
    private static $instance = null;
    private $wpFilesystem;

    private function __construct() {
        if ( ! function_exists('WP_Filesystem') ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;
        $this->wpFilesystem = $wp_filesystem;
    }

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __call($method, $args) {
        return call_user_func_array([$this->wpFilesystem, $method], $args);
    }

    public function getWpFilesystem() {
        return $this->wpFilesystem;
    }
}

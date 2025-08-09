<?php
require_once __DIR__ . '/singleton-wp-filesystem.php';

class Utils {
    public static function generateUuid(): string {
        return uniqid('unity_', true);
    }
    
    // Méthode qui permet la suppression d'un dossier Wordpress friendly.
    public static function deleteFolder($folder) {
        if (is_dir($folder)) {
            WPFilesystemSingleton::getInstance()->rmdir($folder, true);
            echo "<div style='color:green;'>✅ Succès : build '" . esc_html( $folder ) . "' supprimé avec succès.</div>";
        }else{
            echo "<div style='color:green;'>Error : build '" . esc_html( $folder ) . " not a folder.</div>";
        }
    }
    
    // Supprime un dossier
    public static function deleteFolder2(string $dir): void {
        if (!file_exists($dir)) {
            return;
        }
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                WPFilesystemSingleton::getInstance()->rmdir($file->getRealPath());
            } else {
                wp_delete_file($file->getRealPath());
            }
        }
        Utils::deleteFolder($dir);
    }
    
    public static function array_to_string(array $arr): string {
        return implode(', ', $arr);
    }
    
    
    // Retourne la liste des sous-dossiers présents dans $builds_dir.
    public static function list_builds($builds_dir) {
        $builds = [];
        foreach (scandir($builds_dir) as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir($builds_dir . '/' . $entry)) {
                $builds[] = $entry;
            }
        }
        return $builds;
    }    
    
    /**
    * Calcule la taille en octets d'un fichier ou d'un dossier (récursivement).
    *
    * @param string $path Chemin vers le fichier ou dossier.
    * @return int|float Taille en octets, 0 si le chemin n'existe pas.
    */
    public static function getSize(string $path): int|float {
        if (is_file($path)) {
            return filesize($path);
        }
        if (!is_dir($path)) {
            return 0;
        }
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
    
    // Détecte le serveur web utilisé (Apache ou Nginx).
    public static function detectServer(): string { $serverSoftware = isset($_SERVER['SERVER_SOFTWARE'])? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'])): '';
        
        $serverSoftware = sanitize_text_field($serverSoftware); // sécurise la chaîne
        
        if (stripos($serverSoftware, 'apache') !== false) {
            return 'apache';
        }
        if (stripos($serverSoftware, 'nginx') !== false) {
            return 'nginx';
        }
        return $serverSoftware ?: 'unknown';
    }
    
    
    /**
    * Vérifie si le type MIME pour les fichiers .wasm est configuré dans le .htaccess.
    * @return bool True si la directive est présente, false sinon.
    */
    public static function isWasmMimeConfigured(): bool {
        $server = self::detectServer();
        if ($server === 'apache') {
            $htaccessPath = isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) . '/.htaccess': '';
            $directive = 'AddType application/wasm .wasm';
            return file_exists($htaccessPath) && strpos(file_get_contents($htaccessPath), $directive) !== false;
        }
        return false;
    }
    
    /**
    * Configure le type MIME pour les fichiers .wasm dans le .htaccess ou le fichier de configuration Nginx.
    * @return bool True si la configuration a réussi, false sinon.
    */
    public static function setupWasmMime(): bool {
        $server = self::detectServer();
        
        if ($server === 'apache') {
            $htaccessPath = '';
            if ( isset($_SERVER['DOCUMENT_ROOT']) ) {
                $htaccessPath = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) . '/.htaccess';
            }
            $directive = "AddType application/wasm .wasm\n";
            
            if (!file_exists($htaccessPath)) {
                return file_put_contents($htaccessPath, $directive) !== false;
            }
            
            $content = file_get_contents($htaccessPath);
            if (strpos($content, trim($directive)) !== false) return true;
            
            $content .= $directive;
            return file_put_contents($htaccessPath, $content) !== false;
        }
        return false;
    }
    
    /**
    * Supprime la directive MIME pour les fichiers .wasm du .htaccess ou du fichier de configuration Nginx.
    * @return bool True si la suppression a réussi, false sinon.
    */
    public static function removeWasmMimeSetup(): bool {
        $server = self::detectServer();
        
        if ($server === 'apache') {
            $htaccessPath = '';
            if ( isset($_SERVER['DOCUMENT_ROOT']) ) {
                $htaccessPath = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) . '/.htaccess';
            }            $directive = "AddType application/wasm .wasm";
            
            if (!file_exists($htaccessPath)){
                return true;
            }
            
            $content = file_get_contents($htaccessPath);
            $newContent = str_replace($directive . "\n", '', $content);
            $newContent = str_replace($directive, '', $newContent);
            
            return file_put_contents($htaccessPath, $newContent) !== false;
            
        }
        return false;
    }
    
    /**
    * Renomme tous les fichiers et dossiers extraits en minuscules, récursivement.
    */
    public static function lowercaseRecursive(string $dir): void {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $oldPath = $dir . DIRECTORY_SEPARATOR . $item;
            $newName = mb_strtolower($item);
            $newPath = $dir . DIRECTORY_SEPARATOR . $newName;
            
            // Si le nom change, renommer
            if ($newPath !== $oldPath) {
                $fs = WPFilesystemSingleton::getInstance();
                $fs->move( $oldPath, $tmpPath, true );
                $fs->move( $tmpPath, $newPath, true );
            }
            
            if (is_dir($newPath)) {
                self::lowercase_recursive($newPath);
            }
        }
    }
}

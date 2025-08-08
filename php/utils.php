<?php

class Utils {
    public static function generate_uuid(): string {
        return $uuid = uniqid('unity_', true);
    }
    
    // Méthode qui permet la suppression d'un dossier Wordpress friendly.
    public static function delete_folder($folder) {
        if (is_dir($folder)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
            global $wp_filesystem;
            $wp_filesystem->delete($folder, true);
            echo "<div style='color:green;'>✅ Succès : build '{$folder}' supprimé avec succès.</div>";
        }
    }

    // Supprime un dossier
    public static function delete_folder2(string $dir): void {
        if (!file_exists($dir)) {
            return;
        }
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
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
    public static function detectServer(): string {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
        if (stripos($serverSoftware, 'apache') !== false) return 'apache';
        if (stripos($serverSoftware, 'nginx') !== false) return 'nginx';
        return $serverSoftware || 'unknown';
    }
    
    /**
    * Vérifie si le type MIME pour les fichiers .wasm est configuré dans le .htaccess.
    * @return bool True si la directive est présente, false sinon.
    */
    public static function isWasmMimeConfigured(): bool {
        $server = self::detectServer();
        if ($server === 'apache') {
            $htaccessPath = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
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
            $htaccessPath = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
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
            $htaccessPath = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
            $directive = "AddType application/wasm .wasm";
            
            if (!file_exists($htaccessPath)) return true;
            
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
    public static function lowercase_recursive(string $dir): void {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $oldPath = $dir . DIRECTORY_SEPARATOR . $item;
            $newName = mb_strtolower($item);
            $newPath = $dir . DIRECTORY_SEPARATOR . $newName;
            
            // Si le nom change, renommer
            if ($newPath !== $oldPath) {
                rename($oldPath, $newPath);
            }
            
            if (is_dir($newPath)) {
                self::lowercase_recursive($newPath);
            }
        }
    }   
}

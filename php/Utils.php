<?php

class Utils {
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
}


<?php
require_once __DIR__ . '/utils.php';

class BuildExtractor {
    // Chemin vers l'archive ZIP
    private string $zipPath;
    // Dossier cible de destination du build
    private string $targetDir;
    // Liste des fichiers attendus dans le build
    private array $expectedFiles;
    // Dossier temporaire utilisé pour l'extraction
    private string $tmpDir;
    
    public function __construct(string $zipPath, string $targetDir, array $expectedFiles) {
        $this->zipPath = $zipPath;
        $this->targetDir = $targetDir;
        $this->expectedFiles = array_map('strtolower', $expectedFiles);
        $this->tmpDir = $targetDir . '_tmp_' . wp_generate_password(8, false);
    }
    
    public function extract(): bool {
        $this->info("Début de l'extraction");

        // Création du dossier temporaire
        if (!wp_mkdir_p($this->tmpDir)) {
            $this->error(__('Unable to create temporary extraction folder.', 'wpunity'));
            return false;
        }
        
        $zip = new ZipArchive;
        // Ouverture du fichier ZIP
        if ($zip->open($this->zipPath) !== TRUE) {
            $this->error(sprintf(__('Unable to open the .zip file (%s)', 'wpunity'), $this->zipPath));
            return false;
        }
        
        // Extraction dans le dossier temporaire
        if (!$zip->extractTo($this->tmpDir)) {
            $zip->close();
            Utils::delete_folder2($this->tmpDir);
            $this->error(__('Extraction failed to temporary folder.', 'wpunity'));
            return false;
        }
        $zip->close();
        
        // On renomme tous les fichiers et dossiers en minuscules
        $this->lowercaseAllFilenames($this->tmpDir);
        
        // Vérifie que les fichiers attendus sont bien présents
        if (!$this->verifyExtractedFiles($this->tmpDir)) {
            Utils::delete_folder2($this->tmpDir);
            $this->error("Missing expected build files. " . 
            Utils::array_to_string($this->expectedFiles) . "</br>" . "le fichier .zip doit OBLIGATOIREMENT avoir le même nom que les fichiers qu'il contient");
            return false;
        }
        
        // Suppression de l'ancien dossier cible
        if (file_exists($this->targetDir)) {
            Utils::delete_folder2($this->targetDir);
        }
        
        // Déplacement du dossier temporaire vers la cible
        if (!rename($this->tmpDir, $this->targetDir)) {
            Utils::delete_folder2($this->tmpDir);
            $this->error("Failed to move build to target directory.");
            return false;
        }
        
        return true;
    }
    
    // Vérifie que tous les fichiers attendus sont bien dans l'archive extraite
    private function verifyExtractedFiles(): bool {
        $foundFiles = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tmpDir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                // On ajoute le nom du fichier en minuscules pour comparaison
                $foundFiles[] = strtolower($file->getFilename());
            }
        }
        // Affiche les fichiers trouvés pour debug
        $this->info('Fichier trouvés ' . Utils::array_to_string($foundFiles));
        foreach ($this->expectedFiles as $expected) {
            if (!in_array($expected, $foundFiles)) {
                return false;
            }
        }
        return true;
    }
    
    // Renomme tous les fichiers et dossiers en minuscules de manière récursive
    private function lowercaseAllFilenames(string $dir): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            $oldPath = $item->getPathname();
            $dirPath = $item->getPath();
            $lowerName = strtolower($item->getFilename());
            $newPath = $dirPath . DIRECTORY_SEPARATOR . $lowerName;
            
              // Si le nom change uniquement par la casse, certains systèmes (ex. Windows) ne le prennent pas en compte => contournement : on renomme vers un nom temporaire, puis vers le nom final
            if ($oldPath !== $newPath) {
                // Pour contourner les problèmes de casse uniquement
                $tmpPath = $dirPath . DIRECTORY_SEPARATOR . uniqid('tmp_', true);
                rename($oldPath, $tmpPath);
                rename($tmpPath, $newPath);
            }
        }
    }
    
    
    // Affiche un message d'erreur dans l'interface admin
    private function error(string $message): void {
        echo "<p style='color:red;'>" . __('❌ Erreur extraction : ', 'wpunity') . wp_kses_post($message) . "</p>";
    }
    
    // Affiche un message d'information dans l'interface admin WordPress
    private function info(string $message):void {
        echo "<p style='color:black;'>" . __('ℹ️ Info extraction : ', 'wpunity') . wp_kses_post($message) . "</p>";
    }
}


<?php
require_once __DIR__ . '/utils.php';

class BuildExtractor {
    private string $zipPath;
    private string $targetDir;
    private array $expectedFiles;
    private string $tmpDir;
    
    public function __construct(string $zipPath, string $targetDir, array $expectedFiles) {
        $this->zipPath = $zipPath;
        $this->targetDir = $targetDir;
        $this->expectedFiles = array_map('strtolower', $expectedFiles);
        $this->tmpDir = $targetDir . '_tmp_' . wp_generate_password(8, false);
    }
    
    public function extract(): bool {
        if (!wp_mkdir_p($this->tmpDir)) {
            $this->error("Unable to create temporary extraction folder.");
            return false;
        }
        
        $zip = new ZipArchive;
        if ($zip->open($this->zipPath) !== TRUE) {
            $this->error("Unable to open the .zip file ({$this->zipPath})");
            return false;
        }
        
        if (!$zip->extractTo($this->tmpDir)) {
            $zip->close();
            Utils::delete_folder2($this->tmpDir);
            $this->error("Extraction failed to temporary folder.");
            return false;
        }
        $zip->close();
        
        $this->lowercaseAllFilenames($this->tmpDir);
        
        if (!$this->verifyExtractedFiles($this->tmpDir)) {
            Utils::delete_folder2($this->tmpDir);
            $this->error("Missing expected build files. " . 
            Utils::array_to_string($this->expectedFiles));
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
    
    private function verifyExtractedFiles(): bool {
        $foundFiles = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tmpDir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $foundFiles[] = strtolower($file->getFilename());
            }
        }
        $this->info('Fichier trouvés ' . Utils::array_to_string($foundFiles));
        foreach ($this->expectedFiles as $expected) {
            if (!in_array($expected, $foundFiles)) {
                return false;
            }
        }
        return true;
    }
    
    private function lowercaseAllFilenames(): void {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->targetDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $oldPath = $item->getPathname();
            $newPath = $item->getPath() . DIRECTORY_SEPARATOR . strtolower($item->getFilename());
            if ($oldPath !== $newPath && !file_exists($newPath)) {
                rename($oldPath, $newPath);
            }
        }
    }
    
    // Affiche un message d'erreur dans l'interface admin
    private function error(string $message): void {
        echo "<p style='color:red;'>" . __('❌ Erreur extraction : ', 'wpunity') . wp_kses_post($message) . "</p>";
    }

    private function info(string $message):void {
        echo "<p style='color:black;'>" . __('ℹ️ Info extraction : ', 'wpunity') . wp_kses_post($message) . "</p>";
    }
}

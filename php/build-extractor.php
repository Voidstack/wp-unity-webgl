<?php
require_once __DIR__ . '/utils.php';

class BuildExtractor {
    // Path to the ZIP archive
    private string $zipPath;
    // Target directory for the extracted build
    private string $targetDir;
    // List of expected files in the build
    private array $expectedFiles;
    // Temporary directory used for extraction
    private string $tmpDir;
    
    public function __construct(string $zipPath, string $targetDir, array $expectedFiles) {
        $this->zipPath = $zipPath;
        $this->targetDir = $targetDir;
        $this->expectedFiles = array_map('strtolower', $expectedFiles);
        $this->tmpDir = $targetDir . '_tmp_' . wp_generate_password(8, false);
    }
    
    public function extract(): bool {
        $this->info(__('Starting extraction', 'wp-unity-webgl'));
        
        // Create the temporary directory
        if (!wp_mkdir_p($this->tmpDir)) {
            $this->error(__('Unable to create temporary extraction folder.', 'wp-unity-webgl'));
            return false;
        }
        
        $zip = new ZipArchive;
        // Open the ZIP file
        if ($zip->open($this->zipPath) !== true) {
            // translators: %s is the path to the .zip file that could not be opened.
            $this->error(sprintf(__('Unable to open the .zip file (%s)', 'wp-unity-webgl'), $this->zipPath));
            return false;
        }
        
        // Extract to the temporary directory
        if (!$zip->extractTo($this->tmpDir)) {
            $zip->close();
            Utils::delete_folder2($this->tmpDir);
            $this->error(__('Extraction failed to temporary folder.', 'wp-unity-webgl'));
            return false;
        }
        $zip->close();
        
        // Rename all files and folders to lowercase
        $this->lowercaseAllFilenames($this->tmpDir);
        
        // Verify that all expected files are present
        if (!$this->verifyExtractedFiles($this->tmpDir)) {
            Utils::delete_folder2($this->tmpDir);
            $this->error(
                __('Missing expected build files. ', 'wp-unity-webgl') .
                Utils::array_to_string($this->expectedFiles) . '</br>' .
                __('The .zip file MUST have the same name as the files it contains.', 'wp-unity-webgl')
            );
            return false;
        }
        
        // Delete the existing target directory if it exists
        if (file_exists($this->targetDir)) {
            Utils::delete_folder2($this->targetDir);
        }
        
        // Move the temporary directory to the target location
        if (!rename($this->tmpDir, $this->targetDir)) {
            Utils::delete_folder2($this->tmpDir);
            $this->error(__('Failed to move build to target directory.', 'wp-unity-webgl'));
            return false;
        }
        
        return true;
    }
    
    // Verify that all expected files are present in the extracted archive
    private function verifyExtractedFiles(): bool {
        $foundFiles = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tmpDir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                // Add the file name in lowercase for comparison
                $foundFiles[] = strtolower($file->getFilename());
            }
        }
        // Display found files for debugging
        $this->info(__('Files found: ', 'wp-unity-webgl') . Utils::array_to_string($foundFiles));
        foreach ($this->expectedFiles as $expected) {
            if (!in_array($expected, $foundFiles)) {
                return false;
            }
        }
        return true;
    }
    
    // Recursively rename all files and folders to lowercase
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
            
            // If the name changes only in casing, some systems (e.g. Windows) may not recognize the change
            // Workaround: rename to a temporary name, then to the final lowercase name
            if ($oldPath !== $newPath) {
                $tmpPath = $dirPath . DIRECTORY_SEPARATOR . uniqid('tmp_', true);
                rename($oldPath, $tmpPath);
                rename($tmpPath, $newPath);
            }
        }
    }
    
    // Display an error message in the WordPress admin interface
    private function error(string $message): void {
        echo "<p style='color:red;'>" . __('❌ Extraction error: ', 'wp-unity-webgl') . wp_kses_post($message) . "</p>";
    }
    
    // Display an informational message in the WordPress admin interface
    private function info(string $message): void {
        echo "<p style='color:black;'>" . __('ℹ️ Extraction info: ', 'wp-unity-webgl') . wp_kses_post($message) . "</p>";
    }
}

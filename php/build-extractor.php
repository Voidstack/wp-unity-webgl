<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/singleton-wp-filesystem.php';

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
        Utils::info(__('Starting extraction', 'webgl-embedder-for-unity'));
        $result = true;
        
        if (!$this->createTempDirectory() || !$this->extractZipToTemp() || !$this->processExtractedFiles() || !$this->moveToTargetDirectory())
            {
            $result = false;
        }
        
        return $result;
    }
    
    // Create the temporary directory
    private function createTempDirectory(): bool {
        if (!wp_mkdir_p($this->tmpDir)) {
            Utils::error(__('Unable to create temporary extraction folder.', 'webgl-embedder-for-unity'));
            return false;
        }
        return true;
    }
    
    // Extract the ZIP file to the temporary directory
    private function extractZipToTemp(): bool {
        $zip = new ZipArchive;
        if ($zip->open($this->zipPath) !== true) {
            // translators: %s is the path to the zip file that couldn't be opened.
            Utils::error(sprintf(__('Unable to open the .zip file (%s)', 'webgl-embedder-for-unity'), $this->zipPath));
            return false;
        }
        if (!$zip->extractTo($this->tmpDir)) {
            $zip->close();
            Utils::deleteFolder($this->tmpDir);
            Utils::error(__('Extraction failed to temporary folder.', 'webgl-embedder-for-unity'));
            return false;
        }
        $zip->close();
        return true;
    }
    
    // Rename files/folders to lowercase and verify expected files
    private function processExtractedFiles(): bool {
        $this->lowercaseAllFilenames($this->tmpDir);
        
        if (!$this->verifyExtractedFiles($this->tmpDir)) {
            Utils::deleteFolder($this->tmpDir);
            Utils::error(
                __('Missing expected build files. ', 'webgl-embedder-for-unity') .
                Utils::arrayToString($this->expectedFiles) . '</br>' .
                __('The .zip file MUST have the same name as the files it contains.', 'webgl-embedder-for-unity')
            );
            return false;
        }
        return true;
    }
    
    // Move the temporary directory to the target location
    private function moveToTargetDirectory(): bool {
        if (file_exists($this->targetDir)) {
            Utils::deleteFolder($this->targetDir);
        }
        $fsSingleton = WPFilesystemSingleton::getInstance();
        if (!$fsSingleton->move($this->tmpDir, $this->targetDir, true)) {
            Utils::deleteFolder($this->tmpDir);
            Utils::error(__('Failed to move build to target directory.', 'webgl-embedder-for-unity'));
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
        Utils::info(__('Files found: ', 'webgl-embedder-for-unity') . Utils::arrayToString($foundFiles));
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
                $fs = WPFilesystemSingleton::getInstance();
                $fs->rename($oldPath, $newPath, true);
            }
        }
    }
}

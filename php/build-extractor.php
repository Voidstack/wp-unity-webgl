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
        $this->info(__('Starting extraction', 'wp-unity-webgl'));
        $result = true;
        
        if (
            !$this->createTempDirectory() ||
            !$this->extractZipToTemp() ||
            !$this->processExtractedFiles() ||
            !$this->moveToTargetDirectory()
            ) {
                $result = false;
            }
            
            return $result;
        }
        
        // Create the temporary directory
        private function createTempDirectory(): bool {
            if (!wp_mkdir_p($this->tmpDir)) {
                $this->error(__('Unable to create temporary extraction folder.', 'wp-unity-webgl'));
                return false;
            }
            return true;
        }
        
        // Extract the ZIP file to the temporary directory
        private function extractZipToTemp(): bool {
            $zip = new ZipArchive;
            if ($zip->open($this->zipPath) !== true) {
                // translators: %s is the path to the zip file that couldn't be opened.
                $this->error(sprintf(__('Unable to open the .zip file (%s)', 'wp-unity-webgl'), $this->zipPath));
                return false;
            }
            if (!$zip->extractTo($this->tmpDir)) {
                $zip->close();
                Utils::deleteFolder2($this->tmpDir);
                $this->error(__('Extraction failed to temporary folder.', 'wp-unity-webgl'));
                return false;
            }
            $zip->close();
            return true;
        }
        
        // Rename files/folders to lowercase and verify expected files
        private function processExtractedFiles(): bool {
            $this->lowercaseAllFilenames($this->tmpDir);
            
            if (!$this->verifyExtractedFiles($this->tmpDir)) {
                Utils::deleteFolder2($this->tmpDir);
                $this->error(
                    __('Missing expected build files. ', 'wp-unity-webgl') .
                    Utils::arrayToString($this->expectedFiles) . '</br>' .
                    __('The .zip file MUST have the same name as the files it contains.', 'wp-unity-webgl')
                );
                return false;
            }
            return true;
        }
        
        // Move the temporary directory to the target location
        private function moveToTargetDirectory(): bool {
            if (file_exists($this->targetDir)) {
                Utils::deleteFolder2($this->targetDir);
            }
            $fsSingleton = WPFilesystemSingleton::getInstance();
            if (!$fsSingleton->move($this->tmpDir, $this->targetDir, true)) {
                Utils::deleteFolder2($this->tmpDir);
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
            $this->info(__('Files found: ', 'wp-unity-webgl') . Utils::arrayToString($foundFiles));
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
                    $fs = WPFilesystemSingleton::getInstance();
                    
                    $fs->move( $oldPath, $tmpPath, true ); // Étape 1 : renommage temporaire
                    $fs->move( $tmpPath, $newPath, true ); // Étape 2 : renommage vers la bonne casse
                }
            }
        }
        
        // Display an error message in the WordPress admin interface
        private function error(string $message): void {
            echo "<p style='color:red;'>" . esc_html__( '❌ Extraction error: ', 'wp-unity-webgl' ) . wp_kses_post( $message ) . "</p>";
        }
        
        // Display an informational message in the WordPress admin interface
        private function info(string $message): void {
            echo "<p style='color:black;'>" . esc_html__( 'ℹ️ Extraction info: ', 'wp-unity-webgl' ) . wp_kses_post( $message ) . "</p>";
        }
    }
    
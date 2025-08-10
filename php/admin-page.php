<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/singleton-wp-filesystem.php';
require_once __DIR__ . '/build-extractor.php';

/**
* Ce fichier contient toutes les méthodes nécessaires à la gestion
* de la page d'administration de l'extension WordPress.
*
* Cette séparation facilite la maintenance en isolant la logique
* liée uniquement à l'interface admin du reste du plugin.
*/

const STR_TITLE = "Unity WebGL";

// Fonction WORDPRESS pour l'ajout de l'extention
add_action('admin_menu', function (): void {
    add_menu_page(STR_TITLE, STR_TITLE, 'manage_options', 'unity_webgl_admin', 'unityWebglAdminPage', '', 6);
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('mon-style-admin', plugin_dir_url(__FILE__) . '../css/admin-page.css', [], '1.0.0');
});

// Fonction WORDPRESS pour l'ajout d'un boutton pour la fenetre d'admin Unity GL
add_action('admin_head', function () {
    ?>
    <style>
    #toplevel_page_unity_webgl_admin .wp-menu-image {
        background: none !important;
    }
    
    #toplevel_page_unity_webgl_admin .wp-menu-image:before {
        content: "";
        display: inline-block;
        width: 20px;
        height: 20px;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="%23a7aaad" viewBox="0 0 64 64"><path d="M63.22 25.42L56.387 0 30.87 6.814l-3.775 6.637-7.647-.055L.78 32.005l18.67 18.604 7.658-.057 3.78 6.637 25.5 6.81 6.832-25.418L59.34 32zm-16-15.9L36.036 28.86H13.644l14.094-14.34zM36.036 35.145l11.196 19.338-19.507-5.012L13.63 35.15h22.392zm5.468-3.14L52.7 12.665l5.413 19.34L52.7 51.34z"/></svg>') no-repeat center center;
        background-size: contain;
        color: inherit;
    }
    </style>
    <?php
});

// Défini la page d'administration des jeux téléversé sur wordpress.
function unityWebglAdminPage(): void
{
    ?>
    <div class="wrap">
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px;">
    <div style="display: flex; align-items: center;">
    <img style="width: 32px; height: 32px; margin-right: 10px;" src="<?php echo esc_url( plugins_url('../res/unity_icon.svg', __FILE__) ); ?>" alt="Logo" class="logo" />
    <span style="font-size: 18px; color: #333; margin-right: 20px;">Unity WebGL</span>
    </div>
    <a href="https://coff.ee/EnosiStudio" target="_blank" style="text-decoration: none; font-size: 16px; color: #0073aa; white-space: nowrap;">
    ☕ Support me
    </a>
    </div>
    
    <?php
    
    // @keep _e('Current language', 'unity-webgl-integrator');
    
    unityWebglAdminServerConfig();
    
    echo "<div class='simpleblock'>";
    echo '<h2>' . esc_html__('Build Manager', 'unity-webgl-integrator') . '</h2>';
    echo '<p>' . esc_html__('Use this page to add your Unity project by uploading the', 'unity-webgl-integrator') . ' <strong>.zip</strong> ' . esc_html__('folder of your project and manage it easily within the admin dashboard.', 'unity-webgl-integrator') . '</p>';
    ?>
    
    <form method="post" enctype="multipart/form-data">
    <input type="file" name="unity_zip" accept=".zip" required>
    <?php wp_nonce_field('upload_unity_zip_action', 'upload_unity_zip_nonce'); ?>
    <?php submit_button(__('Upload and Extract', 'unity-webgl-integrator')); ?>
    </form>
    <?php
    
    unityWebglHandleUpload();
    
    $upload_dir = wp_upload_dir();
    $builds_dir = $upload_dir['basedir'] . '/unity_webgl';
    
    // Creation of the folder
    $wpFS = WPFilesystemSingleton::getInstance()->getWpFilesystem();
    if ( ! $wpFS->is_dir( $builds_dir ) ) {
        $wpFS->mkdir( $builds_dir, 0755 );
    }
    
    // Supprimer un build si demandé
    if ( ! empty($_POST['delete_build']) && ! empty($_POST['build_name']) && ! empty($_POST['delete_build_nonce']) ) {
        $nonce = sanitize_text_field( wp_unslash( $_POST['delete_build_nonce'] ) );
        if ( wp_verify_nonce( $nonce, 'delete_build_action' ) ) {
            $build_to_delete = basename( sanitize_text_field( wp_unslash( $_POST['build_name'] ) ) );
            $full_path = $builds_dir . '/' . $build_to_delete;
            Utils::deleteFolder( $full_path );
        }
    }
    
    $builds = Utils::listBuilds($builds_dir);
    
    // Delete all builds if requested.
    if (isset($_POST['delete_all_builds'])) {
        foreach ($builds as $build) {
            $path = $builds_dir . '/' . $build;
            Utils::deleteFolder($path);
        }
        $builds = Utils::listBuilds($builds_dir);
        echo '<div class="notice notice-success"><p>' . esc_html__('All builds have been deleted.', 'unity-webgl-integrator') . '</p></div>';
    }
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<tr>
    <th style="text-align:left; border-bottom: 1px solid #ccc;">' . esc_html__('Name', 'unity-webgl-integrator') . '</th>
    <th style="text-align:left; border-bottom: 1px solid #ccc;">' . esc_html__('Path', 'unity-webgl-integrator') . '</th>
    <th style="text-align:center; border-bottom: 1px solid #ccc;">' . esc_html__('Size (MB)', 'unity-webgl-integrator') . '</th>
    <th style="text-align:right; border-bottom: 1px solid #ccc;"></th>
</tr>';
    
    foreach ($builds as $build) {
        $build_path = $builds_dir . '/' . $build;
        $size_bytes = Utils::getSize($build_path);
        $size_mb = round($size_bytes / 1048576, 2);
        
        echo '<tr>';
        echo '<td style="padding: 8px 0;">' . esc_html($build) . '</td>';
        echo '<td style="padding: 8px 0;">' . esc_html($build_path) . '</td>';
        echo '<td style="padding: 8px 8px; text-align:right;">' . esc_html( $size_mb) . '</td>';
        echo '<td style="padding: 8px 0; text-align:right;">';
        echo '<form method="post" onsubmit="return confirm(\'❌ ' . esc_html__('Permanently delete build:', 'unity-webgl-integrator') . ' ' . esc_js($build) . ' ?\');" style="margin:0;">';
        echo '<input type="hidden" name="build_name" value="' . esc_attr($build) . '">';
        wp_nonce_field('delete_build_action', 'delete_build_nonce');
        submit_button('Delete', 'delete', 'delete_build', false);
        echo '</form></td></tr>';
    }
    echo '</table>';
    
    // No build or create a delete all builds btn.
    if (empty($builds)) {
        echo '<p>' . esc_html__('No build found.', 'unity-webgl-integrator') . '</p>';
    }else {
        echo '<form method="post" onsubmit="return confirm(\'❌ ' . esc_js(__('Delete ALL builds?', 'unity-webgl-integrator')) . '\');" style="margin-bottom: 16px;">';
        echo '<input type="hidden" name="delete_all_builds" value="1">';
        submit_button('🧨 ' . __('Delete all builds', 'unity-webgl-integrator'), 'delete');
        echo '</form>';
    }
    
    echo '</div></div>';
    echo '<div class="footer">';
    /* translators: %s is the link to Enosi Studio */
    echo '<p>' . sprintf(esc_html__('Plugin developed by %s.', 'unity-webgl-integrator'),
    '<a href="https://enosistudio.com/" target="_blank" rel="noopener noreferrer">Enosi Studio</a>') . '</p>';
    echo '</div>';
}

/**
* Extracted server configuration block to reduce cognitive complexity.
*/
function unityWebglAdminServerConfig(): void
{
    $serverType = Utils::detectServer();
    echo "<div class='simpleblock'>";
    switch($serverType) {
        case 'apache': {
            echo '<h2>' . esc_html__( 'Server configuration: Apache detected.', 'unity-webgl-integrator' ) . '</h2>';
            if ( isset($_POST['add_wasm_mime'], $_POST['add_wasm_mime_nonce']) ) {
                $nonce = sanitize_text_field( wp_unslash( $_POST['add_wasm_mime_nonce'] ) );
                if ( wp_verify_nonce( $nonce, 'add_wasm_mime_action' ) ) {
                    Utils::setupWasmMime();
                }
            }elseif (isset($_POST['del_wasm_mime'])) {
                Utils::removeWasmMimeSetup();
            }
            
            // Check htaccess pour le type MIME
            if(Utils::isWasmMimeConfigured()){
                echo '<form method="post" style="display: flex; align-items: center; gap: 10px;">';
                submit_button(__('Delete the MIME type for .wasm', 'unity-webgl-integrator'), 'primary', 'del_wasm_mime');
                echo '<span style="color:green;">✅ ' . esc_html__('The MIME type for .wasm files is already configured in the .htaccess.', 'unity-webgl-integrator') . '</span>';
                echo '</form>';
            }else{
                echo '<form method="post" style="display: flex; align-items: center; gap: 10px;">';
                wp_nonce_field('add_wasm_mime_action', 'add_wasm_mime_nonce');
                submit_button(
                    esc_html__('Configure the MIME type for .wasm', 'unity-webgl-integrator'),
                    'primary',
                    'add_wasm_mime'
                );
                echo '<span style="color:orange;">⚠️ ' . esc_html__('The MIME type for .wasm files is not configured in the .htaccess. A warning will be shown in the console at each build launch.', 'unity-webgl-integrator') . '</span>';
                echo '</form>';
            }
            echo '<p>' . esc_html__('The attempt to add or remove may fail for security reasons.', 'unity-webgl-integrator') . '<br />' .
            esc_html__('In that case, the configuration must be done manually in the .htaccess file.', 'unity-webgl-integrator') . '<br />' .
            esc_html__('Any server configuration change requires a manual server restart.', 'unity-webgl-integrator') . '</p>';
            break;
        }
        case 'nginx': {
            echo '<h2>' . esc_html__('Server configuration: Nginx detected.', 'unity-webgl-integrator') . '</h2>';
            echo '<p>' . esc_html__('Please configure the MIME type for .wasm files in your Nginx configuration.', 'unity-webgl-integrator') . '<br />' .
            esc_html__('Automatic detection and configuration of the MIME type for .wasm files is only supported on Apache servers.', 'unity-webgl-integrator') . '</p>';
            break;
        }
        default:{
            // translators: %s is the detected server configuration type.
            echo '<h2>' . sprintf(esc_html__('Server configuration: unknown(%s) detected.', 'unity-webgl-integrator'),esc_html($serverType)) . '</h2>';
            echo '<p>' . esc_html__('Automatic detection and configuration of the MIME type for .wasm files is only supported on Apache servers.', 'unity-webgl-integrator') . '</p>';
        }
    }
    echo "</div>";
}

// Méthod de téléversement d'un projet unity .zip
function unityWebglHandleUpload(): void
{
    // Check si le fichier existe
    if ( ! isset($_POST['upload_unity_zip_nonce']) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['upload_unity_zip_nonce'] ) ), 'upload_unity_zip_action' ) ) {
        return; // nonce absent ou invalide, on arrête
    }
    
    if ( ! isset($_FILES['unity_zip']) ) {
        return; // fichier non envoyé, on arrête
    }
    
    // Problème de permission
    if (!current_user_can('manage_options')) {
        Utils::error(__('Insufficient permissions.', 'unity-webgl-integrator'));
        return;
    }
    
    if ( ! isset($_FILES['unity_zip']['tmp_name']) || empty($_FILES['unity_zip']['tmp_name']) ) {
        Utils::error(__('Temporary file missing.', 'unity-webgl-integrator'));
        return;
    }
    
    // Le transfert est vide ou erreur autre
    // Le transfert est vide ou erreur autre
    if ( empty($_FILES['unity_zip']) ) {
        Utils::error(__('No file sent.', 'unity-webgl-integrator'));
        return;
    } elseif ( ! isset($_FILES['unity_zip']['error']) || $_FILES['unity_zip']['error'] !== UPLOAD_ERR_OK ) {
        $error_code = isset($_FILES['unity_zip']['error']) ? intval($_FILES['unity_zip']['error']) : 0;
        Utils::error(
            /* translators: %d is the upload error code */
            sprintf(__('Upload failed, error code: %d', 'unity-webgl-integrator'), $error_code)
        );
        return;
    }
    
    // Vérification du type MIME.
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $mime = finfo_file($finfo, $_FILES['unity_zip']['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/zip') {
        Utils::error("seul le format ZIP est autorisé.");
        return;
    }
    
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $file = $_FILES['unity_zip'];
    
    // Check si l'extension est bien .zip ou .ZIP
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'zip') {
        Utils::error(__('Only ZIP format MIME type is allowed.', 'unity-webgl-integrator'));
        return;
    }
    
    $upload_dir = wp_upload_dir();
    
    // Check si wordpress renvoi bien le upload directory.
    if (!is_array($upload_dir) || empty($upload_dir['basedir'])) {
        Utils::error(__('Unable to retrieve the WordPress upload directory.', 'unity-webgl-integrator'));
        return;
    }
    
    $build_name = pathinfo($file['name'], PATHINFO_FILENAME);
    
    $unityWebFolderName = '/unity_webgl/';
    
    $target_dir = $upload_dir['basedir'] . $unityWebFolderName . sanitize_title($build_name);
    
    // Vérifie si le dossier unity_webgl existe, sinon le crée
    if (!file_exists($upload_dir['basedir'] . $unityWebFolderName) && !wp_mkdir_p($upload_dir['basedir'] . $unityWebFolderName)) {
        Utils::error(__('Unable to create the unity_webgl folder.', 'unity-webgl-integrator'));
        return;
    }
    
    // Initialise le système de fichiers WordPress
    if(!WPFilesystemSingleton::getInstance()->getWpFilesystem()){
        Utils::error(__('Unable to initialize the WordPress filesystem.', 'unity-webgl-integrator'));
        return;
    }
    
    global $wp_filesystem;
    
    // Vérifie si le dossier cible existe déjà et le supprime si nécessaire
    if (file_exists($target_dir) && is_dir($target_dir) && !$wp_filesystem->delete($target_dir, true)) {
        Utils::error(__('Unable to delete the previous build at: ', 'unity-webgl-integrator') . $target_dir);
        return;
    }
    
    // Crée le dossier cible s'il n'existe pas
    if (!$wp_filesystem->is_dir($target_dir) && !wp_mkdir_p($target_dir)) {
        Utils::error(__('Unable to create target directory: ', 'unity-webgl-integrator') . $target_dir);
        return;
    }
    
    $build_name_lower = strtolower($build_name);
    
    $extractor = new BuildExtractor($file['tmp_name'], $target_dir, [
        $build_name_lower.'.data',
        $build_name_lower.'.wasm',
        $build_name_lower.'.framework.js',
        $build_name_lower.'.loader.js'
    ]);
    
    if ($extractor->extract()) {
        echo '<p style="color:green;">✅ Success: Build extracted and validated.</p>';
    }else{
        Utils::deleteFolder($target_dir);
    }
}
?>

<?php
require_once 'php/Utils.php';

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
    add_menu_page(STR_TITLE, STR_TITLE, 'manage_options', 'unity_webgl_admin', 'unity_webgl_admin_page', '', 6);
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('mon-style-admin', plugin_dir_url(__FILE__) . 'css/admin-page.css');
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
function unity_webgl_admin_page(): void
{
    ?>
    
    <div class="wrap">
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px;">
    <div style="display: flex; align-items: center;">
    <img style="width: 32px; height: 32px; margin-right: 10px;"
    src="<?php echo plugins_url('res/unity_icon.svg', __FILE__); ?>" alt="Logo" class="logo" />
    <span style="font-size: 18px; color: #333; margin-right: 20px;">Upload Build Unity WebGL</span>
    </div>
    <a href="https://coff.ee/EnosiStudio" target="_blank" style="text-decoration: none; font-size: 16px; color: #0073aa; white-space: nowrap;">
    ☕ Support me
    </a>
    </div>
    
    
    <?php
    
    echo "<h1>" . Utils::detectServer() . "</h1>";

    if (isset($_POST['add_wasm_mime'])) {
        Utils::setupWasmMime();
    }else if (isset($_POST['del_wasm_mime'])) {
        Utils::removeWasmMimeSetup();
    }
    
    // Check htaccess pour le type MIME
    if(Utils::isWasmMimeConfigured()){
        echo "<div style='color:green;'>✅ Le type MIME pour les fichiers .wasm est déjà configuré dans le .htaccess.</div>";
        echo "<form method='post' style='margin-top: 10px;'>";
        submit_button('Delete le type MIME pour .wasm', 'primary', 'del_wasm_mime');
        echo "</form>";
    }else{
        echo "<div style='color:orange;'>⚠️ Le type MIME pour les fichiers .wasm n'est pas configuré dans le .htaccess.</div>";
        echo "<form method='post' style='margin-top: 10px;'>";
        submit_button('Configurer le type MIME pour .wasm', 'primary', 'add_wasm_mime');
        echo "</form>";
    }
    ?>
    
    <!--    <?php _e('Current language', 'wpunity'); ?> -->
    <p>Use this page to add your Unity project by uploading the <strong>.zip</strong> folder of your project and manage
    it easily within your admin dashboard.</p>
    
    <form method="post" enctype="multipart/form-data">
    <input type="file" name="unity_zip" accept=".zip" required>
    <?php submit_button('Upload and Extract'); ?>
    </form>
    
    <h2>Builds téléversés</h2>
    <ul>
    <?php
    
    unity_webgl_handle_upload();
    
    $upload_dir = wp_upload_dir();
    $builds_dir = $upload_dir['basedir'] . '/unity_webgl';
    
    if (!is_dir($builds_dir)) {
        mkdir($builds_dir, 0755, true);
    }
    
    // Supprimer un build si demandé
    if (isset($_POST['delete_build']) && !empty($_POST['build_name'])) {
        $build_to_delete = basename($_POST['build_name']); // sécurité
        $full_path = $builds_dir . '/' . $build_to_delete;
        
        Utils::delete_folder($full_path);
    }
    
    $builds = Utils::list_builds($builds_dir);
    
    // Supprimer tout les builds si demandé.
    if (isset($_POST['delete_all_builds'])) {
        foreach ($builds as $build) {
            $path = $builds_dir . '/' . $build;
            Utils::delete_folder($path);
        }
        $builds = Utils::list_builds($builds_dir);
        echo '<div class="notice notice-success"><p>Tous les builds ont été supprimés.</p></div>';
    }
    
    // Aucun build ou création d'un btn de suppression de tt les builds.
    if (empty($builds)) {
        echo '<p>Aucun build trouvé.</p>';
        return;
    }else {
        echo '<form method="post" onsubmit="return confirm(\'❌ Supprimer TOUS les builds ?\');" style="margin-bottom: 16px;">';
        echo '<input type="hidden" name="delete_all_builds" value="1">';
        submit_button('🧨 Supprimer tous les builds', 'delete');
        echo '</form>';
    }
    
    
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<tr>
    <th style="text-align:left; border-bottom: 1px solid #ccc;">Name</th>
    <th style="text-align:left; border-bottom: 1px solid #ccc;">Path</th>
    <th style="text-align:middle; border-bottom: 1px solid #ccc;">Size (Mo)</th>
    <th style="text-align:right; border-bottom: 1px solid #ccc;"></th></tr>';
    
    foreach ($builds as $build) {
        $build_path = $builds_dir . '/' . $build;
        $size_bytes = Utils::getSize($build_path);
        $size_mb = round($size_bytes / 1048576, 2);
        
        echo '<tr>';
        echo '<td style="padding: 8px 0;">' . esc_html($build) . '</td>';
        echo '<td style="padding: 8px 0;">' . esc_html($build_path) . '</td>';
        echo '<td style="padding: 8px 8px; text-align:right;">' . $size_mb . '</td>';
        echo '<td style="padding: 8px 0; text-align:right;">';
        echo '<form method="post" onsubmit="return confirm(\'❌ Supprimer définitivement le build : ' . esc_attr($build) . ' ?\');" style="margin:0;">';
        echo '<input type="hidden" name="build_name" value="' . esc_attr($build) . '">';
        submit_button('Delete', 'delete', 'delete_build', false);
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table></ul>';
    
    echo '</div>';
}

// Méthod de téléversement d'un projet unity .zip
function unity_webgl_handle_upload(): void
{
    if (!isset($_FILES['unity_zip'])) {
        return; // Pas de fichier envoyé, on ne fait rien
    }
    
    if (!current_user_can('manage_options')) {
        echo "<p style='color:red;'>❌ Erreur : permissions insuffisantes.</p>";
        return;
    }
    
    if (empty($_FILES['unity_zip'])) {
        echo "<p style='color:red;'>❌ Erreur : aucun fichier envoyé.</p>";
        return;
    }
    
    if ($_FILES['unity_zip']['error'] !== UPLOAD_ERR_OK) {
        echo "<p style='color:red;'>❌ Erreur lors de l’upload : code " . $_FILES['unity_zip']['error'] . ".</p>";
        return;
    }
    
    $file = $_FILES['unity_zip'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if (strtolower($ext) !== 'zip') {
        echo "<p style='color:red;'>❌ Erreur : seul le format ZIP est autorisé.</p>";
        return;
    }
    
    $upload_dir = wp_upload_dir();
    
    if (!is_array($upload_dir) || empty($upload_dir['basedir'])) {
        echo "<p style='color:red;'>❌ Erreur : impossible de récupérer le dossier d’upload WordPress.</p>";
        return;
    }
    
    $build_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $target_dir = $upload_dir['basedir'] . '/unity_webgl/' . sanitize_title($build_name);
    
    
    if (!file_exists($upload_dir['basedir'] . '/unity_webgl/')) {
        if (!wp_mkdir_p($upload_dir['basedir'] . '/unity_webgl/')) {
            echo "<p style='color:red;'>❌ Erreur : impossible de créer le dossier unity_webgl/.</p>";
            return;
        }
    }
    
    require_once ABSPATH . 'wp-admin/includes/file.php';
    
    if (!function_exists('WP_Filesystem')) {
        echo "<p style='color:red;'>❌ Erreur système : WP_Filesystem non disponible.</p>";
        return;
    }
    
    if (!WP_Filesystem()) {
        echo "<p style='color:red;'>❌ Erreur système : initialisation du système de fichiers échouée.</p>";
        return;
    }
    
    global $wp_filesystem;
    
    
    $is_override = false;
    if (file_exists($target_dir) && is_dir($target_dir)) {
        $is_override = true;
        if (!$wp_filesystem->delete($target_dir, true)) {
            echo "<p style='color:red;'>❌ Erreur : impossible de supprimer l’ancien build à l’emplacement : $target_dir</p>";
            return;
        }
    }
    
    
    if (!$wp_filesystem->is_dir($target_dir)) {
        if (!wp_mkdir_p($target_dir)) {
            echo "<p style='color:red;'>❌ Erreur : impossible de créer le dossier cible : $target_dir</p>";
            return;
        }
    }
    
    $zip = new ZipArchive;
    if ($zip->open($file['tmp_name']) === TRUE) {
        if (!$zip->extractTo($target_dir)) {
            echo "<p style='color:red;'>❌ Erreur : l’extraction a échoué vers $target_dir</p>";
            $zip->close();
            return;
        }
        $zip->close();
        if ($is_override) {
            echo "<p style='color:green;'>✅ Succès : le build existant a été remplacé avec succès.</p>";
        } else {
            echo "<p style='color:green;'>✅ Succès : le build Unity a été extrait avec succès dans $target_dir</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Erreur : impossible d’ouvrir le fichier .zip (" . $file['tmp_name'] . ")</p>";
    }
}
<?php
require_once 'php/Utils.php';
require_once 'php/UploadUtils.php';

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
    <span style="font-size: 18px; color: #333; margin-right: 20px;">Unity WebGL</span>
    </div>
    <a href="https://coff.ee/EnosiStudio" target="_blank" style="text-decoration: none; font-size: 16px; color: #0073aa; white-space: nowrap;">
    ☕ Support me
    </a>
    </div>
    
    <?php
    
    // _e('Current language', 'wpunity');
    
    $serverType = Utils::detectServer();
    echo "<div class='simpleblock'>";
    switch($serverType) {
        case 'apache': {
            echo "<h2>Configuration serveur : Apache détecté.</h2>";
            if (isset($_POST['add_wasm_mime'])) {
                Utils::setupWasmMime();
            }else if (isset($_POST['del_wasm_mime'])) {
                Utils::removeWasmMimeSetup();
            }
            
            // Check htaccess pour le type MIME
            if(Utils::isWasmMimeConfigured()){
                echo "<div style='color:green;'>✅ Le type MIME pour les fichiers .wasm est déjà configuré dans le .htaccess.</div>";
                echo "<form method='post'>";
                submit_button('Delete le type MIME pour .wasm', 'primary', 'del_wasm_mime');
                echo "</form>";
            }else{
                echo "<div style='color:orange;'>⚠️ Le type MIME pour les fichiers .wasm n'est pas configuré dans le .htaccess. un warning sera envoyé dans la console à chaque lancement de build</div>";
                echo "<form method='post'>";
                submit_button('Configurer le type MIME pour .wasm', 'primary', 'add_wasm_mime');
                echo "</form>";
            }
            echo "<p>La tentative d'ajout ou de suppression peut échouer pour des raisons de sécurité. </br> 
            Dans ce cas, la configuration doit être effectuée manuellement dans le fichier .htaccess.</br>
            Toute modification de la configuration du serveur nécessitera un redémarrage manuel du serveur.</p>";
            break;
        }
        case 'nginx': {
            echo "<h2>Configuration serveur : Nginx détecté.</h2> 
                <p>Veuillez configurer le type MIME pour les fichiers .wasm dans votre configuration Nginx.</br>
                La détection et configuration automatique du MIME type pour les fichiers .wasm n'est pris en charge que sous serveur Apache.</p>";
            break;
            
        }
        default:{
            echo "<h2>Configuration serveur : $serverType détecté.</h2>
            <p>La détection et configuration automatique du MIME type pour les fichiers .wasm n'est pris en charge que sous serveur Apache.</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='simpleblock'>";
    ?>
    <h2>Gestionnaire des builds</h2>
    <p>Use this page to add your Unity project by uploading the <strong>.zip</strong> folder of your project and manage
    it easily within the admin dashboard.</p>
    
    <form method="post" enctype="multipart/form-data">
    <input type="file" name="unity_zip" accept=".zip" required>
    <?php submit_button('Upload and Extract'); ?>
    </form>
    
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
    
    // Aucun build ou création d'un btn de suppression de tt les builds.
    if (empty($builds)) {
        echo '<p>Aucun build trouvé.</p>';
        // return;
    }else {
        echo '<form method="post" onsubmit="return confirm(\'❌ Supprimer TOUS les builds ?\');" style="margin-bottom: 16px;">';
        echo '<input type="hidden" name="delete_all_builds" value="1">';
        submit_button('🧨 Supprimer tous les builds', 'delete');
        echo '</form>';
    }
    
    echo '</div></div>';
    echo '<div class="footer">';
    echo '<p>Plugin développé par <a href="https://enosistudio.com/" target="_blank">Enosi Studio</a>.';
    echo '</div>';
}

// Affiche un message d'erreur dans l'interface admin
function unity_webgl_error(string $message): void {
    echo "<p style='color:red;'>❌ Erreur : $message</p>";
}

// Méthod de téléversement d'un projet unity .zip
function unity_webgl_handle_upload(): void
{
    if (!isset($_FILES['unity_zip'])) {
        return; // Pas de fichier envoyé, on ne fait rien
    }
    
    if (!current_user_can('manage_options')) {
        unity_webgl_error("permissions insuffisantes.");
        return;
    }
    
    if (empty($_FILES['unity_zip'])) {
        unity_webgl_error("aucun fichier envoyé.");
        return;
    }
    
    if ($_FILES['unity_zip']['error'] !== UPLOAD_ERR_OK) {
        unity_webgl_error("upload échoué, code d'erreur : " . $_FILES['unity_zip']['error']);
        return;
    }
    
    $file = $_FILES['unity_zip'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if (strtolower($ext) !== 'zip') {
        unity_webgl_error("seul le format ZIP est autorisé.");
        return;
    }
    
    $upload_dir = wp_upload_dir();
    
    if (!is_array($upload_dir) || empty($upload_dir['basedir'])) {
        unity_webgl_error("impossible de récupérer le dossier d’upload WordPress.");
        return;
    }
    
    $build_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $target_dir = $upload_dir['basedir'] . '/unity_webgl/' . sanitize_title($build_name);
    
    // Vérifie si le dossier unity_webgl existe, sinon le crée
    if (!file_exists($upload_dir['basedir'] . '/unity_webgl/')) {
        if (!wp_mkdir_p($upload_dir['basedir'] . '/unity_webgl/')) {
            unity_webgl_error("impossible de créer le dossier unity_webgl.");
            return;
        }
    }
    
    // Initialise le système de fichiers WordPress
    if(!UploadUtils::unity_webgl_init_filesystem()){
        unity_webgl_error("impossible d'initialiser le système de fichiers WordPress.");
    }
    
    global $wp_filesystem;    
    
    // Vérifie si le dossier cible existe déjà et le supprime si nécessaire
    $is_override = false;
    if (file_exists($target_dir) && is_dir($target_dir)) {
        $is_override = true;
        if (!$wp_filesystem->delete($target_dir, true)) {
            unity_webgl_error("impossible de supprimer l’ancien build à l'emplacement : $target_dir");
            return;
        }
    }
    
    // Crée le dossier cible s'il n'existe pas
    if (!$wp_filesystem->is_dir($target_dir)) {
        if (!wp_mkdir_p($target_dir)) {
            unity_webgl_error("impossible de créer le dossier cible : $target_dir");
            return;
        }
    }
    
    // Extrait le fichier ZIP dans le dossier cible
    $zip = new ZipArchive;
    if ($zip->open($file['tmp_name']) === TRUE) {
        if (!$zip->extractTo($target_dir)) {
            unity_webgl_error("l'extraction a échoué vers $target_dir");
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
        unity_webgl_error("impossible d’ouvrir le fichier .zip (" . $file['tmp_name'] . ")");
    }
}
?>
<?php
/**
* Plugin Name: WP Unity WebGL
* Plugin URI:  https://enosistudio.com/
* Description: Displays a Unity WebGL game inside an iframe.
* Version: 1.0
* Author: MARTIN Baptiste / Voidstack
* Plugin URI:  https://enosistudio.com/
* License: GPL2+
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: wpunity
* Domain Path: /languages
*/

/** Permet de charger le script de la page d'administration, uniquement pour l'administration (optimisation) */
if(is_admin()){
    require_once plugin_dir_path(__FILE__) . 'admin-page.php';
    require_once plugin_dir_path(__FILE__) . 'unity-block.php'; // ne s'exécute que dans l'éditeur de blocs (page/post avec Gutenberg)
}

// Ajout du main.css
function unity_enqueue_toolbar_css(): void {
    wp_enqueue_style(
        'unity-toolbar-style',
        plugins_url('css/main.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'css/main.css')
    );
}
add_action('wp_enqueue_scripts', 'unity_enqueue_toolbar_css');

load_plugin_textdomain('wpunity', false, dirname(plugin_basename(__FILE__)) . '/languages');

function unity_enqueue_scripts($build_url, $loader_name, $showOptions, $showOnMobile, $showLogs, $sizeMode, $fixedHeight, $aspectRatio):void {
    wp_enqueue_script(
        'unity-webgl',
        plugins_url('js/client-unity-block.js', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'js/client-unity-block.js'),
        true
    );
    
    wp_localize_script('unity-webgl', 'UnityWebGLData', [
        'buildUrl' => $build_url,
        'loaderName' => $loader_name,
        'showOptions' => $showOptions,
        'showOnMobile' => $showOnMobile,
        'showLogs' => $showLogs,
        'sizeMode' => $sizeMode,
        'fixedHeight' => $fixedHeight,
        'aspectRatio' => $aspectRatio,
        'urlAdmin' => admin_url('/wp-admin/admin.php'),
        'currentUserIsAdmin' => current_user_can('administrator'),
        'admMessage' => __('TempMsg', 'wpunity'),
    ]);
    
    // Permet au script client-unity-block d'import client-unity-toolbar
    add_filter('script_loader_tag', function($tag, $handle) {
        if ($handle === 'unity-webgl') {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }, 10, 2);
    
}

// Définition du shortcut [unity_webgl build="${attributes.selectedBuild}"]
function unity_build_shortcode($atts)
{
    // Magie noir de wordpress qui fait de la merde avec les Uppercases.
    $atts = shortcode_atts([
        'build' => '',
        'showoptions' => 'true',     // minuscules !
        'showonmobile' => 'false',
        'showlogs' => 'false', // Affiche les logs dans la console
        'sizemode' => 'fixed-height', // fixed-height, full-width, or custom
        'fixedheight' => 500,         // only used if sizeMode is fixed-height
        'aspectratio' => '4/3',       // format attendu : nombre/nombre (ex: 4/3)
    ], array_change_key_case($atts, CASE_LOWER), 'unity_webgl');
    
    $showOptions = filter_var($atts['showoptions'], FILTER_VALIDATE_BOOLEAN);
    $showOnMobile = filter_var($atts['showonmobile'], FILTER_VALIDATE_BOOLEAN);
    $showLogs = filter_var($atts['showlogs'], FILTER_VALIDATE_BOOLEAN);

    $sizeMode = $atts['sizemode'];
    $fixedHeight = intval($atts['fixedheight']);
    $aspectRatio = $atts['aspectratio'];
    
    if (empty($atts['build'])) {
        return '<p>❌ Unity WebGL Aucun build spécifié.</p>';
    }
    
    $build_slug = sanitize_title($atts['build']);
    $upload_dir = wp_upload_dir();
    $build_dir_path = trailingslashit($upload_dir['basedir']) . 'unity_webgl/' . $build_slug;
    $build_url = trailingslashit($upload_dir['baseurl']) . 'unity_webgl/' . trailingslashit($build_slug);
    
    // Permet de recherche dynamiquement le Build.loader.js peut importe le nom dans le dossier, c'est l'extension qui prévaut
    $loader_files = glob(pattern: $build_dir_path . '/Build/*.loader.js');
    if (empty($loader_files)) {
        return '<p style="color:red;">Unity build files not found in: ' . esc_html($build_dir_path . '/Build/') . '</p>';
    }
    $loader_filename = basename($loader_files[0]);
    $loader_name = basename($loader_filename, '.loader.js');
    
    $loader_js = $build_url . 'Build/' . $loader_name . '.loader.js';
    $framework_js = $build_url . 'Build/' . $loader_name . '.framework.js';
    $data_file = $build_url . 'Build/' . $loader_name . '.data';
    $wasm_file = $build_url . 'Build/' . $loader_name . '.wasm';
    
    if (wp_is_mobile() && !$showOnMobile) {
        return '<p>🚫 Le jeu n’est pas disponible sur mobile. Merci de le lancer depuis un ordinateur pour une meilleure expérience.</p>';
    }else{
        unity_enqueue_scripts($build_url, $loader_name, $showOptions, $showOnMobile, $showLogs, $sizeMode, $fixedHeight, $aspectRatio);
    }
    
    ob_start(); ?>
    <div id="unity-error" style="display: none; padding: 1rem; color:white;"></div>
    <div id="unity-container">
    <canvas id="unity-canvas"></canvas>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('unity_webgl', 'unity_build_shortcode');
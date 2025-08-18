<?php
require_once plugin_dir_path(__FILE__) . 'php/utils.php';

/**
* Plugin Name: Unity WebGL Integrator
* Plugin URI:  https://enosistudio.com/
* Description: Displays a Unity WebGL game inside your page.
* Version: 0.1
* Author: MARTIN Baptiste / Voidstack
* Author URI: https://www.linkedin.com/in/baptiste-martin56/
* License: GPL-3.0-or-later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Tested up to: 6.8.2
* Stable tag: 0.1
* Text Domain: webgl-embedder-for-unity
* Domain Path: /languages
*/

// empêche l'accès direct au fichier PHP via l'URL
defined('ABSPATH') or die;

/** Permet de charger le script de la page d'administration, uniquement pour l'administration (optimisation) */
if(is_admin()){
    require_once plugin_dir_path(__FILE__) . 'php/admin-page.php';
    require_once plugin_dir_path(__FILE__) . 'php/unity-block.php'; // ne s'exécute que dans l'éditeur de blocs (page/post avec Gutenberg)
}

// Ajout du main.css
function unityEnqueueToolbarCss(): void {
    wp_enqueue_style(
        'unity-toolbar-style',
        plugins_url('css/main.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'css/main.css')
    );
}
add_action('wp_enqueue_scripts', 'unityEnqueueToolbarCss');

// Language
// load_plugin_textdomain('webgl-embedder-for-unity', false, dirname(plugin_basename(__FILE__)) . '/languages');

function unityEnqueueScripts(array $unityArgs): void {
    wp_enqueue_script(
        'unity-webgl',
        plugins_url('js/client-unity-block.js', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'js/client-unity-block.js'),
        true
    );
    
    wp_localize_script('unity-webgl', 'UnityWebGLData', [
        'buildUrl' => $unityArgs['buildUrl'],
        'loaderName' => $unityArgs['loaderName'],
        'showOptions' => $unityArgs['showOptions'],
        'showOnMobile' => $unityArgs['showOnMobile'],
        'showLogs' => $unityArgs['showLogs'],
        'sizeMode' => $unityArgs['sizeMode'],
        'fixedHeight' => $unityArgs['fixedHeight'],
        'aspectRatio' => $unityArgs['aspectRatio'],
        'urlAdmin' => admin_url('/wp-admin/admin.php'),
        'currentUserIsAdmin' => current_user_can('administrator'),
        'admMessage' => __('TempMsg', 'webgl-embedder-for-unity'),
        'instanceId' => $unityArgs['uuid'],
    ]);
    
    // Permet au script client-unity-block d'import client-unity-toolbar
    if (!function_exists('unityScriptTypeModule')) {
        function unityScriptTypeModule(string $tag, string $handle): string {
            if ($handle === 'unity-webgl') {
                return str_replace('<script ', '<script type="module" ', $tag);
            }
            return $tag;
        }
        add_filter('script_loader_tag', 'unityScriptTypeModule', 10, 2);
    }
}

// Definition of the shortcode [unity_webgl build="${attributes.selectedBuild}"]
function unityBuildShortcode(array $atts): string
{
    // Normalize shortcode attributes to lowercase keys and set default values
    // WordPress sometimes messes with uppercase keys in shortcode attributes
    $atts = shortcode_atts([
        'build' => '',
        'showoptions' => 'true',     // minuscules !
        'showonmobile' => 'false',
        'showlogs' => 'false', // Affiche les logs dans la console
        'sizemode' => 'fixed-height', // fixed-height, full-width, or custom
        'fixedheight' => 500,         // only used if sizeMode is fixed-height
        'aspectratio' => '4/3',       // format attendu : nombre/nombre (ex: 4/3)
    ], array_change_key_case($atts, CASE_LOWER), 'unity_webgl');
    
    // Sanitize and convert attribute values to proper types
    $build_slug = sanitize_title($atts['build']);
    $showOptions = filter_var($atts['showoptions'], FILTER_VALIDATE_BOOLEAN);
    $showOnMobile = filter_var($atts['showonmobile'], FILTER_VALIDATE_BOOLEAN);
    $showLogs = filter_var($atts['showlogs'], FILTER_VALIDATE_BOOLEAN);
    $fixedHeight = intval($atts['fixedheight']);
    $sizeMode = sanitize_text_field($atts['sizemode']);
    $aspectRatio = sanitize_text_field($atts['aspectratio']);
    
    // Determine the local server path and URL to the Unity build directory
    $upload_dir = wp_upload_dir();
    $build_dir_path = trailingslashit($upload_dir['basedir']) . 'unity_webgl/' . $build_slug;
    $build_url = trailingslashit($upload_dir['baseurl']) . 'unity_webgl/' . trailingslashit($build_slug);
    
    // Construct the path to the Unity loader script
    $loader_file = $build_dir_path . '/' . $build_slug . '.loader.js';
    
    // Check if the loader script exists, else show an error
    if (!file_exists($loader_file)) {
        // translators: %s is the Unity build loader file path.
        return '<p style="color:red;">' . sprintf(esc_html__('Unity build file not found: %s', 'webgl-embedder-for-unity'),esc_html($loader_file)) . '</p>';
    }
    
    // Extract the loader name from the loader filename (e.g. "Build")
    $loader_name = basename($loader_file, '.loader.js');
    
    // If visitor is on mobile and game is not allowed on mobile, show a message
    if (wp_is_mobile() && !$showOnMobile) {
        return '<p>🚫 ' . esc_html__('The game is not available on mobile. Please launch it on a computer for a better experience.', 'webgl-embedder-for-unity') . '</p>';
    }
    
    $styleSizeMode = match ($sizeMode) {
        'fixed-height' => "height: {$fixedHeight}px;",
        'aspect-ratio' => "aspect-ratio: {$aspectRatio};",
        default => 'ERROR',
    };
    
    $uuid = Utils::generateUuid();
    unityEnqueueScripts([
        'buildUrl' => $build_url,
        'loaderName' => $loader_name,
        'showOptions' => $showOptions,
        'showOnMobile' => $showOnMobile,
        'showLogs' => $showLogs,
        'sizeMode' => $sizeMode,
        'fixedHeight' => $fixedHeight,
        'aspectRatio' => $aspectRatio,
        'uuid' => $uuid,
    ]);
    
    // Start output buffering to capture the HTML output
    ob_start(); ?>
    <div id="<?php echo esc_attr( $uuid ); ?>-error" class="unity-error"></div>
    <div id="<?php echo esc_attr( $uuid ); ?>-container" class="unity-container" style="<?php echo esc_attr($styleSizeMode); ?>">
    <canvas
    id="<?php echo esc_attr($uuid); ?>-canvas"
    class="unity-canvas"
    data-build-url="<?php echo esc_attr($build_url) ?>"
    data-loader-name="<?php echo esc_attr($loader_name) ?>"
    data-show-options="<?php echo $showOptions ? 'true' : 'false' ?>"
    data-show-logs="<?php echo $showLogs ? 'true' : 'false' ?>"
    data-size-mode="<?php echo esc_attr($sizeMode) ?>"
    data-fixed-height="<?php echo intval($fixedHeight) ?>"
    data-aspect-ratio="<?php echo esc_attr($aspectRatio) ?>"
    ></canvas>
    </div>
    <?php
    // Return the buffered HTML as the shortcode output
    return ob_get_clean();
}
add_shortcode('unity_webgl', 'unityBuildShortcode');

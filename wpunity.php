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

// Définition du shortcut [unity_webgl build="${attributes.selectedBuild}"]
function unity_build_shortcode($atts)
{
    $atts = shortcode_atts([
        'build' => '',
    ], $atts, 'unity_webgl');

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

    ob_start(); ?>
    <div id="unity-error" style="display: none; padding: 1rem; color:white;"></div>
    <div id="unity-container" style="width: 100%; height: 600px; color:white;">
        <canvas id="unity-canvas" width="960" height="600" style="width: 100%; height: 100%; background: #000;"></canvas>
        <script>
            const buildUrl = "<?php echo esc_js($build_url); ?>Build";
            const loaderUrl = buildUrl + "/<?php echo esc_js($loader_name); ?>.loader.js"; // <- ici on utilise loader_name

            const unityCanvas = document.getElementById("unity-canvas");
            const errorDiv = document.getElementById("unity-error");
            const unityContainer = document.getElementById("unity-container");

            // Fonction appelée par la config du jeu.
            function unityShowBanner(msg, type) {
                function updateBannerVisibility() {
                    errorDiv.style.display = errorDiv.children.length ? 'block' : 'none';
                    unityCanvas.style.display = errorDiv.children.length ? 'none' : 'block';
                    unityContainer.style.display = errorDiv.children.length ? 'none' : 'block';
                }
                var div = document.createElement('div');
                div.innerHTML = msg;
                errorDiv.appendChild(div);
                if (type == 'error') div.style = 'background: darkred; padding: 10px;';
                else {
                    if (type == 'warning') div.style = 'background: darkorange; padding: 10px;';
                    setTimeout(function () {
                        errorDiv.removeChild(div);
                        updateBannerVisibility();
                    }, 5000);
                }
                updateBannerVisibility();
            }

            // La config du jeu.
            var config = {
                dataUrl: buildUrl + "/<?php echo esc_js($loader_name); ?>.data",
                frameworkUrl: buildUrl + "/<?php echo esc_js($loader_name); ?>.framework.js",
                codeUrl: buildUrl + "/<?php echo esc_js($loader_name); ?>.wasm",
                streamingAssetsUrl: "StreamingAssets",
                companyName: "EnosiStudio",
                productName: "EnosiStudio",
                productVersion: "1.0",
                showBanner: unityShowBanner,
            };

            const script = document.createElement("script");
            script.src = loaderUrl;

            script.onload = () => {
                createUnityInstance(unityCanvas, config, (progress) => {
                    // loading
                }).then((unityInstance) => {
                    // quand l'instance est chargé
                }).catch((message) => {
                    alert(message);
                });
            };

            document.body.appendChild(script);
        </script>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('unity_webgl', 'unity_build_shortcode');
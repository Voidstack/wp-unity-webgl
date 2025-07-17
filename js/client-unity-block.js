import { UnityToolbar } from "./client-unity-toolbar.js";

// Références aux éléments DOM principaux
const unityCanvas = document.getElementById("unity-canvas");
const errorDiv = document.getElementById("unity-error");
const unityContainer = document.getElementById("unity-container");

/**
 * Affiche un message dans la bannière d'erreur ou d'avertissement
 * @param {string} msg - Le message à afficher
 * @param {string} type - Type de message: "error" ou "warning"
 */
function unityShowBanner(msg, type) {
  // Met à jour la visibilité des éléments en fonction de la présence d'erreurs
  function updateBannerVisibility() {
    const hasError = errorDiv.children.length > 0;
    errorDiv.style.display = hasError ? "block" : "none";
    unityCanvas.style.display = hasError ? "none" : "block";
    unityContainer.style.display = hasError ? "none" : "block";
  }

  const div = document.createElement("div");
  div.innerHTML = `${UnityWebGLData.admMessage} : ${msg}`;

  if (type === "error") {
    div.style.background = "darkred";
  } else if (type === "warning") {
    if (UnityWebGLData.currentUserIsAdmin) {
      div.style.background = "darkorange";
      // Supprime le message après un court délai pour éviter d’encombrer l’UI
      setTimeout(() => {
        errorDiv.removeChild(div);
        updateBannerVisibility();
      }, 10);
    } else {
      // Ne pas afficher l’avertissement aux non-admins
      return;
    }
  }
  div.style.padding = "10px";
  errorDiv.appendChild(div);
  updateBannerVisibility();
}

// Construction des URLs à partir des variables passées par PHP
const buildUrl = UnityWebGLData.buildUrl + "Build";
const loaderName = UnityWebGLData.loaderName;

// Configuration de Unity WebGL
const config = {
  dataUrl: buildUrl + "/" + loaderName + ".data",
  frameworkUrl: buildUrl + "/" + loaderName + ".framework.js",
  codeUrl: buildUrl + "/" + loaderName + ".wasm",
  streamingAssetsUrl: "StreamingAssets",
  companyName: "EnosiStudio",
  productName: "EnosiStudio",
  productVersion: "1.0",
  showBanner: unityShowBanner,
};

const loaderUrl = buildUrl + "/" + loaderName + ".loader.js";

// Création du script pour charger Unity WebGL
const script = document.createElement("script");
script.src = loaderUrl;

/** Fonction qui permet de créer la toolbar sous le canvas */
function createToolbar(canvas) {
  const toolbar = document.createElement("div");
  toolbar.id = "unity-toolbar";

  const fullscreenBtn = document.createElement("button");
  fullscreenBtn.id = "fullscreen-btn";
  fullscreenBtn.textContent = "⛶";

  fullscreenBtn.addEventListener("click", () => {
    // Appelle la méthode fullscreen compatible selon le navigateur
    const requestFS =
      canvas.requestFullscreen ||
      canvas.webkitRequestFullscreen ||
      canvas.msRequestFullscreen;
    if (requestFS) requestFS.call(canvas);
  });

  toolbar.appendChild(fullscreenBtn);

  // Ajoute la toolbar dans le conteneur du canvas
  const container = canvas.parentElement;
  container.style.position = "relative";
  container.appendChild(toolbar);
}

// Quand le script Unity est chargé, on initialise l’instance
script.onload = () => {
  createUnityInstance(unityCanvas, config, (progress) => {})
    .then((unityInstance) => {
      if (UnityWebGLData.showOptions) {
        new UnityToolbar(unityCanvas);
        //        createToolbar(unityCanvas);
      }
    })
    .catch((message) => {
      alert(message);
    });
};

// Ajoute le script Unity au DOM pour démarrer le chargement
document.body.appendChild(script);

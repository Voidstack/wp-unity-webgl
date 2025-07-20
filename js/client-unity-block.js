import { UnityToolbar } from "./client-unity-toolbar.js";

const uuid = UnityWebGLData.instanceId;

// Références aux éléments DOM principaux
 const unityCanvas = document.getElementById(uuid + "-canvas");
 const errorDiv = document.getElementById(uuid + "-error");
 const unityContainer = document.getElementById(uuid + "-container");

unityCanvas.className = "unity-canvas";
errorDiv.className = "unity-error";
unityContainer.className = "unity-container";

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
const buildUrl = UnityWebGLData.buildUrl;
const loaderName = UnityWebGLData.loaderName;

// Configuration de Unity WebGL
const config = {
  dataUrl: buildUrl + "/Build.data",
  frameworkUrl: buildUrl + "/Build.framework.js",
  codeUrl: buildUrl + "/Build.wasm",
  streamingAssetsUrl: "StreamingAssets",
  webglContextAttributes: {
    // Permet de capturer le contenu du canvas pour les captures d'écran
    preserveDrawingBuffer: true
  },
  companyName: "EnosiStudio",
  productName: "EnosiStudio",
  productVersion: "1.0",
  showBanner: unityShowBanner,
};

const loaderUrl = buildUrl + "/" + loaderName + ".loader.js";

// Création du script pour charger Unity WebGL
const script = document.createElement("script");
script.src = loaderUrl;

// Quand le script Unity est chargé, on initialise l’instance
script.onload = () => {
  const originalLog = console.log;
  if(!UnityWebGLData.showLogs){
    console.log("Unity logs are hidden.");
    console.log = () => {};
  }
  
  createUnityInstance(unityCanvas, config, (progress) => {})
  .then((unityInstance) => {
    if(UnityWebGLData.sizeMode === "fixed-height") {
      unityContainer.style.height = UnityWebGLData.fixedHeight + "px";
    }else if(UnityWebGLData.sizeMode === "aspect-ratio") {
      unityContainer.style.aspectRatio = UnityWebGLData.aspectRatio;
    }
    if (UnityWebGLData.showOptions) {
      new UnityToolbar(unityCanvas, uuid);
    }
  })
  .finally(() => {
    // Permet de cacher les logs de Unity
    if(!UnityWebGLData.showLogs){
      console.log = originalLog;
    }
  })
  .catch((message) => {
    alert(message);
  });
};

// Ajoute le script Unity au DOM pour démarrer le chargement
document.body.appendChild(script);

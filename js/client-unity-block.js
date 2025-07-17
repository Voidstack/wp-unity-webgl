const unityCanvas = document.getElementById("unity-canvas");
const errorDiv = document.getElementById("unity-error");
const unityContainer = document.getElementById("unity-container");

function unityShowBanner(msg, type) {
  function updateBannerVisibility() {
    errorDiv.style.display = errorDiv.children.length ? "block" : "none";
    unityCanvas.style.display = errorDiv.children.length ? "none" : "block";
    unityContainer.style.display = errorDiv.children.length ? "none" : "block";
  }
  const div = document.createElement("div");
  div.innerHTML = UnityWebGLData.admMessage + " : " + msg;
  errorDiv.appendChild(div);
  if (type === "error") div.style = "background: darkred; padding: 10px;";
  else if (type === "warning") {
    if (UnityWebGLData.currentUserIsAdmin) {
      div.style = "background: darkorange; padding: 10px;";
      setTimeout(() => {
        errorDiv.removeChild(div);
        updateBannerVisibility();
      }, 10); // TODO : util pour afficher les potentiels problèmes.
    } else {
      errorDiv.removeChild(div);
      updateBannerVisibility();
    }
  }
  updateBannerVisibility();
}

const buildUrl = UnityWebGLData.buildUrl + "Build";
const loaderName = UnityWebGLData.loaderName;

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
const script = document.createElement("script");
script.src = loaderUrl;

function createToolbar(canvas) {
  const toolbar = document.createElement("div");
  toolbar.id = "unity-toolbar";

  const fullscreenBtn = document.createElement("button");
  fullscreenBtn.id = "fullscreen-btn";
  fullscreenBtn.textContent = "⛶";

  fullscreenBtn.addEventListener("click", () => {
    if (canvas.requestFullscreen) {
      canvas.requestFullscreen();
    } else if (canvas.webkitRequestFullscreen) {
      canvas.webkitRequestFullscreen();
    } else if (canvas.msRequestFullscreen) {
      canvas.msRequestFullscreen();
    }
  });

  toolbar.appendChild(fullscreenBtn);
  const container = canvas.parentElement;
  container.style.position = "relative";
  container.appendChild(toolbar);
}

script.onload = () => {
  createUnityInstance(unityCanvas, config, (progress) => {})
    .then((unityInstance) => {
      if (UnityWebGLData.showOptions) {
        createToolbar(unityCanvas);
      }
    })
    .catch((message) => {
      alert(message);
    });
};

document.body.appendChild(script);

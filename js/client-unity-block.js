import { UnityToolbar } from "./client-unity-toolbar.js";

class UnityInstanceManager {
  constructor(uuid) {
    this.uuid = uuid;
    this.unityCanvas = document.getElementById(uuid + "-canvas");
    this.errorDiv = document.getElementById(uuid + "-error");
    this.unityContainer = document.getElementById(uuid + "-container");
    this.config = null;
    this.unityInstance = null;
    
    this.canvasData = {
      buildUrl: this.unityCanvas.dataset.buildUrl,
      loaderName: this.unityCanvas.dataset.loaderName,
      showOptions: this.unityCanvas.dataset.showOptions === 'true',
      showLogs: this.unityCanvas.dataset.showLogs === 'true',
      sizeMode: this.unityCanvas.dataset.sizeMode,
      fixedHeight: parseInt(this.unityCanvas.dataset.fixedHeight, 10),
      aspectRatio: this.unityCanvas.dataset.aspectRatio,
    };
  }
  
  showBanner(msg, type) {
    const updateBannerVisibility = () => {
      const hasError = this.errorDiv.children.length > 0;
      this.errorDiv.style.display = hasError ? "block" : "none";
      this.unityCanvas.style.display = hasError ? "none" : "block";
      this.unityContainer.style.display = hasError ? "none" : "block";
    };
    
    const div = document.createElement("div");
    div.innerHTML = `${UnityWebGLData.admMessage} : ${msg}`;
    
    if (type === "error") {
      div.style.background = "darkred";
    } else if (type === "warning") {
      if (UnityWebGLData.currentUserIsAdmin) {
        div.style.background = "darkorange";
        setTimeout(() => {
          this.errorDiv.removeChild(div);
          updateBannerVisibility();
        }, 2000);
      } else {
        return;
      }
    }
    div.style.padding = "10px";
    this.errorDiv.appendChild(div);
    updateBannerVisibility();
  }
  
  async getUnityBuildFiles(buildUrl) {
    const res = await fetch(buildUrl);
    const text = await res.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(text, "text/html");
    const links = Array.from(doc.querySelectorAll("a")).map(a => a.getAttribute("href"));
    
    const resolveFile = (ext) => links.find(href => href.endsWith(ext));
    
    return {
      dataUrl: buildUrl + "/" + resolveFile(".data"),
      frameworkUrl: buildUrl + "/" + resolveFile(".framework.js"),
      loaderUrl: buildUrl + "/" + resolveFile(".loader.js"),
      wasmUrl: buildUrl + "/" + resolveFile(".wasm"),
    };
  }
  
  async load(buildUrl, loaderName, configOverrides = {}) {
    const files = await this.getUnityBuildFiles(buildUrl);
    console.log("Unity files loaded:", files);
    
    this.config = {
      dataUrl: files.dataUrl,
      frameworkUrl: files.frameworkUrl,
      codeUrl: files.wasmUrl,
      streamingAssetsUrl: "StreamingAssets",
      webglContextAttributes: { preserveDrawingBuffer: true },
      companyName: "EnosiStudio",
      productName: "EnosiStudio",
      productVersion: "1.0",
      showBanner: this.showBanner.bind(this),
      ...configOverrides
    };
    
    await this.loadScript(files.loaderUrl);
    
    const originalLog = console.log;
    if (!this.canvasData.showLogs) {
      console.log = () => {};
    }
    
    try {
      this.unityInstance = await createUnityInstance(
        this.unityCanvas,
        this.config,
        (progress) => {
          // optionnel: progress callback
        }
      );
      
      if (this.canvasData.sizeMode === "fixed-height") {
        this.unityContainer.style.height = this.canvasData.fixedHeight + "px";
      } else if (this.canvasData.sizeMode === "aspect-ratio") {
        this.unityContainer.style.aspectRatio = this.canvasData.aspectRatio;
      }
      
      if (this.canvasData.showOptions) {
        new UnityToolbar(this.unityCanvas, this.uuid);
      }
    } catch (e) {
      alert(e);
    } finally {
      if (!this.canvasData.showLogs) {
        console.log = originalLog;
      }
    }
  }
  
  loadScript(src) {
    return new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = src;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error(`Failed to load script ${src}`));
      document.body.appendChild(script);
    });
  }
}

// Exemple d'initialisation pour plusieurs instances
document.querySelectorAll("[id$='-canvas']").forEach((canvas) => {
  const uuid = canvas.id.replace("-canvas", "");
  const manager = new UnityInstanceManager(uuid);
  
  // récupère buildUrl, loaderName dynamiquement depuis tes variables globales ou dataset HTML
  manager.load(manager.canvasData.buildUrl, manager.canvasData.loaderName);
});

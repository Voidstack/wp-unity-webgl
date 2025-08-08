export class UnityLoader {
  constructor(container) {
    this.container = container;
    this.loaderDiv = document.createElement("div");
    this.loaderDiv.className = "unity-loader";
    this.loaderDiv.innerHTML = /* html */ ` 
      <div class="is-alive-loading"></div>
      
      <div class="unitygl-progress-bar">    
        <div class="unitygl-progress-fill"></div>
      </div>
    `;
    this.container.appendChild(this.loaderDiv);
    this.progressFill = this.loaderDiv.querySelector(".unitygl-progress-fill");
  }

  updateProgress(progress) {
    if (this.progressFill) {
      this.progressFill.style.width = `${progress * 100}%`;
    }
  }

  show() {
    this.loaderDiv.style.display = "flex";
  }

  hide() {
    this.loaderDiv.style.transition = "opacity 0.5s ease";
    this.loaderDiv.style.opacity = "0";

    setTimeout(() => {
      this.loaderDiv.style.display = "none";
    }, 500);
  }
}

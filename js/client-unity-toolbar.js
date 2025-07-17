export class UnityToolbar {
  constructor(canvas) {
    // Store reference to the Unity canvas element
    this.canvas = canvas;

    // Create the toolbar container
    this.toolbar = document.createElement("div");
    this.toolbar.id = "unity-toolbar";

    // Add toolbar buttons
    this._createSoundToggleButton();
    this._createFullscreenButton();

    // Attach toolbar to DOM
    this._attach();
  }

  // Creates and adds a fullscreen toggle button to the toolbar
  _createFullscreenButton() {
    const btn = document.createElement("button");
    btn.id = "fullscreen-btn";
    btn.textContent = "⛶";

    // Handle fullscreen toggle on click
    btn.addEventListener("click", () => {
      const req =
        this.canvas.requestFullscreen ||
        this.canvas.webkitRequestFullscreen ||
        this.canvas.msRequestFullscreen;
      if (req) req.call(this.canvas);
    });

    this.toolbar.appendChild(btn);
  }

  // Creates and adds a sound toggle button to the toolbar
  _createSoundToggleButton() {
    const btn = document.createElement("button");
    btn.id = "sound-toggle-btn";
    btn.textContent = "🔊";
    let muted = false;

    // Handle sound mute/unmute on click
    btn.addEventListener("click", () => {
      muted = !muted;
      btn.textContent = muted ? "🔇" : "🔊";

      toggleMutePage(muted);

      // Call Unity method to mute/unmute audio
      if (typeof UnityWebGL !== "undefined" && UnityWebGL.unityInstance) {
        //        UnityWebGL.unityInstance.SetMute(muted);
      }
    });

    this.toolbar.appendChild(btn);
  }

  // Attach the toolbar to the canvas container
  _attach() {
    const container = this.canvas.parentElement;
    container.style.position = "relative";
    container.appendChild(this.toolbar);
  }

  // Mute/unmute all audio elements on the page
  _toggleMutePage(muted) {
    this.canvas.muted = muted;
  }
}

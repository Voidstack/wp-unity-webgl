const UnityLoaderSingleton = (function () {
    // Class Action is used to manage subscribers and trigger actions
    class Action {
        constructor() {
            this.subscribers = new Set();
        }
        subscribe(fn) {
            this.subscribers.add(fn);
        }
        unsubscribe(fn) {
            this.subscribers.delete(fn);
        }
        trigger(...args) {
            for (const fn of this.subscribers) fn(...args);
        }
    }
    
    let instance = null;
    const myAction = new Action();
    
    async function create(canvas, config, onProgress) {
        if (instance) {
            console.log("[WPUnity] Recreating instance : " + canvas.id);
            await instance.Quit();  // quitte l’instance en cours
            instance = null;
        }else{
            console.log("[WPUnity] Creating instance for the first time : " + canvas.id);
        }
        
        // Ici tt les canvas doivent fermer l’instance Unity en cours avant de créer une nouvelle instance
        myAction.trigger(canvas);
        instance = await createUnityInstance(canvas, config, onProgress);
        
        return instance;
    }
    
    return {
        load: create,
        onAction: myAction,
    };
})();

console.log("UnityLoaderSingleton.onAction", UnityLoaderSingleton.onAction);
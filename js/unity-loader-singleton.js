const UnityLoaderSingleton = (function () {
    let instance = null;

    async function create(canvas, config, onProgress) {
        if (instance) {
            await instance.Quit();  // quitte l’instance en cours
            instance = null;
        }
        instance = await createUnityInstance(canvas, config, onProgress);
        return instance;
    }

    return {
        load: create
    };
})();

const { registerBlockType } = wp.blocks;
const { createElement: el, useEffect } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { CheckboxControl, PanelBody } = wp.components;

// Définition de l'icône personnalisée SVG pour le bloc Unity
const iconUnity = {
  src: el(
    "svg",
    { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 64 64" },
    el("path", {
      d: "M63.22 25.42L56.387 0 30.87 6.814l-3.775 6.637-7.647-.055L.78 32.005l18.67 18.604 7.658-.057 3.78 6.637 25.5 6.81 6.832-25.418L59.34 32zm-16-15.9L36.036 28.86H13.644l14.094-14.34zM36.036 35.145l11.196 19.338-19.507-5.012L13.63 35.15h22.392zm5.468-3.14L52.7 12.665l5.413 19.34L52.7 51.34z",
    })
  ),
};

registerBlockType("mon-plugin/unity-webgl", {
  title: "Unity WebGL", // Nom affiché du bloc
  icon: iconUnity, // Icône personnalisée
  category: "embed", // Catégorie du bloc dans l’éditeur
  attributes: {
    selectedBuild: {
      type: "string",
      default: "", // Attribut qui stocke le build Unity sélectionné
    },
    showOptions: { type: "boolean", default: true },
    showOnMobile: { type: "boolean", default: false },
  },

  // Fonction d’édition du bloc (affichage dans l’admin WordPress)
  edit: (props) => {
    const { attributes, setAttributes } = props;
    const { selectedBuild, showOptions, showOnMobile } = attributes;

    // Si aucun build n’est disponible dans la variable globale, on affiche un message
    if (!window.unityBuildsData || !window.unityBuildsData.builds.length) {
      return el("p", null, "Aucun build Unity trouvé.");
    }

    const builds = window.unityBuildsData.builds;

    // On vérifie que le build sélectionné est bien dans la liste des builds valides
    const validSelectedBuild = builds.includes(selectedBuild)
      ? selectedBuild
      : "";

    // Effet React : si le build sélectionné est invalide, on le reset à vide
    useEffect(() => {
      if (validSelectedBuild !== selectedBuild) {
        setAttributes({ selectedBuild: "" });
      }
    }, [selectedBuild, validSelectedBuild, setAttributes]);

    // Retourne l’interface d’édition : label + select + affichage build sélectionné
    const mainContent = el(
      "div",
      { style: { border: "1px solid grey", padding: "10px" } },
      el("label", { htmlFor: "select-build" }, WP_I18N.buildChoose),
      el(
        "select",
        {
          id: "select-build",
          value: validSelectedBuild,
          onChange: (e) => setAttributes({ selectedBuild: e.target.value }),
          "aria-label": WP_I18N.buildChoose,
        },
        el("option", { value: "" }, "-- Aucun --"),
        builds.map((build) => el("option", { key: build, value: build }, build))
      ),
      validSelectedBuild &&
        el(
          "div",
          { style: { marginTop: "10px" } },
          `Build sélectionné : ${validSelectedBuild}`
        )
    );

    // Panel à droite
    const inspector = el(
      InspectorControls,
      null,
      el(
        PanelBody,
        { title: "Options", initialOpen: true },
        el(CheckboxControl, {
          label: "Afficher les options",
          checked: props.attributes.showOptions,
          onChange: (value) => props.setAttributes({ showOptions: value }),
        }),
        el(CheckboxControl, {
          label: "Afficher sur mobile",
          checked: props.attributes.showOnMobile,
          onChange: (value) => props.setAttributes({ showOnMobile: value }),
        })
      )
    );

    // retourne les deux
    return [inspector, mainContent];
  },

  // Fonction qui sauvegarde la sortie HTML du bloc (affichage côté front)
  save: ({ attributes }) => {
    const { selectedBuild, showOptions, showOnMobile } = attributes;
    const shortcode = `[unity_webgl build="${selectedBuild}" showOptions="${
      showOptions ? "true" : "false"
    }" showOnMobile="${showOnMobile ? "true" : "false"}"]`;
    console.log("Shortcode:", shortcode);
    return el("div", null, shortcode);
  },
});

const { registerBlockType } = wp.blocks;
const { createElement, useState } = wp.element;

const STR_UNITY_VIEW_SHORTCUT = "unity_webgl";

const iconUnity = {
  src: wp.element.createElement(
    "svg",
    { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 64 64" },
    wp.element.createElement("path", {
      d: "M63.22 25.42L56.387 0 30.87 6.814l-3.775 6.637-7.647-.055L.78 32.005l18.67 18.604 7.658-.057 3.78 6.637 25.5 6.81 6.832-25.418L59.34 32zm-16-15.9L36.036 28.86H13.644l14.094-14.34zM36.036 35.145l11.196 19.338-19.507-5.012L13.63 35.15h22.392zm5.468-3.14L52.7 12.665l5.413 19.34L52.7 51.34z",
    })
  ),
};

registerBlockType("mon-plugin/unity-webgl", {
  title: "Unity WebGL",
  icon: iconUnity,
  category: "embed",
  attributes: {
    selectedBuild: {
      type: "string",
      default: "",
    },
  },
  // Ici est construit le block d'édition dans l'interface d'admin de wordpress.
  edit: ({ attributes, setAttributes }) => {
    const { selectedBuild } = attributes;

    if (!window.unityBuildsData || !window.unityBuildsData.builds.length) {
      return createElement("p", null, "Aucun build Unity trouvé.");
    }

    const validSelectedBuild = window.unityBuildsData.builds.includes(
      selectedBuild
    )
      ? selectedBuild
      : "";

    if (validSelectedBuild !== selectedBuild) {
      setAttributes({ selectedBuild: "" });
    }

    return createElement(
      "div",
      { style: { border: "1px solid grey", padding: "10px" } },
      createElement("label", null, "Choisissez un build Unity :"),
      createElement(
        "select",
        {
          value: validSelectedBuild,
          onChange: (e) => setAttributes({ selectedBuild: e.target.value }),
        },
        createElement("option", { value: "" }, "-- Aucun --"),
        window.unityBuildsData.builds.map((build) =>
          createElement("option", { key: build, value: build }, build)
        )
      ),
      validSelectedBuild &&
        createElement(
          "div",
          { style: { marginTop: "10px" } },
          `Build sélectionné : ${validSelectedBuild}`
        )
    );
  },
  save: ({ attributes }) => {
    return createElement(
      "div",
      null,
      `[unity_webgl build="${attributes.selectedBuild}"]`
    );
  },
});

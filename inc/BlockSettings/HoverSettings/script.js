const { createElement: el, Fragment } = wp.element;

function isBlockSupportBackgroundColor(blockName) {
  const blockSupport = window.wp.blocks.getBlockSupport(blockName, "color");
  return (
    blockSupport?.background ||
    blockSupport?.__experimentalDefaultControls?.background
  );
}

function isBlockSupportTextColor(blockName) {
  const blockSupport = window.wp.blocks.getBlockSupport(blockName, "color");
  return (
    blockSupport?.text || blockSupport?.__experimentalDefaultControls?.text
  );
}

function isBlockSupportBorder(blockName) {
  return window.wp.blocks.hasBlockSupport(blockName, "__experimentalBorder");
}

/** Register attribute */
function registerHoverSettingsAttributes(settings) {
  if (typeof settings.attributes === "undefined") {
    return settings;
  }

  if (!settings.attributes.style) {
    settings.attributes.style = {};
  }

  let newAttributes = {};

  if (isBlockSupportBackgroundColor(settings)) {
    newAttributes.hoverBackgroundColor = {
      type: "string",
      default: "",
    };
  }

  if (isBlockSupportTextColor(settings)) {
    newAttributes.hoverTextColor = {
      type: "string",
      default: "",
    };
  }

  if (isBlockSupportBorder(settings)) {
    newAttributes.hoverBorder = {
      type: "object",
      default: {},
    };
  }

  settings.attributes.style = Object.assign(
    settings.attributes.style,
    newAttributes
  );

  return settings;
}

wp.hooks.addFilter(
  "blocks.registerBlockType",
  "shadcn/hover-settings-attributes",
  registerHoverSettingsAttributes
);

/** Display controls */
const addHoverSettingsControls = wp.compose.createHigherOrderComponent(
  (BlockEdit) => {
    return (props) => {
      const { isSelected, name: blockName } = props;
      const canAddSettings =
        isBlockSupportBackgroundColor(blockName) ||
        isBlockSupportTextColor(blockName) ||
        isBlockSupportBorder(blockName);
      return el(
        Fragment,
        null,
        el(BlockEdit, props),
        isSelected && canAddSettings && el(Settings, props)
      );
    };
  },
  "addHoverSettingsControls"
);

wp.hooks.addFilter(
  "editor.BlockEdit",
  "shadcn/hover-settings-controls",
  addHoverSettingsControls
);

const addHoverSettingsStyleToBlock = wp.compose.createHigherOrderComponent(
  (BlockListBlock) => {
    return (props) => {
      const { attributes } = props;
      const extraWrapperProps = props.wrapperProps ?? {};
      const hoverBackgroundColor = attributes.style?.hoverBackgroundColor;
      const hoverTextColor = attributes.style?.hoverTextColor;
      const hoverBorder = attributes.style?.hoverBorder;

      if (
        Object.keys(attributes.style ?? {}).includes("hoverBackgroundColor") &&
        hoverBackgroundColor
      ) {
        extraWrapperProps.style = {
          ...extraWrapperProps.style,
          "--hover-background-color": hoverBackgroundColor,
        };
      }
      if (
        Object.keys(attributes.style ?? {}).includes("hoverTextColor") &&
        hoverTextColor
      ) {
        extraWrapperProps.style = {
          ...extraWrapperProps.style,
          "--hover-color": hoverTextColor,
        };
      }

      if (
        Object.keys(attributes.style ?? {}).includes("hoverBorder") &&
        hoverBorder
      ) {
        extraWrapperProps.style = {
          ...extraWrapperProps.style,
          ...getBorderHoverStyleFromAttributes(attributes, props),
        };
      }

      return el(BlockListBlock, {
        ...props,
        wrapperProps: { ...extraWrapperProps },
      });
    };
  },
  "addHoverSettingsStyleToBlock"
);

wp.hooks.addFilter(
  "editor.BlockListBlock",
  "shadcn/hover-settings-style",
  addHoverSettingsStyleToBlock
);

function getBorderHoverStyleFromAttributes(attributes, props) {
  const style = {};
  if (
    ["core/group", "core/columns"].includes(props.block?.name) &&
    attributes.style?.border?.radius
  ) {
    style["overflow"] = "hidden";
  }

  if (Object.keys(attributes.style?.hoverBorder ?? {}).length > 0) {
    if (Object.keys(attributes.style?.hoverBorder ?? {}).includes("top")) {
      ["top", "bottom", "left", "right"].forEach((aspect) => {
        style[`--hover-border-${aspect}-c`] =
          attributes.style?.hoverBorder[aspect].color;
        style[`--hover-border-${aspect}-w`] =
          attributes.style?.hoverBorder[aspect].width;
        style[`--hover-border-${aspect}-s`] =
          attributes.style?.hoverBorder[aspect].style;
      });
    } else {
      style[`--hover-border-c`] = attributes.style?.hoverBorder.color;
      style[`--hover-border-w`] = attributes.style?.hoverBorder.width;
      style[`--hover-border-s`] = attributes.style?.hoverBorder.style;
    }
  }

  return style;
}

const { useEffect, useRef } = wp.element;
const { __ } = wp.i18n;
const {
  __experimentalToolsPanelItem: ToolsPanelItem,
  __experimentalBorderBoxControl: BorderBoxControl,
  __experimentalToolsPanel: ToolsPanel,
} = wp.components;
const {
  InspectorControls,
  __experimentalColorGradientSettingsDropdown: ColorGradientSettingsDropdown,
  __experimentalUseMultipleOriginColorsAndGradients:
    useMultipleOriginColorsAndGradients,
} = wp.blockEditor;

function Settings(props) {
  const isFirstRender = useRef(true);

  const colorGradientSettings = useMultipleOriginColorsAndGradients();
  const { attributes, setAttributes, name: blockName, clientId } = props;

  const hoverBackgroundColor = attributes.style?.hoverBackgroundColor ?? "";
  const hoverTextColor = attributes.style?.hoverTextColor ?? "";
  const hoverBorder = attributes.style?.hoverBorder ?? {};
  const hasHoverTextColor = isBlockSupportTextColor(blockName) ?? false;
  const hasHoverBackgroundColor =
    isBlockSupportBackgroundColor(blockName) ?? false;
  const hasHoverBorder = isBlockSupportBorder(blockName) ?? false;

  useEffect(() => {
    const isSupportedGenerateColorBlocks = [
      "core/button",
      "woocommerce/product-button",
      "shadcn/button",
    ];
    if (!isSupportedGenerateColorBlocks.includes(blockName)) {
      return;
    }
    if (isFirstRender.current) {
      isFirstRender.current = false; // skip the first time
      return;
    }
    let backgroundColor = attributes.backgroundColor ?? "";
    if (!backgroundColor) {
      backgroundColor = attributes.style?.color?.background ?? "";
    }
    let newHoverColor = "";
    const palette = (colorGradientSettings?.colors ?? []).reduce((acc, p) => {
      return [...acc, ...(p.colors ?? [])];
    }, []);
    const generatedColors = generateButtonColors(
      getColorFromPalette(backgroundColor, palette)
    );
    if (generatedColors?.hoverColor) {
      newHoverColor = generatedColors?.hoverColor;
    }
    setAttributes({
      style: {
        ...(attributes.style ?? {}),
        hoverBackgroundColor: newHoverColor,
      },
    });
  }, [
    attributes.style?.color?.background,
    attributes.backgroundColor,
    setAttributes,
    colorGradientSettings?.colors,
    blockName,
  ]);

  return el(
    Fragment,
    null,
    (hasHoverTextColor || hasHoverBackgroundColor) &&
      el(
        InspectorControls,
        { group: "color" },
        hasHoverTextColor &&
          el(ColorGradientSettingsDropdown, {
            settings: [
              {
                label: __("Hover color", "shadcn"),
                colorValue: hoverTextColor,
                clearable: true,
                onColorChange: (value) => {
                  setAttributes({
                    style: {
                      ...(attributes.style ?? {}),
                      hoverTextColor: value,
                    },
                  });
                },
                resetAllFilter: (prev) => {
                  prev.style.hoverTextColor = undefined;
                  return prev;
                },
              },
            ],
            panelId: clientId,
            hasColorsOrGradients: false,
            disableCustomColors: false,
            __experimentalIsRenderedInSidebar: true,
            ...colorGradientSettings,
          }),
        hasHoverBackgroundColor &&
          el(ColorGradientSettingsDropdown, {
            settings: [
              {
                label: __("Hover background color", "shadcn"),
                colorValue: hoverBackgroundColor,
                clearable: true,
                onColorChange: (value) => {
                  setAttributes({
                    style: {
                      ...(attributes.style ?? {}),
                      hoverBackgroundColor: value,
                    },
                  });
                },
                resetAllFilter: (prev) => {
                  prev.style.hoverBackgroundColor = undefined;
                  return prev;
                },
              },
            ],
            panelId: clientId,
            hasColorsOrGradients: false,
            disableCustomColors: false,
            __experimentalIsRenderedInSidebar: true,
            ...colorGradientSettings,
          })
      ),
    hasHoverBorder &&
      el(
        InspectorControls,
        { group: "border" },
        el(
          ToolsPanelItem,
          {
            panelId: clientId,
            hasValue: () => Object.keys(hoverBorder ?? {}).length > 0,
            label: __("Hover border", "shadcn"),
            onDeselect: () => {
              setAttributes({
                style: {
                  ...(attributes.style ?? {}),
                  hoverBorder: {},
                },
              });
            },
            resetAllFilter: (prev) => {
              prev.style.hoverBorder = {};
              return prev;
            },
            isShownByDefault: true,
          },
          el(BorderBoxControl, {
            colors: colorGradientSettings?.colors ?? [],
            panelId: clientId,
            value: hoverBorder,
            onChange: (v) => {
              setAttributes({
                style: {
                  ...(attributes.style ?? {}),
                  hoverBorder: v,
                },
              });
            },
            enableAlpha: true,
            popoverOffset: 40,
            popoverPlacement: "left-start",
            __experimentalIsRenderedInSidebar: true,
            size: "__unstable-large",
            label: __("Hover border", "shadcn"),
          })
        )
      )
  );
}

function hexToRgb(hex) {
  // Support both 6-digit (#RRGGBB) and 8-digit (#RRGGBBAA) hex colors
  const result6 = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  const result8 = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(
    hex
  );

  if (result8) {
    return {
      r: parseInt(result8[1], 16),
      g: parseInt(result8[2], 16),
      b: parseInt(result8[3], 16),
      a: parseInt(result8[4], 16) / 255, // Convert to 0-1 range
    };
  }

  if (result6) {
    return {
      r: parseInt(result6[1], 16),
      g: parseInt(result6[2], 16),
      b: parseInt(result6[3], 16),
    };
  }

  return null;
}

function getLuminance(hex) {
  const rgb = hexToRgb(hex);
  if (!rgb) return 0;

  const [r, g, b] = [rgb.r / 255, rgb.g / 255, rgb.b / 255].map((val) => {
    return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
  });

  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function darkenColor(hex, amount = 0.15) {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;

  const r = Math.max(0, Math.floor(rgb.r * (1 - amount)));
  const g = Math.max(0, Math.floor(rgb.g * (1 - amount)));
  const b = Math.max(0, Math.floor(rgb.b * (1 - amount)));

  // Preserve alpha channel if present
  const colorParts = [r, g, b].map((x) => x.toString(16).padStart(2, "0"));

  if (rgb.a !== undefined) {
    const alpha = Math.round(rgb.a * 255);
    colorParts.push(alpha.toString(16).padStart(2, "0"));
  }

  return `#${colorParts.join("")}`;
}

function lightenColor(hex, amount = 0.15) {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;

  const r = Math.min(255, Math.floor(rgb.r + (255 - rgb.r) * amount));
  const g = Math.min(255, Math.floor(rgb.g + (255 - rgb.g) * amount));
  const b = Math.min(255, Math.floor(rgb.b + (255 - rgb.b) * amount));

  // Preserve alpha channel if present
  const colorParts = [r, g, b].map((x) => x.toString(16).padStart(2, "0"));

  if (rgb.a !== undefined) {
    const alpha = Math.round(rgb.a * 255);
    colorParts.push(alpha.toString(16).padStart(2, "0"));
  }

  return `#${colorParts.join("")}`;
}

function getOptimalTextColor(bgColor) {
  const luminance = getLuminance(bgColor);
  return luminance > 0.5 ? "#000000" : "#ffffff";
}

function generateButtonColors(mainColor) {
  const luminance = getLuminance(mainColor);

  // If color is very dark (luminance < 0.2), lighten it for hover
  // Otherwise, darken it as usual
  const hoverColor =
    mainColor === "#000000"
      ? "#2f2f2f"
      : luminance < 0.2
      ? lightenColor(mainColor, 0.1)
      : darkenColor(mainColor, 0.12);

  const textColor = getOptimalTextColor(mainColor);

  const textHoverColor = getOptimalTextColor(hoverColor);

  return {
    mainColor,
    hoverColor,
    textColor,
    textHoverColor,
  };
}

function getColorFromPalette(color, palette = []) {
  return palette.find((c) => c.slug === color)?.color ?? color;
}

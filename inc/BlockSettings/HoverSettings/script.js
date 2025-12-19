/**
 * Hover Settings Block Extension
 *
 * Adds hover color and border settings to blocks that support them.
 */
(function () {
  "use strict";

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useEffect = wp.element.useEffect;
  var useRef = wp.element.useRef;
  var __ = wp.i18n.__;
  var ToolsPanelItem =
    wp.components.__experimentalToolsPanelItem || wp.components.ToolsPanelItem;
  var BorderBoxControl =
    wp.components.__experimentalBorderBoxControl ||
    wp.components.BorderBoxControl;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var ColorGradientSettingsDropdown =
    wp.blockEditor.__experimentalColorGradientSettingsDropdown ||
    wp.blockEditor.ColorGradientSettingsDropdown;
  var useMultipleOriginColorsAndGradients =
    wp.blockEditor.__experimentalUseMultipleOriginColorsAndGradients ||
    wp.blockEditor.useMultipleOriginColorsAndGradients;

  function isBlockSupportBackgroundColor(blockName) {
    var blockSupport = window.wp.blocks.getBlockSupport(blockName, "color");
    return (
      (blockSupport && blockSupport.background) ||
      (blockSupport &&
        blockSupport.__experimentalDefaultControls &&
        blockSupport.__experimentalDefaultControls.background)
    );
  }

  function isBlockSupportTextColor(blockName) {
    var blockSupport = window.wp.blocks.getBlockSupport(blockName, "color");
    return (
      (blockSupport && blockSupport.text) ||
      (blockSupport &&
        blockSupport.__experimentalDefaultControls &&
        blockSupport.__experimentalDefaultControls.text)
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

    var newAttributes = {};

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
  var addHoverSettingsControls = wp.compose.createHigherOrderComponent(
    function (BlockEdit) {
      return function (props) {
        var isSelected = props.isSelected;
        var blockName = props.name;
        var canAddSettings =
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

  var addHoverSettingsStyleToBlock = wp.compose.createHigherOrderComponent(
    function (BlockListBlock) {
      return function (props) {
        var attributes = props.attributes;
        var extraWrapperProps = props.wrapperProps || {};
        var styleAttrs = attributes.style || {};
        var hoverBackgroundColor = styleAttrs.hoverBackgroundColor;
        var hoverTextColor = styleAttrs.hoverTextColor;
        var hoverBorder = styleAttrs.hoverBorder;

        if (
          Object.keys(styleAttrs).indexOf("hoverBackgroundColor") !== -1 &&
          hoverBackgroundColor
        ) {
          extraWrapperProps.style = Object.assign({}, extraWrapperProps.style, {
            "--hover-background-color": hoverBackgroundColor,
          });
        }
        if (
          Object.keys(styleAttrs).indexOf("hoverTextColor") !== -1 &&
          hoverTextColor
        ) {
          extraWrapperProps.style = Object.assign({}, extraWrapperProps.style, {
            "--hover-color": hoverTextColor,
          });
        }

        if (
          Object.keys(styleAttrs).indexOf("hoverBorder") !== -1 &&
          hoverBorder
        ) {
          extraWrapperProps.style = Object.assign(
            {},
            extraWrapperProps.style,
            getBorderHoverStyleFromAttributes(attributes, props)
          );
        }

        return el(BlockListBlock, Object.assign({}, props, {
          wrapperProps: extraWrapperProps,
        }));
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
    var style = {};
    var styleAttrs = attributes.style || {};
    var hoverBorder = styleAttrs.hoverBorder || {};

    if (
      props.block &&
      ["core/group", "core/columns"].indexOf(props.block.name) !== -1 &&
      styleAttrs.border &&
      styleAttrs.border.radius
    ) {
      style["overflow"] = "hidden";
    }

    if (Object.keys(hoverBorder).length > 0) {
      if (Object.keys(hoverBorder).indexOf("top") !== -1) {
        ["top", "bottom", "left", "right"].forEach(function (aspect) {
          if (hoverBorder[aspect]) {
            style["--hover-border-" + aspect + "-c"] = hoverBorder[aspect].color;
            style["--hover-border-" + aspect + "-w"] = hoverBorder[aspect].width;
            style["--hover-border-" + aspect + "-s"] = hoverBorder[aspect].style;
          }
        });
      } else {
        style["--hover-border-c"] = hoverBorder.color;
        style["--hover-border-w"] = hoverBorder.width;
        style["--hover-border-s"] = hoverBorder.style;
      }
    }

    return style;
  }

  function Settings(props) {
    var isFirstRender = useRef(true);
    var colorGradientSettings = useMultipleOriginColorsAndGradients();
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var blockName = props.name;
    var clientId = props.clientId;

    var styleAttrs = attributes.style || {};
    var hoverBackgroundColor = styleAttrs.hoverBackgroundColor || "";
    var hoverTextColor = styleAttrs.hoverTextColor || "";
    var hoverBorder = styleAttrs.hoverBorder || {};
    var hasHoverTextColor = isBlockSupportTextColor(blockName) || false;
    var hasHoverBackgroundColor =
      isBlockSupportBackgroundColor(blockName) || false;
    var hasHoverBorder = isBlockSupportBorder(blockName) || false;

    useEffect(
      function () {
        var isSupportedGenerateColorBlocks = [
          "core/button",
          "woocommerce/product-button",
          "shadcn/button",
        ];
        if (isSupportedGenerateColorBlocks.indexOf(blockName) === -1) {
          return;
        }
        if (isFirstRender.current) {
          isFirstRender.current = false;
          return;
        }
        var backgroundColor = attributes.backgroundColor || "";
        if (!backgroundColor) {
          backgroundColor =
            (styleAttrs.color && styleAttrs.color.background) || "";
        }
        var newHoverColor = "";
        var colors = (colorGradientSettings && colorGradientSettings.colors) || [];
        var palette = colors.reduce(function (acc, p) {
          return acc.concat(p.colors || []);
        }, []);
        var generatedColors = generateButtonColors(
          getColorFromPalette(backgroundColor, palette)
        );
        if (generatedColors && generatedColors.hoverColor) {
          newHoverColor = generatedColors.hoverColor;
        }
        setAttributes({
          style: Object.assign({}, styleAttrs, {
            hoverBackgroundColor: newHoverColor,
          }),
        });
      },
      [
        styleAttrs.color && styleAttrs.color.background,
        attributes.backgroundColor,
        setAttributes,
        colorGradientSettings && colorGradientSettings.colors,
        blockName,
      ]
    );

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
                  onColorChange: function (value) {
                    setAttributes({
                      style: Object.assign({}, styleAttrs, {
                        hoverTextColor: value,
                      }),
                    });
                  },
                  resetAllFilter: function (prev) {
                    prev.style.hoverTextColor = undefined;
                    return prev;
                  },
                },
              ],
              panelId: clientId,
              hasColorsOrGradients: false,
              disableCustomColors: false,
              __experimentalIsRenderedInSidebar: true,
              colors: colorGradientSettings.colors,
              gradients: colorGradientSettings.gradients,
            }),
          hasHoverBackgroundColor &&
            el(ColorGradientSettingsDropdown, {
              settings: [
                {
                  label: __("Hover background color", "shadcn"),
                  colorValue: hoverBackgroundColor,
                  clearable: true,
                  onColorChange: function (value) {
                    setAttributes({
                      style: Object.assign({}, styleAttrs, {
                        hoverBackgroundColor: value,
                      }),
                    });
                  },
                  resetAllFilter: function (prev) {
                    prev.style.hoverBackgroundColor = undefined;
                    return prev;
                  },
                },
              ],
              panelId: clientId,
              hasColorsOrGradients: false,
              disableCustomColors: false,
              __experimentalIsRenderedInSidebar: true,
              colors: colorGradientSettings.colors,
              gradients: colorGradientSettings.gradients,
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
              hasValue: function () {
                return Object.keys(hoverBorder).length > 0;
              },
              label: __("Hover border", "shadcn"),
              onDeselect: function () {
                setAttributes({
                  style: Object.assign({}, styleAttrs, {
                    hoverBorder: {},
                  }),
                });
              },
              resetAllFilter: function (prev) {
                prev.style.hoverBorder = {};
                return prev;
              },
              isShownByDefault: true,
            },
            el(BorderBoxControl, {
              colors: (colorGradientSettings && colorGradientSettings.colors) || [],
              panelId: clientId,
              value: hoverBorder,
              onChange: function (v) {
                setAttributes({
                  style: Object.assign({}, styleAttrs, {
                    hoverBorder: v,
                  }),
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
    var result6 = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    var result8 = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(
      hex
    );

    if (result8) {
      return {
        r: parseInt(result8[1], 16),
        g: parseInt(result8[2], 16),
        b: parseInt(result8[3], 16),
        a: parseInt(result8[4], 16) / 255,
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
    var rgb = hexToRgb(hex);
    if (!rgb) return 0;

    var values = [rgb.r / 255, rgb.g / 255, rgb.b / 255].map(function (val) {
      return val <= 0.03928
        ? val / 12.92
        : Math.pow((val + 0.055) / 1.055, 2.4);
    });

    return 0.2126 * values[0] + 0.7152 * values[1] + 0.0722 * values[2];
  }

  function darkenColor(hex, amount) {
    if (amount === undefined) amount = 0.15;
    var rgb = hexToRgb(hex);
    if (!rgb) return hex;

    var r = Math.max(0, Math.floor(rgb.r * (1 - amount)));
    var g = Math.max(0, Math.floor(rgb.g * (1 - amount)));
    var b = Math.max(0, Math.floor(rgb.b * (1 - amount)));

    var colorParts = [r, g, b].map(function (x) {
      return x.toString(16).padStart(2, "0");
    });

    if (rgb.a !== undefined) {
      var alpha = Math.round(rgb.a * 255);
      colorParts.push(alpha.toString(16).padStart(2, "0"));
    }

    return "#" + colorParts.join("");
  }

  function lightenColor(hex, amount) {
    if (amount === undefined) amount = 0.15;
    var rgb = hexToRgb(hex);
    if (!rgb) return hex;

    var r = Math.min(255, Math.floor(rgb.r + (255 - rgb.r) * amount));
    var g = Math.min(255, Math.floor(rgb.g + (255 - rgb.g) * amount));
    var b = Math.min(255, Math.floor(rgb.b + (255 - rgb.b) * amount));

    var colorParts = [r, g, b].map(function (x) {
      return x.toString(16).padStart(2, "0");
    });

    if (rgb.a !== undefined) {
      var alpha = Math.round(rgb.a * 255);
      colorParts.push(alpha.toString(16).padStart(2, "0"));
    }

    return "#" + colorParts.join("");
  }

  function getOptimalTextColor(bgColor) {
    var luminance = getLuminance(bgColor);
    return luminance > 0.5 ? "#000000" : "#ffffff";
  }

  function generateButtonColors(mainColor) {
    var luminance = getLuminance(mainColor);

    var hoverColor =
      mainColor === "#000000"
        ? "#2f2f2f"
        : luminance < 0.2
        ? lightenColor(mainColor, 0.1)
        : darkenColor(mainColor, 0.12);

    var textColor = getOptimalTextColor(mainColor);
    var textHoverColor = getOptimalTextColor(hoverColor);

    return {
      mainColor: mainColor,
      hoverColor: hoverColor,
      textColor: textColor,
      textHoverColor: textHoverColor,
    };
  }

  function getColorFromPalette(color, palette) {
    if (!palette) palette = [];
    var found = palette.find(function (c) {
      return c.slug === color;
    });
    return found ? found.color : color;
  }
})();

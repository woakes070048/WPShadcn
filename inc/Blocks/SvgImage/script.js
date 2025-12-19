/**
 * SVG Image Block Variation
 *
 * Registers an "SVG Image" variation of core/image that renders inline SVG.
 */
(function () {
  "use strict";

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var __ = wp.i18n.__;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var PanelBody = wp.components.PanelBody;
  var TextareaControl = wp.components.TextareaControl;
  var ColorPalette = wp.components.ColorPalette;
  var BaseControl = wp.components.BaseControl;
  var Button = wp.components.Button;
  var useSetting = wp.blockEditor.useSetting;
  var UnitControl =
    wp.components.__experimentalUnitControl || wp.components.UnitControl;

  /**
   * Register svgCode attribute on core/image block.
   */
  function addSvgCodeAttribute(settings) {
    if (settings.name !== "core/image") {
      return settings;
    }

    settings.attributes = Object.assign({}, settings.attributes, {
      svgCode: {
        type: "string",
        default: "",
      },
      svgColor: {
        type: "string",
        default: "",
      },
      svgSize: {
        type: "string",
        default: "",
      },
    });

    return settings;
  }

  wp.hooks.addFilter(
    "blocks.registerBlockType",
    "shadcn/svg-image-attribute",
    addSvgCodeAttribute
  );

  /**
   * Register SVG Image block variation.
   */
  wp.domReady(function () {
    wp.blocks.registerBlockVariation("core/image", {
      name: "svg-image",
      title: __("SVG Image", "shadcn"),
      description: __("Paste SVG code to render inline SVG", "shadcn"),
      icon: "code-standards",
      attributes: {
        svgCode: "",
        svgColor: "",
        svgSize: "",
      },
      isActive: function (blockAttributes) {
        return !!blockAttributes.svgCode;
      },
    });
  });

  /**
   * Basic SVG sanitization for editor preview.
   * Removes script tags and event handlers.
   */
  function sanitizeSvgForPreview(svg) {
    if (!svg || typeof svg !== "string") {
      return "";
    }
    if (svg.indexOf("<svg") === -1) {
      return "";
    }
    // Remove script tags and event handlers
    return svg
      .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, "")
      .replace(/\s+on\w+\s*=\s*["'][^"']*["']/gi, "")
      .replace(/\s+on\w+\s*=\s*[^\s>]+/gi, "");
  }

  /**
   * Replace BlockEdit to show SVG preview when svgCode is set.
   */
  var SvgImageEdit = wp.compose.createHigherOrderComponent(
    function (BlockEdit) {
      return function (props) {
        if (props.name !== "core/image") {
          return el(BlockEdit, props);
        }

        var attributes = props.attributes;
        var setAttributes = props.setAttributes;
        var isSelected = props.isSelected;
        var svgCode = attributes.svgCode || "";
        var svgColor = attributes.svgColor || "";
        var svgSize = attributes.svgSize || "";
        var sanitizedSvg = sanitizeSvgForPreview(svgCode);
        var hasSvg = !!sanitizedSvg;

        // Hooks must be called unconditionally
        var themeColors = useSetting("color.palette") || [];

        var wrapperStyle = {};
        if (hasSvg && svgColor) {
          wrapperStyle.color = svgColor;
        }
        if (hasSvg && svgSize) {
          wrapperStyle.width = svgSize;
        }

        var blockProps = useBlockProps({
          className: hasSvg ? "shadcn-svg-preview" : "",
          style: wrapperStyle,
        });

        // Build inspector panels as variables to avoid conditional hook issues
        var svgEditPanel = el(
          PanelBody,
          {
            title: __("SVG Image", "shadcn"),
            initialOpen: hasSvg,
          },
          el(TextareaControl, {
            label: __("SVG Code", "shadcn"),
            help: hasSvg
              ? __("Edit or clear the SVG code.", "shadcn")
              : __("Paste SVG code here to render inline SVG instead of image.", "shadcn"),
            value: svgCode,
            onChange: function (value) {
              setAttributes({ svgCode: value });
            },
            rows: hasSvg ? 8 : 6,
          }),
          hasSvg &&
            el(
              Button,
              {
                variant: "secondary",
                isDestructive: true,
                style: { marginTop: "8px" },
                onClick: function () {
                  setAttributes({ svgCode: "", svgColor: "" });
                },
              },
              __("Clear SVG", "shadcn")
            )
        );

        var settingsPanel = hasSvg
          ? el(
              PanelBody,
              {
                title: __("SVG Settings", "shadcn"),
                initialOpen: true,
              },
              el(
                BaseControl,
                {
                  label: __("Size", "shadcn"),
                  __nextHasNoMarginBottom: true,
                },
                el(UnitControl, {
                  value: svgSize,
                  onChange: function (value) {
                    setAttributes({ svgSize: value || "" });
                  },
                  units: [
                    { value: "px", label: "px", default: 100 },
                    { value: "%", label: "%", default: 50 },
                    { value: "em", label: "em", default: 10 },
                    { value: "rem", label: "rem", default: 10 },
                    { value: "vw", label: "vw", default: 10 },
                  ],
                  __nextHasNoMarginBottom: true,
                })
              ),
              el(
                BaseControl,
                {
                  label: __("Fill Color", "shadcn"),
                  help: __("Sets color for SVG elements using currentColor.", "shadcn"),
                  __nextHasNoMarginBottom: true,
                },
                el(ColorPalette, {
                  colors: themeColors,
                  value: svgColor,
                  onChange: function (value) {
                    setAttributes({ svgColor: value || "" });
                  },
                  clearable: true,
                })
              )
            )
          : null;

        var inspectorControls =
          isSelected || hasSvg
            ? el(InspectorControls, null, svgEditPanel, settingsPanel)
            : null;

        // If SVG code exists, show SVG preview
        if (hasSvg) {
          return el(
            Fragment,
            null,
            el(
              "figure",
              blockProps,
              el("div", {
                className: "shadcn-svg-preview__content",
                dangerouslySetInnerHTML: { __html: sanitizedSvg },
              })
            ),
            inspectorControls
          );
        }

        // No SVG code - show normal image block
        return el(Fragment, null, el(BlockEdit, props), inspectorControls);
      };
    },
    "SvgImageEdit"
  );

  wp.hooks.addFilter(
    "editor.BlockEdit",
    "shadcn/svg-image-edit",
    SvgImageEdit
  );
})();

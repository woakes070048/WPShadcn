/**
 * Button Size Block Extension
 *
 * Adds size controls (sm, md, lg) to button blocks.
 */
(function () {
  "use strict";

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var __ = wp.i18n.__;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var ToolsPanel =
    wp.components.__experimentalToolsPanel || wp.components.ToolsPanel;
  var ToolsPanelItem =
    wp.components.__experimentalToolsPanelItem || wp.components.ToolsPanelItem;
  var ToggleGroupControl =
    wp.components.__experimentalToggleGroupControl ||
    wp.components.ToggleGroupControl;
  var ToggleGroupControlOption =
    wp.components.__experimentalToggleGroupControlOption ||
    wp.components.ToggleGroupControlOption;

  function isButtonBlock(name) {
    return ["core/button"].indexOf(name) !== -1;
  }

  function isRestrictedButtonBlock(attributes) {
    return false;
  }

  /** Register attribute */
  function addButtonSizeAttributes(settings) {
    if (typeof settings.attributes === "undefined") {
      return settings;
    }
    if (!isButtonBlock(settings.name)) {
      return settings;
    }
    settings.attributes = Object.assign(settings.attributes, {
      size: {
        type: "string",
        default: "",
      },
    });

    return settings;
  }

  wp.hooks.addFilter(
    "blocks.registerBlockType",
    "shadcn-blocks/button-size-attribute",
    addButtonSizeAttributes
  );

  /** Display controls */
  var ButtonSizeControls = wp.compose.createHigherOrderComponent(
    function (BlockEdit) {
      return function (props) {
        var isSelected = props.isSelected;
        var attributes = props.attributes;
        var setAttributes = props.setAttributes;
        var name = props.name;
        var canAddSettings =
          isButtonBlock(name) && !isRestrictedButtonBlock(attributes);

        return el(
          Fragment,
          null,
          el(BlockEdit, props),
          isSelected &&
            canAddSettings &&
            el(
              InspectorControls,
              null,
              el(
                ToolsPanel,
                {
                  label: __("Size settings", "shadcn"),
                  resetAll: function () {
                    setAttributes({ size: undefined });
                  },
                },
                el(
                  ToolsPanelItem,
                  {
                    label: __("Size", "shadcn"),
                    isShownByDefault: true,
                    hasValue: function () {
                      return !!attributes.size;
                    },
                    onDeselect: function () {
                      setAttributes({ size: undefined });
                    },
                    __nextHasNoMarginBottom: true,
                  },
                  el(
                    ToggleGroupControl,
                    {
                      value: attributes.size,
                      isBlock: true,
                      __next40pxDefaultSize: true,
                      __nextHasNoMarginBottom: true,
                      onChange: function (value) {
                        setAttributes({ size: value });
                      },
                    },
                    el(ToggleGroupControlOption, {
                      value: "sm",
                      label: __("Small", "shadcn"),
                    }),
                    el(ToggleGroupControlOption, {
                      value: "md",
                      label: __("Medium", "shadcn"),
                    }),
                    el(ToggleGroupControlOption, {
                      value: "lg",
                      label: __("Large", "shadcn"),
                    })
                  )
                )
              )
            )
        );
      };
    },
    "ButtonSizeControls"
  );

  wp.hooks.addFilter(
    "editor.BlockEdit",
    "shadcn-blocks/button-size-controls",
    ButtonSizeControls,
    1
  );

  var addButtonSizeStyleToBlock = wp.compose.createHigherOrderComponent(
    function (BlockListBlock) {
      return function (props) {
        var attributes = props.attributes;
        var name = props.name;
        var wrapperProps = props.wrapperProps || {};
        var extraWrapperProps = Object.assign({}, wrapperProps);

        if (
          !isRestrictedButtonBlock(attributes) &&
          isButtonBlock(name) &&
          attributes.size != null &&
          attributes.size !== ""
        ) {
          extraWrapperProps.className =
            (wrapperProps.className || "") + " is-size-" + attributes.size;
        }

        return el(
          BlockListBlock,
          Object.assign({}, props, { wrapperProps: extraWrapperProps })
        );
      };
    },
    "addButtonSizeStyleToBlock"
  );

  wp.hooks.addFilter(
    "editor.BlockListBlock",
    "shadcn/button-size-style",
    addButtonSizeStyleToBlock,
    1
  );

  /**
   * Save function
   */
  function addButtonSizeProps(props, blockType, attributes) {
    if (
      !isButtonBlock(blockType.name || "") ||
      isRestrictedButtonBlock(attributes)
    ) {
      return props;
    }

    if (attributes.size != null && attributes.size !== "") {
      return Object.assign({}, props, {
        className: (props.className || "") + " is-size-" + attributes.size,
      });
    }

    return props;
  }

  wp.hooks.addFilter(
    "blocks.getSaveContent.extraProps",
    "shadcn-blocks/button-size-props",
    addButtonSizeProps
  );
})();

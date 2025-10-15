# Developer Notes & Documentation Links

This file contains quick links to official documentation for the core technologies used in this project. Use these links to verify functions, hooks, and best practices before committing code.

## Core Technologies

- **WordPress:**
  - [Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
  - [Code Reference (Functions, Hooks, Classes)](https://developer.wordpress.org/reference/)
  - [Hook Reference](https://developer.wordpress.org/reference/hooks/)

- **WooCommerce:**
  - [WooCommerce Developer Resources](https://developer.woocommerce.com/)
  - [Action and Filter Hook Reference](https://woocommerce.github.io/code-reference/hooks/hooks.html)

- **Advanced Custom Fields (ACF):**
  - [ACF Documentation](https://www.advancedcustomfields.com/resources/)
  - [Function Reference](https://www.advancedcustomfields.com/resources/functions/)
  - [Action and Filter Reference](https://www.advancedcustomfields.com/resources/actions-and-filters/)

## Other Key Plugins & Theme

- **Elementor (Free & Pro):**
  - [Developer Documentation Home](https://developers.elementor.com/)
  - [PHP Hooks Reference](https://developers.elementor.com/docs/hooks/)
  - [JavaScript Hooks Reference](https://developers.elementor.com/docs/hooks/js-hooks/)

- **Astra Theme (Free):**
  - [Astra Developer Documentation](https://wpastra.com/docs/documentation-for-developers/)
  - [Visual Hook Guide](https://wpastra.com/docs/visual-guide-to-astra-hooks/)

- **LiteSpeed Cache:**
  - While a public, hook-based developer API is not a primary feature, LiteSpeed provides a comprehensive guide on its ESI (Edge Side Includes) functionality for developers.
  - [Developer's Guide to ESI](https://docs.litespeedtech.com/lscache/lscwp/esi/)

- **Wordfence (Free):**
  - The free version of Wordfence is not designed with extensive third-party developer extension in mind and does not maintain a public hook/filter reference. Integration is typically done via their premium version or specific internal APIs.

## Granular Styling with CSS Classes

To allow for maximum design flexibility, the plugin supports per-field styling that overrides the global styles set on the plugin's main settings page. This is achieved by adding custom CSS classes to ACF fields.

### Workflow

1.  **Define a Global Style in Elementor (Optional but Recommended):**
    *   Go to `Elementor -> Site Settings -> Global Colors / Global Typography`.
    *   Create the new style you want to use, for example, a new color called "Special Heading Color".

2.  **Add a Custom Class in ACF:**
    *   When editing an ACF Field Group, select the field you want to style differently.
    *   Go to the **Presentation** tab for that field.
    *   Find the **Wrapper Attributes** (`Omslagsattribut`) setting.
    *   In the left input, write `class`.
    *   In the right input, write your custom class name, for example, `special-heading`.

3.  **Inform the Plugin:**
    *   The final step will be to map this class to the desired Elementor Global Style on the plugin's settings page. This ensures the system remains portable and doesn't require manual CSS editing.

### CSS Logic (for reference)

The system uses CSS specificity to apply the granular styles. A rule targeting a custom class will always override a general rule.

**To style a field's label (title) differently:**

```css
/* This rule targets the label of any field with the .special-heading class */
.acf-field.special-heading .acf-label label {
    color: var(--e-global-color-of-your-choice);
}
```

**To style the options within that same field differently:**

```css
/* This rule targets the radio button labels inside the .special-heading field */
.acf-field.special-heading .acf-radio-list label {
    color: var(--e-global-color-for-the-options);
}
```

This method provides a powerful, clean, and maintainable way to handle exceptions to the global styling rules.
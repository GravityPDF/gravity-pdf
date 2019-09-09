/**
 * Replace some of Gravity Forms JS variables so it functions correctly with our PDF version
 *
 * @since 4.1
 */
export function setupGravityForms () {
  /**
   * Check if the global gf_vars has been set and if so replace the .thisFormButton, .show, .hide objects with our
   * customised options.
   * @since 4.0
   */
  if (typeof gf_vars !== 'undefined') {
    gf_vars.thisFormButton = GFPDF.conditionalText
    gf_vars.show = GFPDF.enable
    gf_vars.hide = GFPDF.disable
  }
}

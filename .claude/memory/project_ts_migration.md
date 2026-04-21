---
name: TypeScript migration — all three JS bundles
description: All JS source files across react/, legacy/, and admin/ have been converted to TypeScript on the modern-restructuring branch
type: project
---

All three webpack bundles are now fully TypeScript. 345/345 Jest tests pass, `tsc --noEmit` is clean, and all E2E tests (87/87) pass.

**Why:** Modernisation effort — consistent type safety across the entire frontend.

**How to apply:** TypeScript compilation is via Babel (not tsc), so `yarn dev:build` uses Babel and `npx tsc --noEmit` is the type-check step.

## React bundle (src/assets/js/react/)
- 79 source files + ~55 Jest test files converted to .ts/.tsx
- Key types: `src/assets/js/react/types/global.d.ts` (GFPDFGlobal + window), `src/assets/js/react/types/index.ts` (FontItem, TemplateItem, etc.)
- Typed Redux hooks: `src/assets/js/react/store/hooks.ts`

## Legacy bundle (src/assets/js/legacy/)
- 1 file: `gfpdf-entries.ts` — IIFE with `$: JQueryStatic` parameter type

## Admin bundle (src/assets/js/admin/)
- 31 files converted to .ts
- Global declarations for WP/GF/TinyMCE: `src/assets/js/admin/types/globals.d.ts`
  - `tinyMCE`, `wp.media`, `gform.addFilter`, `QTags`, `switchEditors`, `getUserSetting`
  - `ConditionalLogic`, `GetFirstRuleField`, `GetRuleValuesDropDown`, `ToggleConditionalLogic`
  - `window.gfpdf_current_pdf`, `window.gfpdf_extra_conditional_logic_options`
  - jQuery augmentations: `wpColorPicker`, `toJSON`
- GFPDFGlobal extended with admin-only strings (letsGoCreateOne, pdfDeleteWarning, etc.)
- No Jest tests added — admin/legacy covered by Playwright E2E only
- NOT added to Jest collectCoverageFrom (would break 75% threshold)

## Config files changed
- `tsconfig.json` — includes react/, legacy/, admin/, and jest test files
- `webpack.config.js` — all three entries now point to .ts files
- `jest.config.js` — handles .ts/.tsx transforms (unchanged from React migration)

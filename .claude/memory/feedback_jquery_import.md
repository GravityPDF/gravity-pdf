---
name: jQuery usage in admin/legacy JS files
description: Use the global jQuery variable, not import $ from 'jquery', in admin and legacy JS/TS files
type: feedback
---

Do not use `import $ from 'jquery'` in admin or legacy JS/TS files. Instead use one of:
- `const $ = jQuery;` at the top of each module that needs `$`
- Pass `jQuery` into a function/IIFE: `(function($: JQueryStatic) { ... })(jQuery)`

**Why:** WordPress provides jQuery as a global and uses `jQuery.noConflict()`. The `import $ from 'jquery'` pattern relies on webpack's external mapping and can cause `$ is not a function` runtime errors when the module context doesn't expose `$`. Using the global `jQuery` directly is explicit and safe.

**How to apply:** When converting admin/ or legacy/ JS to TypeScript, replace any `import $ from 'jquery'` with `const $ = jQuery;` (for module files) or the IIFE/function-wrapping approach. The `gfpdf-entries.ts` IIFE pattern is already correct.

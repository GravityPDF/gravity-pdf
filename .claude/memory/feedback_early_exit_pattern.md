---
name: Early exit / guard clause pattern
description: User prefers early exit for bad paths rather than wrapping happy path in a conditional
type: feedback
---

Use the early exit / guard clause pattern: test for the bad/skip condition first and return early, rather than wrapping the happy path in an `if` block.

**Why:** Cleaner control flow — the happy path runs at the base indentation level and there's no trailing closing brace to reason about.

**How to apply:** In any function or `useEffect` that has a conditional guard, invert the condition and return early. Examples:

```typescript
// Avoid:
const handleRetry = () => {
    if (retry.length > 0) {
        setIsLoading(true);
        void startDownloadFonts(retry);
    }
};

// Prefer:
const handleRetry = () => {
    if (retry.length === 0) return;
    setIsLoading(true);
    void startDownloadFonts(retry);
};
```

```typescript
// Avoid:
useEffect(() => {
    if (requestDownload === 'finished') {
        resetState();
    }
}, [requestDownload]);

// Prefer:
useEffect(() => {
    if (requestDownload !== 'finished') return;
    resetState();
}, [requestDownload]);
```

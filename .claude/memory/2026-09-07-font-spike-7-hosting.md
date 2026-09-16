---
name: font-spike-7-hosting
description: Phase 0 spike 7 — font file hosting decided as R2 on a public custom domain; GitHub releases ruled out on mechanism and AUP, Worker binding on caching
metadata:
  type: project
---

Phase 0 spike 7 of `.claude/plans/2026-08-26-remove-core-font-installer.md`, assessed 2026-09-07. Store recorded in
§9.24 and §4.3 Hosting. Follows [[font-spike-6-pack-split]], whose CJK split changed the asset size range.
**The assessment half is done; the exit is not met** — the staging deploy and smoke suite are Worker-repo work.

## Decision

**R2, with `files/` served from a public R2 custom domain declared as `files_base`** — not Worker-proxied GitHub
release assets, and not read through a Worker R2 binding either. The signed root, `sources/` and `entries/` stay on
the Worker (small, signed, short max-age); only the immutable blobs move.

## GitHub was foreclosed on mechanism, before cost mattered

A release-asset URL does not serve bytes — it 302s to a **time-limited signed URL**. Measured against a real asset:

```
$ curl -sI https://github.com/GravityPDF/gravity-pdf/releases/download/6.17/gravity-pdf-6.17.0.zip
302 → https://release-assets.githubusercontent.com/...?se=<+1h>&sig=…&jwt=…
```

(the JWT's path claim names `releaseassetproduction.blob.core.windows.net`, so the real backing store is Azure Blob).

That breaks two of the plan's own invariants:

1. `Font_Downloader::fetch()` sets **`redirection => 0`** on every hash-addressed fetch, and the inline batch sets
   `follow_redirects => false` — deliberate, so the pinned origin is the only host contacted. A redirecting origin
   fails *every* font download, not just the inline ones.
2. A signed, hour-expiring URL cannot be published in an immutable index, so even the `files_base` escape hatch
   cannot point at GitHub.

So option B collapses to the Worker *proxying* the bytes — at which point the Worker carries the full egress anyway
and GitHub is only origin storage behind an extra hop, with none of R2's advantages.

**The AUP is the second reason.** GitHub's docs say a release has "no limit on the total size … nor bandwidth
usage", but the Acceptable Use Policy reserves the right to "suspend your Account, throttle your file hosting, or
otherwise limit your activity" where bandwidth is "significantly excessive in relation to other users", and to
delete repositories placing "undue strain" on their infrastructure. Allowed by the letter, discretionary and
revocable in fact — not a base for a paid product's font delivery.

## R2 is free at this scale

Store size, computed from the fork packages plus the Google estimate:

| | |
|---|---|
| 15 coverage packs | 99.34 MB |
| 4 popular packs | 13.65 MB |
| `serif-mono` (google/fonts, spike 8) | ~1.5 MB |
| Google half, ~7,000 files at the popular packs' 310 KB average face | ~2.2 GB |
| **total** | **~2.3 GB** |

Against 2026 R2 pricing: storage $0.015/GB-month with a **10 GB-month free tier**; Class B (reads) $0.36/million
with a **10M/month free tier**; **egress $0** — "Egressing directly from R2, including via the Workers API, S3 API,
and r2.dev domains does not incur data transfer (egress) charges." Even a million installs a month at three files
each is 3M Class B reads. Both axes sit inside the free tier, so hosting cost is effectively zero and *egress
volume is not a variable worth designing around*.

## Why the custom domain rather than the Worker binding

**R2 read through a Worker binding is not automatically edge-cached.** Cloudflare's Workers Cache docs are explicit
that the Cache API is a programmatic interface a Worker must call itself; to have Cloudflare return a response
without executing the Worker you need Workers Caching. So a binding-based `files/` re-executes the Worker and
re-reads R2 on every download unless we write and maintain `cache.put()`/`cache.match()` with a cache-key
discipline. A **public R2 custom domain** gets standard CDN caching automatically.

Second reason: a 17.63 MB `Sun-ExtB.ttf` never enters a Worker isolate. Workers enforce no response-body size limit,
but the isolate memory ceiling is **128 MB on both Free and Paid**, and the docs push `TransformStream` /
`ReadableStream` passthrough precisely to avoid buffering. Off the Worker, that discipline stops being ours to get
right. (Streaming would not have cost CPU time — network I/O does not count against it, Paid allows 30 s default —
so this was about memory and correctness, not CPU.)

Cloudflare caches objects up to **512 MB on Free/Pro/Business** (5 GB Enterprise), so every pack is comfortably
cacheable at the edge.

## Consequences written into the plan

- **`files_base` must be filterable by `gfpdf_font_download_base_url`**, like the root. Otherwise a mirror serves
  the indexes and still sends every site to our R2 domain for the bytes — the one thing a mirror exists to avoid —
  and the E2E stub could not intercept a download at all. Pinned by a test that a filtered base changes both an
  index URL and a `files/` URL.
- **Two headers stop being Worker code.** On the custom domain, `Cache-Control: public, max-age=31536000, immutable`
  is per-object metadata written at upload (`wrangler r2 object put --cache-control`, so the pipeline owns it and a
  re-upload is the only way to change it), and `ACAO: *` — needed because the admin browser loads preview woff2
  cross-origin — is a bucket CORS policy. Both go in the Worker plan's task inventory, and the smoke suite must
  assert them on a real object, not on a Worker response. The plugin cannot compensate for either.

## Still open (exit not met)

The staging deploy and the Worker plan's smoke suite against it. The largest streamed download in that suite is now
**17.63 MB (`Sun-ExtB.ttf`)**, not the 23 MB the brief assumed — Sun-ExtA is gone with the spike-5 swaps.

## Sources

- https://developers.cloudflare.com/r2/pricing/ · https://developers.cloudflare.com/r2/platform/limits/
- https://developers.cloudflare.com/workers/platform/limits/ · https://developers.cloudflare.com/workers/runtime-apis/cache/
- https://developers.cloudflare.com/r2/buckets/public-buckets/ · https://developers.cloudflare.com/cache/concepts/default-cache-behavior/
- https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases
- https://docs.github.com/en/site-policy/acceptable-use-policies/github-acceptable-use-policies

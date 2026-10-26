---
layout: ""
permalink: false
---

# CLAUDE.md - AI Assistant Guide for Chobble Client

## Project Overview

**Chobble Client** is a content repository that merges with the [Chobble Template](https://git.chobble.com/chobble/chobble-template) at build time to produce a static website. It uses **Bun** as the package manager and runtime.

### Architecture

This project separates content from template:

- **This repo** (`chobble-client`): Site content, custom styles, build scripts
- **Chobble Template**: Eleventy SSG, themes, components, collections

At build time, GitHub Actions merges both repos via sparse-checkout, then runs Eleventy.

---

## Quick Reference

### Essential Commands

```bash
bun install          # Install dependencies (MUST use bun, not npm)
bun run build        # Build the site
bun run serve        # Development server with hot reload
bun run test         # Run tests
bun run lint         # Check code with Biome
bun run lint:fix     # Auto-fix lint issues
bun run cpd          # jscpd over scripts, includes, layouts, CSS and content
bun run cpd:ratchet  # Fails if the jscpd minTokens threshold could be lowered
bun run check:copy   # Flags sentences repeated word-for-word across pages
bun run grade        # Page grader: voice, meta, links, business invariants
```

### Development environment

The shell is [devenv](https://devenv.sh): `devenv shell` (or `direnv allow`
with a `use devenv` `.envrc`) provides bun, git and vips plus one PATH command
per package.json script. `devenv.yaml`/`devenv.nix`/`devenv.lock` are
committed; `.devenv/` and `.bin/` (the jscpd binary `scripts/jscpd.js`
installs) are not.

### Page grader

`bun scripts/grade-pages.js` grades every gradeable page (mechanical checks
always; the Jev LLM checks when `OPENCODE_API_KEY` or
`/run/secrets/opencode_api_key` is available). Mechanical checks pin the
business invariants: only phone `01428 751 745` may appear in copy, no
private payment detail, location map embeds must show the right town, no
template/model leakage, no paste cruft. Useful flags: `--no-jev`
(mechanical only — what the pre-commit hook runs on staged pages),
`--fail-on-critical` (batch exit code), `--gate` (single page: exit 1 unless
no FAILs, score >= 85, EEAT >= 70; fails closed without Jev), `--list-checks`.

### Copy-paste detection

`.jscpd.json` scans maintained code (`scripts/`, `_includes/`, `_data/`,
`css/`) and the markdown content dirs (`service-pages/`, `pages/`,
`reviews/`) at `minTokens: 39` (the ratchet floor — `bun run cpd:ratchet`
fails if the threshold could be lowered). The WordPress-export layers are
fenced out the same way CLAUDE.md fences the legacy `<style>` blocks:
`_includes/head.html`, `_includes/site-header.html`, `_layouts/`,
`locations/` (their WP gallery markup — the prose is guarded by check:copy)
and the one-off/upstream-synced scripts (`migrate-*.js`,
`clean-descriptions.js`, `fetch-*.js`). jscpd never sees frontmatter prose
blocks — that is `bun run check:copy`'s job, which reads the prose on
`locations/`, `service-pages/`, `pages/` and the root pages and fails on any
sentence repeated word-for-word across pages (`--all` also covers
`client-testimonials.md`). The pre-commit hook (`.githooks/pre-commit`,
installed by `bun install`) runs check:copy, the mechanical grader on staged
pages, cpd and the ratchet.

### Directory Structure

```
chobble-client/
├── scripts/         # Build utilities and tooling
├── _data/           # Site configuration (site.json, meta.json)
├── pages/           # Content pages (markdown)
├── css/             # Custom stylesheets
├── images/          # Site images
├── .pages.yml       # CMS configuration
├── biome.json       # Linting config (extends js-toolkit base)
├── bunfig.toml      # Bun test configuration
└── .jscpd.json      # Copy-paste detection config
```

---

## Styling / CSS

**No inline CSS.** Never add `<style>` blocks or `style="..."` attributes in layouts, includes, or content. All styles live in SCSS that compiles into the design-system bundle.

- **Where styles go:**
  - `css/theme.scss` — design-system `:root` token overrides (brand colours, fonts, font-size re-basing, radius). It is `@use`d last by the template's `design-system-bundle.scss`, so it wins the cascade.
  - `css/_*.scss` partials (e.g. `css/_home.scss`) — page/component styles, pulled into the bundle with `@use "..."` from `css/theme.scss`.
- **Linking:** the compiled `/css/design-system-bundle.css` is linked in `<head>` by the block layouts (`_layouts/blocks-page.html`, `_layouts/home.html`). Add new client CSS through the SCSS bundle, not a new `<link>`.
- **Scope page styles** under the page's `<body>` class (e.g. `.home …`) so they don't leak.
- **Root font-size gotcha:** the legacy WordPress theme sets `html { font-size: 62.5% }` (1rem = 10px). The design-system's rem font tokens are re-based ×1.6 in `theme.scss` to compensate — account for this when adding rem-based sizing.

The large inline `<style>` blocks in `_includes/head.html` are the legacy WordPress export — leave them; the no-inline-CSS rule applies to our own code.

---

## Functional Programming Style

This codebase uses a functional programming approach with curried, composable functions. **This is ideal for a static site generator**, which is fundamentally a series of transforms with no mutable state:

```
Content Files → Parse → Transform → Filter → Sort → Render → Static HTML
```

Each step is a pure function. Data flows through pipelines without mutation.

### Why FP for Static Sites?

1. **Transforms, not mutations**: SSGs transform input files to output files
2. **Composability**: Build complex operations from simple, testable pieces
3. **Predictability**: Pure functions always produce the same output
4. **Debuggability**: No hidden state changes to track down

### Import Aliases

Use the `#fp` alias for functional utilities:

```javascript
import { pipe, filter, map, unique } from "#fp";
import { memoize } from "#fp/memoize";
import { sortBy } from "#fp/sorting";
```

---

## Functional Utilities (`#fp`)

### Core Composition

| Function       | Purpose                         | Example                        |
| -------------- | ------------------------------- | ------------------------------ |
| `pipe(...fns)` | Compose functions left-to-right | `pipe(filter(x), map(y))(arr)` |

### Curried Array Operations

| Function           | Purpose                 | Example                           |
| ------------------ | ----------------------- | --------------------------------- |
| `filter(pred)`     | Curried array filter    | `filter(x => x > 0)(arr)`         |
| `map(fn)`          | Curried array map       | `map(x => x * 2)(arr)`            |
| `flatMap(fn)`      | Curried array flatMap   | `flatMap(x => [x, x])(arr)`       |
| `reduce(fn, init)` | Curried array reduce    | `reduce((a, x) => a + x, 0)(arr)` |
| `sort(cmp)`        | Non-mutating sort       | `sort((a, b) => a - b)(arr)`      |
| `sortBy(key)`      | Sort by property/getter | `sortBy('name')(users)`           |

### Deduplication & Filtering

| Function              | Purpose                  | Example                                  |
| --------------------- | ------------------------ | ---------------------------------------- |
| `unique(arr)`         | Remove duplicates        | `unique([1, 1, 2])` → `[1, 2]`           |
| `uniqueBy(fn)`        | Dedupe by key            | `uniqueBy(x => x.id)(arr)`               |
| `compact(arr)`        | Remove falsy values      | `compact([1, null, 2])` → `[1, 2]`       |
| `filterMap(pred, fn)` | Filter + map in one pass | `filterMap(x => x > 0, x => x * 2)(arr)` |

### Membership & Exclusion

| Function            | Purpose              | Example                             |
| ------------------- | -------------------- | ----------------------------------- |
| `memberOf(vals)`    | Membership predicate | `filter(memberOf(['a', 'b']))(arr)` |
| `notMemberOf(vals)` | Exclusion predicate  | `filter(notMemberOf(['x']))(arr)`   |
| `exclude(vals)`     | Filter out values    | `exclude(['a'])(arr)`               |
| `pick(keys)`        | Extract object keys  | `pick(['a', 'b'])(obj)`             |

### Caching & Memoization

| Function               | Purpose               | Example                              |
| ---------------------- | --------------------- | ------------------------------------ |
| `memoize(fn, opts?)`   | Cache results         | `memoize(fn, { cacheKey })`          |
| `indexBy(getKey)`      | Build cached lookup   | `indexBy(x => x.id)(arr)`            |
| `groupByWithCache(fn)` | Build cached grouping | `groupByWithCache(x => x.tags)(arr)` |

### Utilities

| Function           | Purpose                       | Example                              |
| ------------------ | ----------------------------- | ------------------------------------ |
| `pluralize(s, p?)` | Format count                  | `pluralize('item')(3)` → `"3 items"` |
| `accumulate(fn)`   | Safe array building in reduce | See below                            |

### Example: Processing Content

```javascript
import { pipe, filter, map, sortBy, unique } from "#fp";

// Process blog posts: filter drafts, extract tags, sort by date
const processedPosts = pipe(
  filter((post) => !post.draft),
  sortBy("date"),
  map((post) => ({ ...post, tags: post.tags || [] })),
)(posts);

// Get all unique tags
const allTags = pipe(
  flatMap((post) => post.tags),
  unique,
)(processedPosts);
```

### Safe Array Building with `accumulate()`

Avoid the `noAccumulatingSpread` lint error:

```javascript
// BAD - O(n^2) performance
const ids = items.reduce(
  (acc, item) => (item.id ? [...acc, item.id] : acc),
  [],
);

// GOOD - O(n) performance
import { accumulate } from "#fp";
const ids = accumulate((acc, item) => {
  if (item.id) acc.push(item.id);
  return acc;
})(items);
```

---

## Linting Rules (Biome)

The project enforces strict code quality via Biome.

### Must Follow

| Rule                             | Requirement                                 |
| -------------------------------- | ------------------------------------------- |
| `useArrowFunction`               | Use arrow functions                         |
| `useTemplate`                    | Use template literals                       |
| `useConst`                       | Use const (or let when reassignment needed) |
| `noVar`                          | Never use var                               |
| `noDoubleEquals`                 | Use `===`, not `==`                         |
| `noForEach`                      | Use `for...of` or curried `map`/`filter`    |
| `noAccumulatingSpread`           | Use `accumulate()` helper                   |
| `noUnusedImports`                | Remove unused imports                       |
| `noUnusedVariables`              | Remove unused variables                     |
| `noExcessiveCognitiveComplexity` | Max complexity: 7 (30 in tests)             |
| `noConsole`                      | No console.log except in scripts/           |

### Formatting

- 2-space indentation
- Run `bun run lint:fix` to auto-format

---

## Anti-Patterns to Avoid

1. **Don't use npm** - This project requires Bun
2. **Don't use `forEach`** - Use `for...of` loops or curried `map`/`filter`
3. **Don't accumulate with spread** - Use `accumulate()` helper for O(1) operations
4. **Don't use `var`** - Always use `const` (or `let` when reassignment needed)
5. **Don't use `==`** - Always use `===`
6. **Don't add console.log** - Except in build scripts
7. **Don't exceed complexity 7** - Break complex functions into smaller pieces
8. **Don't mutate data** - Create new objects/arrays instead

---

## When Making Changes

1. **Read existing code first** - Understand patterns before modifying
2. **Follow existing conventions** - Match the style of surrounding code
3. **Use functional patterns** - Prefer `pipe`, curried functions, immutability
4. **Run linter** - `bun run lint:fix` to auto-fix issues
5. **Keep functions small** - Stay under complexity limit of 7
6. **Use the #fp utilities** - They're optimized and well-tested

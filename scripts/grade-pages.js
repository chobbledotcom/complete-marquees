#!/usr/bin/env bun

/**
 * Grades site pages against the house content rules for Complete Marquees,
 * using Jev (TypeSafe System One) via the OpenCode zen API for the judgement
 * calls and plain code for everything a regex can catch.
 *
 * Adapted from monster-event-hire's grade-pages.js. Most copy on this site
 * lives in `blocks[]` frontmatter (service pages render `blocks[].content`,
 * `intro_content`, image-card and FAQ items) or in the `description` field on
 * location pages; the extractor reads those per layout rather than the
 * markdown body. The fuzzy checks score EEAT (Experience, Expertise,
 * Authoritativeness, Trustworthiness) against the trust brief in
 * EEAT-CREDENTIALS.md, the Voice & tone rules and the Google helpful-content
 * bar, plus the bounded SEO decisions that need no human interpretation:
 * title/heading/meta-description accuracy and location-page differentiation
 * (a town page with only the town name swapped is a template, not a local
 * page). Batch runs also aggregate exact-duplicate meta descriptions and
 * titles, the classic many-pages-one-snippet failure.
 *
 * Single page: full per-check report. No args or several pages: parallel
 * batch with a worst-first table and aggregate failure stats, so a sweep of
 * the site surfaces the pages most worth refining next.
 *
 * Usage:
 *   bun scripts/grade-pages.js                              # every gradeable page
 *   bun scripts/grade-pages.js service-pages/party-marquees.md  # one page
 *   bun scripts/grade-pages.js /party-marquees/             # URL shorthand
 *   bun scripts/grade-pages.js locations                    # a whole directory
 *   bun scripts/grade-pages.js -- --type location --limit 20
 *   bun scripts/grade-pages.js -- --prefix /areas/aldershot/
 *   bun scripts/grade-pages.js service-pages/x.md -- --no-jev  # mechanical only
 *   bun scripts/grade-pages.js -- --json / --csv out.csv / --list-checks
 *
 * The Jev API key is read from OPENCODE_API_KEY (bun loads .env) or
 * /run/secrets/opencode_api_key. Batch mode always exits 0 (it is a report);
 * single-page mode exits 1 on critical failures such as a broken internal
 * link.
 */

import { existsSync, readdirSync, readFileSync, writeFileSync } from "node:fs";
import { join, relative } from "node:path";
import { path } from "./utils.js";

const SITE_URL = "https://www.completemarquees.co.uk";
const ZEN_SYSTEMONE_URL = "https://opencode.ai/zen/v1/systemone";
const DEFAULT_MODEL = "jev-1.13";
const DEFAULT_KEY_FILE = "/run/secrets/opencode_api_key";

// Payload guard on the prose sent to Jev per page. The longest pages here run
// to around 14k characters; an over-limit state comes back as HTTP 400 and
// quietly degrades the page to mechanical-only grading.
const MAX_BODY_CHARS = 24000;
const MAX_LINKS = 60;

const CONTENT_DIRS = ["service-pages", "locations", "pages"];

// Root-level content files outside the content directories (the homepage and
// the blocks-page files that used to be WordPress pages).
const ROOT_FILES = [
  "index.html",
  "packages.md",
  "sizes-prices.md",
  "contact-us.md",
  "client-testimonials.md",
  "privacy.md",
];

// Legal text is exempt from the house voice; the rest are utility pages with
// nothing to grade. They stay in the URL map so links to them still resolve.
const SKIP_GRADE = new Set([
  "pages/not-found.md",
  "pages/thank-you.md",
  "privacy.md",
]);

// Hub pages that list other pages and are thin by design.
const LISTING_PAGES = new Set(["client-testimonials.md"]);

// No page on this site sits outside the house voice (no technical or
// accreditation pages), so the set is empty; the mechanism is kept so the
// exemption is a one-line addition if one appears.
const VOICE_EXEMPT = new Set();
const VOICE_CHECKS = new Set([
  "house_voice",
  "cliche_score",
  "contractions",
  "voice_anti_patterns",
]);

const ALL_TYPES = ["service", "location", "home", "page"];
const SELLING_TYPES = ["service", "location", "home"];

// ---------------------------------------------------------------------------
// Page discovery and extraction
// ---------------------------------------------------------------------------

const walkMarkdown = (dir) => {
  if (!existsSync(dir)) return [];
  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) return walkMarkdown(full);
    return /\.(md|html)$/.test(entry.name) ? [full] : [];
  });
};

const relPath = (file) => relative(path(), file).split("\\").join("/");

const allContentFiles = () => [
  ...CONTENT_DIRS.flatMap((dir) => walkMarkdown(path(dir))),
  ...ROOT_FILES.map((f) => path(f)),
];

const splitFrontmatter = (text) => {
  const m = text.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n?/);
  if (!m) return { data: {}, body: text };
  return { data: Bun.YAML.parse(m[1]) ?? {}, body: text.slice(m[0].length) };
};

const readPage = (file) => splitFrontmatter(readFileSync(file, "utf8"));

const normaliseUrl = (url) => {
  const clean = String(url).split("#")[0].split("?")[0];
  if (!clean || clean === "/") return "/";
  return clean.endsWith("/") ? clean : `${clean}/`;
};

const detectPageType = (rel, fm) => {
  if (rel.startsWith("locations/")) return "location";
  if (rel === "index.html") return "home";
  if (rel.startsWith("service-pages/")) return "service";
  if (fm.layout === "blocks-page") return "service";
  if (fm.layout === "home") return "home";
  if (fm.layout === "location") return "location";
  return "page";
};

/** Town name for a location page, taken from its URL slug. */
const townFor = (x) => {
  if (x.pageType !== "location") return "";
  const slug = x.url.split("/")[2] ?? "";
  return slug.replace(/-/g, " ");
};

/** Markdown to plain prose: markup, images and code dropped so word counts
 * and phrase checks see real copy. */
const markdownToText = (md) =>
  md
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<!--[\s\S]*?-->/g, " ")
    .replace(/\{%[\s\S]*?%\}|\{\{[\s\S]*?\}\}/g, " ")
    .replace(/```[\s\S]*?```/g, " ")
    .replace(/!\[[^\]]*\]\([^)]*\)/g, " ")
    .replace(/<img[^>]*>/gi, " ")
    .replace(/\[([^\]]*)\]\([^)]*\)/g, "$1")
    .replace(/<a\s[^>]*>([\s\S]*?)<\/a>/gi, "$1")
    .replace(/<\/?[a-zA-Z][^>]*>/g, " ")
    .replace(/^\s{0,3}#{1,6}\s+/gm, " ")
    .replace(/(\*\*|__|\*|`)/g, " ")
    .replace(/^\s*>\s?/gm, " ")
    .replace(/^\s*[-*+]\s+/gm, " ")
    .replace(/^\s*\d+\.\s+/gm, " ")
    .replace(/\s+/g, " ")
    .trim();

/** House copy only: blockquotes and inline quoted speech removed, because
 * the voice rules do not apply inside quotation marks. Short quoted terms
 * such as a marquee name survive - only spans of three or more words count
 * as someone else's voice. */
const stripQuoted = (md) =>
  md
    .split(/\r?\n/)
    .filter((line) => !/^\s*>/.test(line))
    .join("\n")
    .replace(/"[^"\n]*?\s[^"\n]*?\s[^"\n]*?"/g, " ")
    .replace(/\u201c[^\u201d\n]*?\s[^\u201d\n]*?\s[^\u201d\n]*?\u201d/g, " ");

const imageCardsMarkdown = (items) =>
  (Array.isArray(items) ? items : [])
    .map((it) => {
      const name = it?.name ?? "";
      const desc = it?.description ?? "";
      return name || desc ? `### ${name}\n\n${desc}` : "";
    })
    .filter(Boolean)
    .join("\n\n");

const faqsMarkdown = (items) =>
  (Array.isArray(items) ? items : [])
    .map((f) => `### ${f?.question ?? ""}\n\n${f?.answer ?? ""}`)
    .join("\n\n");

/** One block flattened into markdown: content, intro copy, image-card and
 * FAQ items. */
const blockMarkdown = (b) => {
  const parts = [];
  if (b?.content) parts.push(b.content);
  if (b?.intro_content) parts.push(b.intro_content);
  if (b?.type === "image-cards") parts.push(imageCardsMarkdown(b.items));
  if (b?.type === "faqs") parts.push(faqsMarkdown(b.items));
  return parts.filter(Boolean).join("\n\n");
};

/** A service/home page's blocks flattened into markdown - everything a
 * visitor reads. */
const blocksMarkdown = (fm) =>
  (Array.isArray(fm.blocks) ? fm.blocks : [])
    .map(blockMarkdown)
    .filter(Boolean)
    .join("\n\n");

/** The page's own copy: the layout's prose field plus the markdown body. */
const mainMarkdown = (pageType, fm, body) => {
  if (pageType === "location") return fm.description ?? "";
  if (pageType === "service" || pageType === "home") return blocksMarkdown(fm);
  return [fm.body ?? "", body].filter(Boolean).join("\n\n");
};

const ASSET_RE = /\.(jpe?g|png|gif|webp|svg|pdf|css|js|ico|mp4|webm)$/i;

const isPageHref = (target) =>
  target.startsWith("/") &&
  !target.startsWith("//") &&
  !target.startsWith("/images/") &&
  !ASSET_RE.test(target.split("?")[0]);

const pushLink = (links, seen, text, href) => {
  const target = href?.trim() ?? "";
  if (!isPageHref(target)) return;
  const norm = normaliseUrl(target);
  const label = markdownToText(text ?? "");
  const key = `${norm}|${label}`;
  if (norm === "/" || !label || seen.has(key)) return;
  seen.add(key);
  links.push({ text: label, href: norm });
};

/** Internal page links from the copy: markdown links and raw anchors. */
const extractLinks = (md) => {
  const links = [];
  const seen = new Set();
  for (const m of md.matchAll(/(?<!!)\[([^\]]*)\]\(([^)\s]+)[^)]*\)/g)) {
    pushLink(links, seen, m[1], m[2]);
  }
  for (const m of md.matchAll(
    /<a\s[^>]*href="([^"]*)"[^>]*>([\s\S]*?)<\/a>/gi,
  )) {
    pushLink(links, seen, m[2], m[1]);
  }
  return links.slice(0, MAX_LINKS);
};

const metaTitleOf = (fm) => fm.meta_title || fm.name || fm.title || "";

const metaDescriptionOf = (pageType, fm) =>
  fm.meta_description ||
  (pageType === "location" || pageType === "page" ? fm.description : "") ||
  "";

const locationHeading = (fm) => {
  const m = String(fm.description ?? "").match(/^\s*#\s+(.+)$/m);
  return m ? m[1].trim() : (fm.breadcrumb_name ?? "");
};

const headingOf = (pageType, fm) => {
  if (pageType === "location") return locationHeading(fm);
  if (pageType === "service") return fm.header_text || fm.title || "";
  if (pageType === "home") return "";
  return fm.header_text || "";
};

/** Alt text and gallery captions: gallery and image-card items on blocks
 * pages, `figure_alt` on split-image blocks. */
const blockAltFor = (b) => {
  if (b?.type === "gallery" && Array.isArray(b.items))
    return b.items.map((g) => g?.caption ?? "");
  if (b?.type === "split-image") return [b?.figure_alt ?? ""];
  if (b?.type === "image-cards" && Array.isArray(b.items))
    return b.items.map((g) => g?.name ?? "");
  return [];
};

const blockAlts = (fm) =>
  (Array.isArray(fm.blocks) ? fm.blocks : []).flatMap(blockAltFor);

/** Alt text from raw HTML (location pages carry `<img alt=...>` in their
 * description body). */
const htmlAlts = (html) =>
  [...html.matchAll(/<img[^>]*\s+alt="([^"]*)"/gi)].map((m) => m[1]);

const pageUrl = (rel, fm) =>
  normaliseUrl(fm.permalink ?? `/${rel.replace(/\.(md|html)$/, "")}/`);

const faqCountOf = (fm) =>
  (Array.isArray(fm.blocks) ? fm.blocks : [])
    .filter((b) => b?.type === "faqs")
    .reduce((n, b) => n + (Array.isArray(b.items) ? b.items.length : 0), 0);

const altsOf = (pageType, fm) =>
  pageType === "location" ? htmlAlts(fm.description ?? "") : blockAlts(fm);

/** Build the extraction state for one page. */
const extractPage = (file, forcedType) => {
  const rel = relPath(file);
  const { data: fm, body } = readPage(file);
  const pageType = forcedType || detectPageType(rel, fm);
  const main = mainMarkdown(pageType, fm, body);
  const houseMd = stripQuoted(main);
  const prose = markdownToText(main);
  const proseHouse = markdownToText(houseMd);
  const x = {
    file: rel,
    url: pageUrl(rel, fm),
    pageType,
    name: fm.name ?? "",
    heading: headingOf(pageType, fm),
    metaTitle: metaTitleOf(fm),
    metaDescription: metaDescriptionOf(pageType, fm),
    markdown: main,
    houseMarkdown: houseMd,
    prose,
    proseHouse,
    words: prose.split(/\s+/).filter(Boolean).length,
    links: extractLinks(main),
    h1Count: (main.match(/^# /gm) ?? []).length,
    subCount: (main.match(/^#{2,3} /gm) ?? []).length,
    faqCount: faqCountOf(fm),
    alts: altsOf(pageType, fm),
    // the close of the page's own copy, before any FAQ block
    tail: markdownToText(stripQuoted(main)).slice(-400),
  };
  return { ...x, town: townFor(x, fm) };
};

// ---------------------------------------------------------------------------
// Mechanical checks
//
// engine "code": fn(extraction, urlMap) returns [status, goodness, note];
// status is PASS, WARN, FAIL or SKIP, and SKIP drops the check from the
// weighted total.
// ---------------------------------------------------------------------------

const hitsOf = (patterns, text) =>
  patterns.filter(([re]) => re.test(text)).map(([, label]) => label);

const listHits = (hits) => hits.slice(0, 5).join(", ");

const US_SPELLING_RE =
  /\b(color|colors|center|centers|centered|favorite|favorites|organiz(e|es|ed|ing|ation)|speciali[z](e|es|ed|ing)|recogniz(e|es|ed)|analyz(e|es|ed|ing)|optimiz(e|es|ed|ing|ation)|traveling|traveled|jewelry|gray|aluminum|meter|meters)\b/gi;

// The no-go list plus the anti-patterns a regex can catch. Quoted speech is
// stripped first, so a customer saying "the kit was perfect" is fine.
const NO_GO_PATTERNS = [
  [/cor blimey/i, "cor blimey"],
  [/guv'?nor/i, "guv'nor"],
  [/lovely jubbly/i, "lovely jubbly"],
  [/\bcushty\b/i, "cushty"],
  [/\bbovvered\b/i, "bovvered"],
  [/\binnit\b/i, "innit"],
  [/\btreacle\b/i, "treacle"],
  [/\b(marvellous|splendid|frightfully)\b/i, "RP parody word"],
  [/\bcheerio\b|\bta-ta\b/i, "cheerio"],
  [/\bposh\b/i, "posh"],
  [/\bour kid\b|\bay up\b|\bowt\b|\bnowt\b/i, "northern marker"],
  [/'appy|\bsummat\b|\bfella\b/i, "phonetic accent spelling"],
  [/salt of the earth|honest as the day/i, "salt of the earth"],
  [/\bno[- ]nonsense\b|\bdown-to-earth\b/i, "no-nonsense"],
  [/\bahoy\b|\bshipshape\b|smooth sailing/i, "sailing cliché"],
  [/dear chap|old boy/i, "club voice"],
  [/passionate about|\bwe believe\b/i, "belief statement"],
  [
    /pulls its weight|pays for itself|proves its worth|worth the outlay/i,
    "self-justifying value phrase",
  ],
  [/100% safe|completely safe|totally safe/i, "absolute safety claim"],
  [/\bthe ultimate\b/i, "the ultimate"],
  [/\bintroducing (our|the)\b/i, "introducing our"],
  [
    /perfect (add-on|addition|for any|for all)|ideal for any/i,
    "perfect for any",
  ],
  [/endless fun|fun for all|fun-filled/i, "endless fun"],
  [
    /\bunforgettable\b/i,
    "unforgettable - brochure tell, state the real outcome instead",
  ],
  [/\bpeace of mind\b/i, "peace of mind"],
  [/\bno fuss\b|\bno stress\b|\bstress-free\b/i, "no fuss / stress-free"],
  [/\bseamless\b/i, "seamless"],
  [/\bimpress(es|ing)? (your|their) guests\b/i, "impress your guests"],
  [/\bbring (your|their) vision to life\b/i, "vision to life"],
];

// Voice anti-patterns that are worth a look but not always wrong.
const VOICE_PATTERNS = [
  [/\bactually\b/i, "actually"],
  [/\bimagine\b|\bpicture (a|the|your)\b/i, "imagine/picture"],
  [/\bnot just\b|\bmore than just\b/i, "not just X"],
  [/\band yes,/i, "and yes, ..."],
  [/\bweather-?proof\b|\bweather-?ready\b/i, "weather-proof/ready"],
  [/\bstunning\b|\bmagical\b|\btruly\b/i, "stunning/magical/truly"],
  [/\bperfect (choice|solution|fit)\b/i, "perfect choice/solution"],
];

const CONTRAST_PATTERNS = [
  [/\brather than\b/i, "rather than"],
  [/\binstead of\b/i, "instead of"],
  [/\bas opposed to\b/i, "as opposed to"],
  [/\bnot because\b/i, "not because"],
];

// Dates that go stale plus "now" narration a reader has no earlier version
// to compare against.
const DATED_PATTERNS = [
  [
    /\b(tested|inspected|certified) (in |on )?(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]* 20\d\d/i,
    "test date",
  ],
  [/\b(valid|current|certified) until\b/i, "valid until"],
  [/\bexpires?\b/i, "expiry"],
  [/\bnew for (the )?20\d\d\b/i, "new for 20XX"],
  [/\b20\d\d season\b/i, "20XX season"],
  [
    /\b(is|are) now (live|available)\b|\bno longer\b|\bwent live\b/i,
    "history narration",
  ],
];

// "Don't show your workings" - research inputs never belong in public copy.
const WORKINGS_PATTERNS = [
  [/\bbeeper\b/i, "Beeper"],
  [/\bwhatsapp\b/i, "WhatsApp"],
  [/photo dump|latest batch|sidecar|llm-img/i, "research artefact"],
  [
    /\bthe (live|product) page (lists|says|shows)\b/i,
    "page referencing itself",
  ],
  [/\bphotos? (joanne|david|dave) sent\b/i, "photos sent over"],
];

const UNCONTRACTED_RE =
  /\b(we have|we will|we are|it is|do not|does not|did not|is not|are not|cannot|you are|that is|there is|we would)\b/gi;
const CONTRACTED_RE =
  /\b(we've|we'll|we're|it's|don't|doesn't|didn't|isn't|aren't|can't|you're|that's|there's|we'd)\b/gi;

const countMatches = (re, text) => (text.match(re) ?? []).length;

const checkMetaTitleLength = (x) => {
  const n = x.metaTitle.length;
  if (n === 0 || n > 75) return ["FAIL", 0, `${n} chars (target <= 65)`];
  if (n > 65 || n < 15) return ["WARN", 0.5, `${n} chars (target <= 65)`];
  return ["PASS", 1, `${n} chars`];
};

const checkMetaDescriptionPresent = (x) =>
  x.metaDescription.trim()
    ? ["PASS", 1, "present"]
    : ["FAIL", 0, "no meta description in frontmatter"];

const checkMetaDescriptionLength = (x) => {
  const n = x.metaDescription.length;
  if (!n) return ["SKIP", 0, "no meta description to measure"];
  if (n > 175) return ["FAIL", 0, `${n} chars (target < 160)`];
  if (n >= 160 || n < 70) return ["WARN", 0.5, `${n} chars (target 70-159)`];
  return ["PASS", 1, `${n} chars`];
};

const COUNTIES = ["Hampshire", "Surrey", "Sussex"];

/** The meta checklist: the marquee keyword plus geography - the three core
 * counties on service pages, the town on location pages. */
const checkMetaKeywords = (x) => {
  const meta = x.metaDescription;
  if (!meta) return ["SKIP", 0, "no meta description"];
  const missing = [];
  if (!/\b(marquee|hire)\b/i.test(meta)) missing.push("'marquee'");
  const places = x.town ? [x.town] : COUNTIES;
  const absent = places.filter((p) =>
    p ? !meta.toLowerCase().includes(p.toLowerCase()) : false,
  );
  missing.push(...absent);
  if (!missing.length)
    return ["PASS", 1, `marquee keyword and ${places.join(", ")} present`];
  const good = missing.length === 1 ? 0.5 : 0;
  return ["WARN", good, `meta description missing: ${missing.join(", ")}`];
};

const checkHeading = (x) => {
  if (x.pageType !== "page" && x.pageType !== "home") {
    return x.heading
      ? ["PASS", 1, `h1 from frontmatter: "${x.heading}"`]
      : [
          "FAIL",
          0,
          "layout h1 field is empty (header_text / description heading)",
        ];
  }
  if (x.h1Count === 1) return ["PASS", 1, "exactly one h1 in the markdown"];
  if (x.h1Count === 0)
    return ["FAIL", 0, "no h1 (# heading) in the markdown body"];
  return ["WARN", 0.5, `${x.h1Count} h1s in the markdown body`];
};

const WORD_FLOORS = {
  service: [150, 300],
  location: [150, 300],
  home: [100, 200],
  page: [100, 200],
};

const checkThinContent = (x) => {
  if (LISTING_PAGES.has(x.file)) return ["SKIP", 0, "hub page, thin by design"];
  const [failAt, warnAt] = WORD_FLOORS[x.pageType] ?? WORD_FLOORS.service;
  if (x.words < failAt) return ["FAIL", 0, `${x.words} words of copy`];
  if (x.words < warnAt) return ["WARN", 0.5, `${x.words} words of copy`];
  return ["PASS", 1, `${x.words} words of copy`];
};

const checkSubheadings = (x) => {
  if (x.words < 400)
    return ["SKIP", 0, `${x.words} words, short enough to skip`];
  if (x.subCount >= 2)
    return ["PASS", 1, `${x.subCount} subheadings over ${x.words} words`];
  if (x.subCount === 1)
    return ["WARN", 0.5, `only 1 subheading over ${x.words} words`];
  return ["FAIL", 0, `no subheadings over ${x.words} words`];
};

const checkNoEmDash = (x) => {
  const n = countMatches(/\u2014/g, x.proseHouse);
  return n
    ? ["FAIL", 0, `${n} em-dash(es) - use spaced hyphens, commas or full stops`]
    : ["PASS", 1, "no em-dashes"];
};

const checkUkSpelling = (x) => {
  const hits = x.proseHouse.match(US_SPELLING_RE);
  if (!hits) return ["PASS", 1, "no US spellings detected"];
  const unique = [...new Set(hits.map((h) => h.toLowerCase()))];
  return ["WARN", 0, `US spelling(s): ${listHits(unique)}`];
};

const patternCheck = (patterns, status, good, what) => (x) => {
  const hits = hitsOf(patterns, x.proseHouse);
  return hits.length
    ? [status, good, `${what}: ${listHits(hits)}`]
    : ["PASS", 1, `no ${what}`];
};

/** Exclamation marks in house copy are a brochure tell; quotes are exempt. */
const checkExclamations = (x) => {
  const n = countMatches(/!/g, x.proseHouse);
  if (n === 0) return ["PASS", 1, "no exclamation marks"];
  return ["WARN", n > 2 ? 0 : 0.5, `${n} exclamation mark(s) in house copy`];
};

const checkEmoji = (x) => {
  const hits = x.proseHouse.match(/\p{Extended_Pictographic}/gu);
  return hits
    ? [
        "WARN",
        0,
        `emoji in copy (${[...new Set(hits)].join(" ")}) - a sign of old AI copy`,
      ]
    : ["PASS", 1, "no emoji"];
};

const checkBold = (x) => {
  const n = countMatches(/\*\*[^*\n]+\*\*/g, x.houseMarkdown);
  if (n > 4)
    return [
      "WARN",
      0,
      `${n} bold phrases - scattered bold is a sign of old AI copy`,
    ];
  if (n > 1) return ["WARN", 0.5, `${n} bold phrases in prose`];
  return ["PASS", 1, `${n} bold phrase(s)`];
};

const checkWeVoice = (x) => {
  const n = countMatches(/\b(we|we've|we're|we'll|our|us)\b/gi, x.proseHouse);
  if (n >= 2)
    return ["PASS", 1, `${n} 'we/our/us' - written as the family business`];
  const corporate = countMatches(
    /\bComplete Marquees (is|has|offers|provides)\b/g,
    x.proseHouse,
  );
  return [
    "WARN",
    0.5,
    `only ${n} 'we/our/us'${corporate ? ` and ${corporate} third-person 'Complete Marquees ...'` : ""} - the voice wants "we" throughout`,
  ];
};

const checkContractions = (x) => {
  const full = countMatches(UNCONTRACTED_RE, x.proseHouse);
  const short = countMatches(CONTRACTED_RE, x.proseHouse);
  if (full < 3 || full <= short)
    return ["PASS", 1, `${short} contracted vs ${full} uncontracted`];
  const sample = [
    ...new Set(
      (x.proseHouse.match(UNCONTRACTED_RE) ?? []).map((s) => s.toLowerCase()),
    ),
  ];
  return [
    "WARN",
    full > short * 2 ? 0 : 0.5,
    `${full} uncontracted vs ${short} contracted (${listHits(sample)}) - the voice is spoken`,
  ];
};

const RICH_LINK_TYPES = new Set(["service", "location", "home"]);

const checkInternalLinkCount = (x) => {
  const n = x.links.length;
  const sample = x.links
    .slice(0, 4)
    .map((l) => l.text)
    .join(", ")
    .slice(0, 90);
  if (n >= 3) return ["PASS", 1, `${n} internal link(s) (${sample})`];
  if (!RICH_LINK_TYPES.has(x.pageType)) {
    return n
      ? ["PASS", 1, `${n} internal link(s)`]
      : ["WARN", 0.5, "no internal links in copy"];
  }
  return n
    ? ["WARN", 0.5, `${n} internal link(s) (${sample})`]
    : ["FAIL", 0, "no internal links in copy"];
};

const checkLocationLinks = (x, urlMap) => {
  const n = x.links.filter((l) =>
    (urlMap.get(l.href) ?? "").startsWith("locations/"),
  ).length;
  return n
    ? ["PASS", 1, `${n} link(s) to town/location pages`]
    : [
        "WARN",
        0,
        "no location links - the EEAT baseline wants links to town pages, not just service pages",
      ];
};

const checkLinksResolve = (x, urlMap) => {
  const broken = x.links.filter((l) => !urlMap.has(l.href));
  return broken.length
    ? [
        "FAIL",
        0,
        `broken link(s): ${broken
          .slice(0, 3)
          .map((l) => `${l.text} -> ${l.href}`)
          .join("; ")}`,
      ]
    : ["PASS", 1, "all copy links resolve"];
};

const CTA_RE =
  /\b(quote|enquir|get in touch|ring us|call us|give us a (ring|call)|tell us|let us know|send us|drop us|contact us|book|check availability|check your (dates|availability))/i;

const checkCta = (x) => {
  if (CTA_RE.test(x.tail))
    return ["PASS", 1, "copy closes with an enquiry prompt"];
  if (x.links.some((l) => l.href === "/contact-us/")) {
    return [
      "WARN",
      0.5,
      "links /contact-us/ but the close has no enquiry prompt",
    ];
  }
  return [
    "WARN",
    0,
    `no enquiry prompt near the end ("...${x.tail.slice(-80)}")`,
  ];
};

const checkFaqCount = (x) => {
  if (!x.faqCount) return ["SKIP", 0, "no faqs block"];
  return x.faqCount >= 3
    ? ["PASS", 1, `${x.faqCount} FAQs`]
    : ["WARN", 0, `${x.faqCount} FAQ(s) - wants 3+ or none at all`];
};

const checkAltText = (x) => {
  if (!x.alts.length) return ["SKIP", 0, "no gallery images"];
  const empty = x.alts.filter((a) => !a.trim()).length;
  const long = x.alts.filter((a) => a.length > 100).length;
  if (!empty && !long)
    return [
      "PASS",
      1,
      `${x.alts.length} caption(s)/alt text(s) under 100 chars`,
    ];
  return ["WARN", 0.5, `${empty} empty and ${long} over-100-char alt text(s)`];
};

/** Vague anchor text tells a reader (and a crawler) nothing about the
 * destination; descriptive link text is an SEO and accessibility baseline. */
const VAGUE_ANCHOR_RE =
  /^(here|click here|this page|read more|learn more|find out more|more|visit|this one|it)$/i;

const checkVagueAnchors = (x) => {
  const vague = x.links.filter((l) => VAGUE_ANCHOR_RE.test(l.text.trim()));
  if (!vague.length) return ["PASS", 1, "no vague anchor text"];
  return [
    "WARN",
    0,
    `vague anchor(s): ${listHits(vague.map((l) => `"${l.text}"`))} - use descriptive link text`,
  ];
};

const code = (id, label, fn, weight, types = ALL_TYPES, extra = {}) => ({
  id,
  label,
  engine: "code",
  fn,
  weight,
  types,
  ...extra,
});

const CODE_CHECKS = [
  code("meta_title_length", "Meta title length", checkMetaTitleLength, 3, [
    "service",
    "location",
  ]),
  code(
    "meta_description_present",
    "Meta description present",
    checkMetaDescriptionPresent,
    6,
    ["service", "location"],
  ),
  code(
    "meta_description_length",
    "Meta description length",
    checkMetaDescriptionLength,
    2,
    ["service", "location"],
  ),
  code(
    "meta_keywords",
    "Meta has marquee keyword + geography",
    checkMetaKeywords,
    3,
    ["service", "location"],
  ),
  code("h1_present", "Page heading (h1) present", checkHeading, 3),
  code("thin_content", "Copy not thin", checkThinContent, 5),
  code("subheading_structure", "Subheading structure", checkSubheadings, 2),
  code("no_em_dash", "No em-dashes", checkNoEmDash, 3),
  code("uk_spelling", "UK spellings", checkUkSpelling, 2),
  code(
    "no_go_phrases",
    "No-go phrases absent",
    patternCheck(NO_GO_PATTERNS, "FAIL", 0, "no-go phrase(s)"),
    5,
  ),
  code(
    "voice_anti_patterns",
    "Voice anti-patterns",
    patternCheck(VOICE_PATTERNS, "WARN", 0.5, "voice anti-pattern(s)"),
    3,
  ),
  code(
    "unneeded_contrast",
    "Unneeded contrasts",
    patternCheck(CONTRAST_PATTERNS, "WARN", 0.5, "contrast framing"),
    3,
  ),
  code(
    "dated_claims",
    "No stale dates or history narration",
    patternCheck(DATED_PATTERNS, "WARN", 0, "dated claim(s)"),
    3,
  ),
  code(
    "no_workings",
    "Doesn't show its workings",
    patternCheck(WORKINGS_PATTERNS, "FAIL", 0, "research workings in copy"),
    4,
  ),
  code("emoji_check", "No emoji", checkEmoji, 2),
  code("exclamations", "No exclamation marks", checkExclamations, 2),
  code("scattered_bold", "No scattered bold", checkBold, 2),
  code("we_voice", "Written as 'we'", checkWeVoice, 2, SELLING_TYPES),
  code("contractions", "Uses contractions", checkContractions, 2),
  code(
    "internal_link_count",
    "Internal links in copy",
    checkInternalLinkCount,
    4,
  ),
  code("location_links", "Links to town pages", checkLocationLinks, 3, [
    "service",
    "location",
    "home",
  ]),
  code(
    "internal_links_resolve",
    "Internal links resolve",
    checkLinksResolve,
    5,
    ALL_TYPES,
    { critical: true },
  ),
  code(
    "cta_close",
    "Closes with an enquiry prompt",
    checkCta,
    3,
    SELLING_TYPES,
  ),
  code("faq_count", "FAQ block has 3+ questions or none", checkFaqCount, 2),
  code(
    "alt_text",
    "Gallery alt text present and < 100 chars",
    checkAltText,
    1,
    ["service", "location", "home"],
  ),
  code(
    "vague_anchor_text",
    "Descriptive link text",
    checkVagueAnchors,
    2,
    ALL_TYPES,
  ),
];

// ---------------------------------------------------------------------------
// Jev checks
//
// "score" questions return 0..n-1 against the criteria list; "noul" returns a
// probability. threshold applies to noul checks; invert=true when the
// question asks about a bad thing, so a low probability is good.
// ---------------------------------------------------------------------------

const jevScore = (
  id,
  label,
  weight,
  instructions,
  criteria,
  types = ALL_TYPES,
) => ({
  id,
  label,
  engine: "jev",
  types,
  weight,
  score_pass: 2,
  score_warn: 1,
  question: { type: "score", instructions, criteria },
});

const jevNoul = (id, label, weight, instructions, criteria, extra = {}) => ({
  id,
  label,
  engine: "jev",
  types: extra.types ?? ALL_TYPES,
  weight,
  threshold: 0.5,
  invert: !!extra.invert,
  question: { type: "noul", instructions, criteria },
});

const JEV_CHECKS = [
  // --- EEAT: each dimension scores 0-3; computeEeat combines the four ---
  jevScore(
    "eeat_experience",
    "EEAT: Experience",
    4,
    "How much first-hand operating experience does `body` show for a " +
      "family-run marquee and event hire business that owns and installs its " +
      "own marquees? Look for named towns and venues (Aldershot, Basingstoke, " +
      "Guildford, a named wedding venue), years trading (since 2002, Havant " +
      "base), and detail only someone who installs marquees would know: how " +
      "long setup takes, what the crew does on the day, how linking works, " +
      "site requirements (flat grass, no trees), flooring and dance floors. " +
      "Generic claims like 'years of experience' with nothing behind them do " +
      "not count.",
    [
      "No experience signals - could be any marquee company's page",
      "Vague experience claims with no specifics",
      "Concrete first-hand experience - named towns, venues or install detail",
      "Rich, lived-in operating detail throughout, with named towns and venues",
    ],
  ),
  jevScore(
    "eeat_expertise",
    "EEAT: Expertise",
    4,
    "How much genuine practical expertise does `body` show about marquee " +
      "hire? For this site, expertise means precise, correct detail: marquee " +
      "sizes (20x20, 20x30, 28x38, 28x58, 6x6m Pagoda), guest capacities " +
      "(seated/standing), arch heights (approx 7-8ft, centre pole 16ft), " +
      "linking capability, side walls (half clear, half solid, configurable " +
      "on the day), site requirements (flat grass, no trees/bushes/hedges), " +
      "flooring under dance floors, the weatherproofing caveat (not in stormy " +
      "weather), and the honest limit that marquees cannot attach to " +
      "buildings. Buzzword lists do not count.",
    [
      "No practical or technical detail",
      "Shallow or generic claims, no real specs",
      "Solid, correct practical detail - sizes, capacities, site needs",
      "Deep expertise - precise specs, site requirements and honest limits that help someone plan",
    ],
  ),
  jevScore(
    "eeat_authoritativeness",
    "EEAT: Authoritativeness",
    3,
    "How authoritative does the business behind this page come across? " +
      "Signals: the 20+ year track record since 2002, named towns served " +
      "across Hampshire, Surrey and West Sussex, the sister company Monster " +
      "Event Hire (same family owners, established 2002), named customer " +
      "reviews (Jane, Johan, Judith, Lucy), published transparent sizes and " +
      "prices, and links in `internal_links` to related service, package and " +
      "location pages. Unsupported self-descriptions like 'leading' or " +
      "'trusted' do not count.",
    [
      "No authority signals",
      "Self-asserted standing with nothing backing it",
      "Named towns, reviews or the sister company appear",
      "Strong - 20+ year track record, named towns, named reviews and evidence pages linked",
    ],
  ),
  jevScore(
    "eeat_trustworthiness",
    "EEAT: Trustworthiness",
    5,
    "How trustworthy does `body` feel for someone booking a marquee for a " +
      "wedding, party or public event? Concrete signals: family-run since " +
      "2002, a fixed commercial address in Havant, published transparent " +
      "sizes and package prices, delivery/installation/breakdown in-house, " +
      "weather-rated and fully compliant structures, DBS-checked and " +
      "first-aid trained staff (via the group), honest limits (flat grass " +
      "only, no trees/bushes/hedges, not in stormy weather, cannot attach to " +
      "buildings), and named customer reviews. Safety claims should be " +
      "stated as what the business does, never absolutes like '100% safe'. " +
      "Generic reassurance does not count.",
    [
      "No trust signals",
      "Generic reassurance with nothing concrete behind it",
      "Concrete signals - track record, prices, in-house delivery or honest limits",
      "Strong - concrete track record and honest limits, restrained factual wording",
    ],
  ),
  jevScore(
    "concrete_facts",
    "Concrete facts vs marketing filler",
    5,
    "How concrete is `body`: specific facts, numbers, marquee sizes (20x20, " +
      "28x58), prices (£295, £3,100), guest numbers (seats 80), named towns " +
      "(Aldershot, Guildford, Winchester), and named counties (Hampshire, " +
      "Surrey, West Sussex) - versus brochure filler that could describe any " +
      "marquee company ('perfect for any occasion', 'unforgettable', " +
      "'weather-ready')?",
    [
      "Generic filler throughout",
      "Mostly generic with one or two specifics",
      "A healthy mix of concrete facts and selling copy",
      "Concrete facts, numbers and named examples throughout",
    ],
  ),
  jevScore(
    "house_voice",
    "House voice (WhatsApp test)",
    4,
    "Judge `body_house` against this house voice: plain, dry, family-run, " +
      "written as 'we' by a husband-and-wife business (Joanne and David " +
      "Morris), with contractions, loose longer sentences, hedges ('give or " +
      "take', 'around twenty miles') and specific detail (towns, sizes, " +
      "prices). The test: could the owners type each line on their phone " +
      "between two event installs? Brochure polish, short punchy parallel " +
      "sentences, uncontracted formal English, emojis, stock phrases " +
      "('unforgettable', 'seamless', 'weather-ready') and small-business " +
      "pomposity all fail. Score ONLY `body_house`; quoted customer reviews " +
      "are exempt and already stripped.",
    [
      "Brochure copy throughout - reads like an agency wrote it",
      "Mostly polished marketing voice with a few plain lines",
      "Mostly plain-spoken with a few brochure lines a light edit would fix",
      "Plain, dry and natural throughout - passes the WhatsApp test line by line",
    ],
  ),
  jevScore(
    "cliche_score",
    "Cliché score",
    4,
    "Judge `body_house` for copywriting clichés - the trying-too-hard " +
      "structures, not single words. Score ONLY `body_house`; quoted reviews " +
      "are exempt and already stripped. Look for: sentence fragments in prose " +
      "('No fuss.', 'The lot.'); cinematic one-line closers; X / X / X - Y " +
      "build-ups; lists of three with a comic third; contrast flips ('not " +
      "just a marquee'); 'imagine' or 'picture a' daydreams; handling " +
      "objections nobody raised ('and yes, it works in a garden'); borrowed " +
      "warm-up questions ('Looking for a marquee?'), especially as the " +
      "opening line or two in a row; self-justifying value phrases; " +
      "overselling modifiers ('ultimate', 'unforgettable'). 0 means riddled, " +
      "3 means clean.",
    [
      "Riddled - built from advert structures, several distinct failure modes",
      "Multiple clichés - three or more hits, or one structure repeated",
      "A cliché or two that a light rewrite would trim",
      "Clean - plain comfortable prose",
    ],
  ),
  jevScore(
    "searcher_intent",
    "Searcher intent",
    4,
    "Judge whether `body` serves the person who lands on this page. Work " +
      "out who they are from `page.url` and `page.page_type`: a service page " +
      "gets someone deciding whether to hire a marquee (what it is, sizes " +
      "and prices, who it suits, site requirements, what's included, how to " +
      "enquire); a location page gets someone hiring a marquee in `page.town` " +
      "(can they come here, what sizes and prices, local events); the home " +
      "page gets someone choosing a marquee company (range, track record, " +
      "next step); other pages get someone after that page's specific " +
      "answer. Does `body` say early what the page offers, answer that " +
      "visitor's main questions, and make the next step obvious?",
    [
      "Intent not addressed - the visitor can't tell what the page offers them",
      "Intent implied but late or incomplete - main questions left unanswered",
      "Intent addressed - who it's for and the main questions answered clearly",
      "Addressed early and completely - questions answered up front, next step obvious",
    ],
  ),
  jevScore(
    "choice_support",
    "Helps choose between marquee options",
    4,
    "This is a service page. Does `body` help the visitor choose between the " +
      "options - the Capri sizes (20x20 to 28x58), the Pagoda, the packages, " +
      "flooring/lighting/furniture add-ons, and which suits which event type, " +
      "site or budget - or does it just restate marquee names and generic " +
      "praise?",
    [
      "No choice guidance - just names or generic praise",
      "A hint of guidance but mostly restated names",
      "Useful guidance on which options suit which events or sites",
      "Clear buckets and choosing criteria - size, audience, site and budget fit",
    ],
    ["service"],
  ),
  jevNoul(
    "social_proof",
    "Social proof in the copy",
    3,
    "Does `body` quote or attribute a customer review, name a client, or " +
      "mention a real booking at a named town or venue?",
    {
      true: "A review quote, named client or named venue appears",
      false: "No social proof anywhere",
    },
  ),
  jevNoul(
    "practical_limits",
    "States practical limits",
    3,
    "Does `body` state honest practical limits: flat-grass site requirements, " +
      "no trees/bushes/hedges, marquee sizes, guest capacities, weather " +
      "limits (not in stormy weather), what the marquee cannot do (attach to " +
      "buildings), or what's not included?",
    {
      true: "At least one honest practical limit appears",
      false: "Claims suitability for everything with no limits stated",
    },
    { types: SELLING_TYPES },
  ),
  jevNoul(
    "adversarial_customer",
    "Not set against the customer",
    3,
    "Does `body_house` frame the customer or their guests as opponents - " +
      "chancers, needing taking down a peg, trying it on - or cast the staff " +
      "as having to manage them? The customer is the person we're working " +
      "for.",
    {
      true: "The copy sets us against the customer or guests",
      false: "The customer is only ever the people we're working for",
    },
    { invert: true },
  ),
  // --- Title and metadata accuracy (bounded decisions, no human input
  // needed to interpret the answer) ---
  jevNoul(
    "title_content_match",
    "Title matches body",
    2,
    "Does the page title (`page.meta_title`) accurately represent what `body` " +
      "covers? A searcher who lands here should find what the title promised. " +
      "Flag titles that are misleading, generic boilerplate, keyword-stuffed, " +
      "or about something the body barely mentions.",
    {
      true: "Title accurately reflects the body",
      false: "Title is misleading, boilerplate, or mismatched with the body",
    },
  ),
  jevNoul(
    "h1_title_consistency",
    "H1 and title consistent",
    2,
    "Do the page's H1 (`page.heading`) and title (`page.meta_title`) describe " +
      "the same core topic? A mismatch (e.g. title says weddings, H1 says " +
      "garden parties) confuses both users and search engines.",
    {
      true: "H1 and title describe the same core topic",
      false: "H1 and title diverge on the core topic",
    },
    { types: ["service", "location", "page"] },
  ),
  jevNoul(
    "meta_description_match",
    "Meta description matches body",
    2,
    "Does the meta description (`page.meta_description`) accurately represent " +
      "what `body` covers - the same topic, with no promises the page doesn't " +
      "keep?",
    {
      true: "Meta description accurately reflects the body",
      false: "Meta description is mismatched, stale or misleading",
    },
    { types: ["service", "location"] },
  ),
  // --- Local-page uniqueness: template pages with the town name swapped in
  // have almost no chance of ranking (or earning the click) for that town ---
  jevScore(
    "local_differentiation",
    "Local page differentiation",
    4,
    "This is a location page for `page.town`. Does `body` contain meaningful " +
      "local content specific to this town - local venues, landmarks, " +
      "schools, businesses, nearby towns, local events, or anything that " +
      "could not be said with the town name swapped for any other? Or is it a " +
      "generic marquee template with the town name dropped in?",
    [
      "Generic template - only the town name differs from any other location page",
      "Mostly generic with one or two token local mentions",
      "Several genuinely local references - local venues, landmarks or nearby towns",
      "Rich local content - specific local venues, landmarks and local relevance throughout",
    ],
    ["location"],
  ),
];

const CHECKS = [...CODE_CHECKS, ...JEV_CHECKS];

// ---------------------------------------------------------------------------
// Jev client
// ---------------------------------------------------------------------------

const loadApiKey = () => {
  const key = process.env.OPENCODE_API_KEY;
  if (key) return key.trim();
  try {
    return readFileSync(DEFAULT_KEY_FILE, "utf8").trim();
  } catch {
    return null;
  }
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const postJev = (payload, apiKey, sessionId) =>
  fetch(ZEN_SYSTEMONE_URL, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
      "User-Agent": "grade-pages/0.1 (complete-marquees)",
      "x-opencode-session": sessionId,
    },
    body: JSON.stringify(payload),
  });

/** One attempt: { resp } on success, { err, retryAfter } on failure, where
 * retryAfter is null when retrying will not help. */
const attemptJev = async (payload, apiKey, sessionId, attempt) => {
  try {
    const res = await postJev(payload, apiKey, sessionId);
    if (res.ok) return { resp: await res.json() };
    const err = `HTTP ${res.status}: ${(await res.text()).slice(0, 400)}`;
    if (res.status === 429) return { err, retryAfter: 5000 * (attempt + 1) };
    if ([400, 401, 402].includes(res.status)) return { err, retryAfter: null };
    return { err, retryAfter: 1000 + attempt };
  } catch (e) {
    return { err: `${e.name}: ${e.message}`, retryAfter: 1000 + attempt };
  }
};

const callJev = async (state, questions, model, apiKey, sessionId) => {
  const payload = { model, state, questions };
  let last = { err: "no attempts made" };
  for (let attempt = 0; attempt < 3; attempt++) {
    last = await attemptJev(payload, apiKey, sessionId, attempt);
    if (last.resp || last.retryAfter === null) break;
    await sleep(last.retryAfter);
  }
  return {
    resp: last.resp ?? null,
    err: last.resp ? null : last.err,
    req: payload,
  };
};

// ---------------------------------------------------------------------------
// Grading
// ---------------------------------------------------------------------------

const buildJevState = (x) => ({
  page: {
    url: `${SITE_URL}${x.url}`,
    page_type: x.pageType,
    name: x.name,
    heading: x.heading,
    town: x.town,
    meta_title: x.metaTitle,
    meta_description: x.metaDescription,
  },
  body: x.prose.slice(0, MAX_BODY_CHARS),
  body_truncated: x.prose.length > MAX_BODY_CHARS,
  // quoted reviews stripped - customer voices are not judged on house voice
  body_house: x.proseHouse.slice(0, MAX_BODY_CHARS),
  internal_links: x.links,
  business: {
    name: "Complete Marquees",
    who: "family-run marquee and event hire business run by Joanne and David Morris from Havant, Hampshire, since 2002",
    context:
      "owns and installs its Capri and Pagoda marquees in-house (delivery, setup and breakdown), with a sister company Monster Event Hire (same family owners, also since 2002) supplying entertainment; " +
      "covers Hampshire, Surrey and West Sussex; publishes full sizes and package prices; " +
      "group carries £10 million public liability insurance with DBS-checked, first-aid trained staff; " +
      "structures are weather-rated, secure and fully compliant",
  },
});

const activeChecks = (x) =>
  CHECKS.filter(
    (c) =>
      c.types.includes(x.pageType) &&
      !(VOICE_EXEMPT.has(x.file) && VOICE_CHECKS.has(c.id)),
  );

const runMechanical = (x, checks, urlMap) => {
  const results = {};
  for (const c of checks.filter((check) => check.engine === "code")) {
    const [status, goodness, note] = c.fn(x, urlMap);
    results[c.id] = {
      label: c.label,
      engine: "code",
      weight: c.weight,
      status,
      goodness,
      note,
      critical: !!c.critical,
    };
  }
  return results;
};

const statusFor = (good, pass, warn) => {
  if (good >= pass) return "PASS";
  return good >= warn ? "WARN" : "FAIL";
};

const gradeNoul = (ans, c) => {
  const p = ans.noul;
  const good = c.invert ? 1 - p : p;
  return {
    value: p,
    goodness: good,
    status: statusFor(good, c.threshold, c.threshold - 0.3),
    note: `noul=${p.toFixed(2)}${c.invert ? " (lower is better)" : ""}`,
  };
};

const gradeScore = (ans, c) => {
  const s = ans.score;
  const good = s >= c.score_pass ? 1 : s >= c.score_warn ? 0.5 : 0;
  const levels = c.question.criteria.length - 1;
  return {
    value: s,
    goodness: good,
    status: statusFor(good, 1, 0.5),
    note: `score=${s.toFixed(2)}/${levels} (conf ${(ans.confidence ?? 0).toFixed(2)})`,
  };
};

/** A hard FAIL the model isn't confident about is a review flag, not a
 * verdict (TypeSafe confidence architecture: act only when confident). */
const softenUnconfident = (entry, confidence) => {
  if (typeof confidence !== "number" || confidence >= 0.3) return entry;
  if (entry.status !== "FAIL") return entry;
  return {
    ...entry,
    status: "WARN",
    goodness: 0.5,
    note: `${entry.note} [low confidence - human review]`,
  };
};

const gradeJevAnswer = (ans, c) => {
  const graded = ans.type === "noul" ? gradeNoul(ans, c) : gradeScore(ans, c);
  return softenUnconfident(
    {
      label: c.label,
      engine: "jev",
      weight: c.weight,
      critical: false,
      raw: ans,
      ...graded,
    },
    ans.confidence,
  );
};

const gradeJevAnswers = (answers, checks) =>
  Object.fromEntries(
    checks
      .filter((c) => answers[c.id])
      .map((c) => [c.id, gradeJevAnswer(answers[c.id], c)]),
  );

const letterFor = (score) => {
  if (score >= 90) return "A";
  if (score >= 75) return "B";
  if (score >= 60) return "C";
  return score >= 45 ? "D" : "F";
};

const summarise = (results) => {
  const counted = Object.values(results).filter((r) => r.status !== "SKIP");
  const totalW = counted.reduce((a, r) => a + r.weight, 0);
  const gotW = counted.reduce((a, r) => a + r.weight * r.goodness, 0);
  const score = totalW ? Math.round((100 * gotW) / totalW) : 0;
  const counts = { PASS: 0, WARN: 0, FAIL: 0 };
  for (const r of counted) counts[r.status]++;
  return { score, letter: letterFor(score), counts };
};

const EEAT_IDS = [
  "eeat_experience",
  "eeat_expertise",
  "eeat_authoritativeness",
  "eeat_trustworthiness",
];

/** The four EEAT dimension scores (0-3 each) combined into a headline, or
 * null when none were graded (mechanical-only runs). */
const computeEeat = (results) => {
  const dims = Object.fromEntries(
    EEAT_IDS.filter((id) => typeof results[id]?.value === "number").map(
      (id) => [id, results[id].value],
    ),
  );
  const values = Object.values(dims);
  if (!values.length) return null;
  const mean = values.reduce((a, v) => a + v, 0) / values.length;
  const score = Math.round((mean / 3) * 100);
  return {
    mean: Math.round(mean * 100) / 100,
    score,
    letter: letterFor(score),
    dimensions: dims,
  };
};

const sessionIdFor = (x) =>
  `grade-pages-${x.url.replace(/[^a-z0-9]/gi, "").slice(-40)}`;

const logVerbose = (opts, req, resp) => {
  if (!opts.verbose) return;
  console.error("--- jev request ---");
  console.error(JSON.stringify(req, null, 2));
  console.error("--- jev response ---");
  console.error(JSON.stringify(resp, null, 2));
};

/** Ask Jev every fuzzy question for the page in one call. */
const runJev = async (x, checks, opts) => {
  const jevChecks = checks.filter((c) => c.engine === "jev");
  if (!jevChecks.length || opts.noJev)
    return { results: {}, info: null, error: null };
  if (!opts.apiKey) return { results: {}, info: null, error: "no API key" };
  const questions = Object.fromEntries(
    jevChecks.map((c) => [c.id, structuredClone(c.question)]),
  );
  const started = Date.now();
  const { resp, err, req } = await callJev(
    buildJevState(x),
    questions,
    opts.model,
    opts.apiKey,
    sessionIdFor(x),
  );
  logVerbose(opts, req, resp);
  if (err) return { results: {}, info: null, error: err };
  return {
    results: gradeJevAnswers(resp.answers ?? {}, jevChecks),
    info: {
      model: resp.model ?? opts.model,
      input_tokens: resp.usage?.input_tokens,
      output_tokens: resp.usage?.output_tokens,
      seconds: (Date.now() - started) / 1000,
    },
    error: null,
  };
};

const elapsed = (start) => Math.round((Date.now() - start) / 10) / 100;

const gradePage = async (file, opts, urlMap) => {
  const start = Date.now();
  try {
    const x = extractPage(file, opts.type);
    const checks = activeChecks(x);
    const jev = await runJev(x, checks, opts);
    const results = { ...runMechanical(x, checks, urlMap), ...jev.results };
    return {
      file: x.file,
      url: x.url,
      page_type: x.pageType,
      meta_title: x.metaTitle,
      meta_description: x.metaDescription,
      ...summarise(results),
      checks: results,
      eeat: computeEeat(results),
      jev: jev.info,
      jev_error: jev.error,
      seconds: elapsed(start),
    };
  } catch (e) {
    return {
      file: relPath(file),
      url: relPath(file),
      page_type: "?",
      score: null,
      letter: "E",
      counts: { PASS: 0, WARN: 0, FAIL: 0 },
      checks: {},
      eeat: null,
      jev: null,
      jev_error: null,
      error: `${e.name}: ${e.message}`,
      seconds: elapsed(start),
    };
  }
};

// ---------------------------------------------------------------------------
// Reports
// ---------------------------------------------------------------------------

const MARKS = { PASS: "+", WARN: "~", FAIL: "X", SKIP: "-" };

const formatEeat = (e) => {
  const dims = EEAT_IDS.filter((id) => id in e.dimensions)
    .map((id) => `${id.replace("eeat_", "")} ${e.dimensions[id].toFixed(1)}`)
    .join(" | ");
  return `EEAT: ${e.score}/100 (${e.letter}) - ${dims}`;
};

const printCheckLine = (r) => {
  const crit = r.critical && r.status === "FAIL" ? " [CRITICAL]" : "";
  console.log(
    `  [${MARKS[r.status]}] ${r.status.padEnd(4)} ${r.label.padEnd(42)} (${r.engine.padEnd(4)} w${r.weight}) ${r.note}${crit}`,
  );
};

const printReport = (x, result) => {
  console.log(`Complete Marquees page grader - ${x.url} (${x.file})`);
  console.log(
    `page type: ${x.pageType}${x.town ? ` (${x.town})` : ""} | words: ${x.words} | checks: ${Object.keys(result.checks).length}\n`,
  );
  for (const r of Object.values(result.checks)) printCheckLine(r);
  const c = result.counts;
  console.log(
    `\nScore: ${result.score}/100 (${result.letter}) - ${c.PASS} pass, ${c.WARN} warn, ${c.FAIL} fail`,
  );
  if (result.eeat) console.log(formatEeat(result.eeat));
  if (result.jev) {
    const j = result.jev;
    console.log(
      `Jev: model=${j.model}, ${j.input_tokens} in / ${j.output_tokens} out tokens, ${j.seconds.toFixed(2)}s`,
    );
  }
};

const failedIds = (row, sep = ",") =>
  Object.entries(row.checks)
    .filter(([, chk]) => chk.status === "FAIL")
    .map(([id]) => id)
    .join(sep);

const progressLine = (row, done, total) => {
  const prefix = `[${done}/${total}]`;
  if (row.score === null)
    return `${prefix} SKIP ${row.url} - ${(row.error ?? "").slice(0, 80)}`;
  const jevNote = row.jev_error ? ` (jev: ${row.jev_error.slice(0, 40)})` : "";
  return `${prefix} ${String(row.score).padStart(3)} ${row.letter} ${row.page_type.padEnd(8)} ${(failedIds(row) || "-").slice(0, 60)} ${row.url}${jevNote}`;
};

const gradeAll = async (targets, opts, urlMap) => {
  const rows = new Array(targets.length);
  let done = 0;
  let next = 0;
  const worker = async () => {
    while (next < targets.length) {
      const i = next++;
      rows[i] = await gradePage(targets[i], opts, urlMap);
      done++;
      console.error(progressLine(rows[i], done, targets.length));
    }
  };
  const count = Math.min(opts.workers, targets.length);
  await Promise.all(Array.from({ length: count }, worker));
  return rows.sort(
    (a, b) =>
      (a.score === null) - (b.score === null) ||
      (a.score ?? 0) - (b.score ?? 0),
  );
};

const RULE = "-".repeat(110);

const tableRow = (r) => {
  if (r.score === null) {
    return `${"---".padStart(5)} ${"-".padEnd(2)} ${"".padStart(5)} ${r.page_type.padEnd(9)} ${"".padEnd(9)} ${r.url} - ${(r.error ?? "").slice(0, 60)}`;
  }
  const c = r.counts;
  const eeat = r.eeat ? `${r.eeat.score}${r.eeat.letter}` : "-";
  return `${String(r.score).padStart(5)} ${r.letter.padEnd(2)} ${eeat.padStart(5)} ${r.page_type.padEnd(9)} ${`${c.PASS}/${c.WARN}/${c.FAIL}`.padEnd(9)} ${failedIds(r) || "-"} ${r.url}`;
};

const tally = (rows, status) => {
  const counter = new Map();
  for (const chk of rows.flatMap((r) => Object.values(r.checks))) {
    if (chk.status === status)
      counter.set(chk.label, (counter.get(chk.label) ?? 0) + 1);
  }
  return [...counter].sort((a, b) => b[1] - a[1]);
};

const median = (values) => {
  const sorted = [...values].sort((a, b) => a - b);
  return sorted[Math.floor(sorted.length / 2)];
};

const printTally = (heading, status, rows, n) => {
  const top = tally(rows, status).slice(0, n);
  if (!top.length) return;
  console.log(`\n${heading}`);
  for (const [label, count] of top)
    console.log(`  ${status} x${String(count).padEnd(4)} ${label}`);
};

const printEeatSummary = (graded) => {
  const rows = graded.filter((r) => r.eeat);
  if (!rows.length) return;
  const worst = [...rows]
    .sort((a, b) => a.eeat.score - b.eeat.score)
    .slice(0, 5)
    .map((r) => `${r.url} ${r.eeat.score}${r.eeat.letter}`)
    .join(", ");
  console.log(
    `\nEEAT: ${rows.length} pages graded | median ${median(rows.map((r) => r.eeat.score))}/100 | worst: ${worst}`,
  );
};

const normaliseMeta = (v) =>
  String(v ?? "")
    .toLowerCase()
    .replace(/\s+/g, " ")
    .trim();

/** Exact-duplicate meta descriptions and titles across the batch. This is a
 * deterministic signal (no Jev call needed) for the classic SEO failure of
 * many pages sharing one snippet, so it is surfaced at batch level only. */
const printDuplicateMeta = (heading, field, rows) => {
  const buckets = new Map();
  for (const r of rows) {
    const key = normaliseMeta(r[field]);
    if (!key) continue;
    if (!buckets.has(key)) buckets.set(key, []);
    buckets.get(key).push(r.url);
  }
  const dupes = [...buckets]
    .filter(([, urls]) => urls.length > 1)
    .sort((a, b) => b[1].length - a[1].length);
  if (!dupes.length) return;
  console.log(`\n${heading}`);
  for (const [value, urls] of dupes.slice(0, 5)) {
    console.log(
      `  x${String(urls.length).padEnd(3)} "${value.slice(0, 90)}" -> ${urls.join(", ")}`,
    );
  }
};

const printSummary = (rows, seconds) => {
  const graded = rows.filter((r) => r.score !== null);
  if (!graded.length) return;
  const letters = {};
  for (const r of graded) letters[r.letter] = (letters[r.letter] ?? 0) + 1;
  const spread = Object.keys(letters)
    .sort()
    .map((l) => `${l}:${letters[l]}`)
    .join(" ");
  const jevErrors = graded.filter((r) => r.jev_error).length;
  console.log(RULE);
  console.log(
    `${graded.length} graded, ${rows.length - graded.length} errored, ${jevErrors} without Jev | median ${median(graded.map((r) => r.score))} | ${spread} | ${seconds.toFixed(0)}s total`,
  );
  printTally("Most-failed checks across the batch:", "FAIL", graded, 10);
  printTally("Most-warned checks across the batch:", "WARN", graded, 8);
  printEeatSummary(graded);
  printDuplicateMeta(
    "Duplicate meta descriptions:",
    "meta_description",
    graded,
  );
  printDuplicateMeta("Duplicate meta titles:", "meta_title", graded);
};

const printTable = (rows, opts, seconds) => {
  console.log(
    `\nComplete Marquees page grader - batch of ${rows.length} (workers=${opts.workers}, model=${opts.noJev ? "none" : opts.model})`,
  );
  console.log(
    `${"Score".padStart(5)} ${"L".padEnd(2)} ${"EEAT".padStart(5)} ${"Type".padEnd(9)} ${"p/w/x".padEnd(9)} Failed checks`,
  );
  console.log(RULE);
  for (const r of rows) console.log(tableRow(r));
  printSummary(rows, seconds);
};

const csvEscape = (v) => {
  const s = String(v ?? "");
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
};

const CSV_COLUMNS = [
  "score",
  "letter",
  "eeat",
  "eeat_letter",
  "page_type",
  "url",
  "file",
  "pass",
  "warn",
  "fail",
  "failed_checks",
  "jev_error",
  "error",
];

const csvRow = (r) =>
  [
    r.score,
    r.letter,
    r.eeat?.score,
    r.eeat?.letter,
    r.page_type,
    r.url,
    r.file,
    r.counts.PASS,
    r.counts.WARN,
    r.counts.FAIL,
    failedIds(r, ";"),
    r.jev_error,
    r.error,
  ]
    .map(csvEscape)
    .join(",");

const writeCsv = (rows, file) => {
  writeFileSync(
    file,
    `${[CSV_COLUMNS.join(","), ...rows.map(csvRow)].join("\n")}\n`,
  );
  console.error(`\nCSV written to ${file}`);
};

const runBatch = async (targets, opts, urlMap) => {
  const start = Date.now();
  const rows = await gradeAll(targets, opts, urlMap);
  if (opts.json) console.log(JSON.stringify(rows, null, 2));
  else printTable(rows, opts, (Date.now() - start) / 1000);
  if (opts.csv) writeCsv(rows, opts.csv);
  return 0;
};

const runSingle = async (file, opts, urlMap) => {
  const result = await gradePage(file, opts, urlMap);
  if (result.score === null) {
    console.error(`ERROR grading ${result.url}: ${result.error}`);
    return 2;
  }
  printReport(extractPage(file, opts.type), result);
  if (result.jev_error)
    console.error(
      `note: Jev unavailable (${result.jev_error}); report is mechanical-only`,
    );
  const critical = Object.values(result.checks).filter(
    (r) => r.critical && r.status === "FAIL",
  );
  for (const r of critical) console.log(`CRITICAL: ${r.label}: ${r.note}`);
  return critical.length ? 1 : 0;
};

// ---------------------------------------------------------------------------
// URL map and target resolution
// ---------------------------------------------------------------------------

const permalinksOf = (fm) => [
  fm.permalink,
  ...(Array.isArray(fm.redirect_from) ? fm.redirect_from : []),
];

const builtSiteUrls = (dir, prefix = "/") => {
  if (!existsSync(dir)) return [];
  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    if (entry.isDirectory())
      return builtSiteUrls(join(dir, entry.name), `${prefix}${entry.name}/`);
    return entry.name === "index.html" ? [prefix] : [];
  });
};

/** Every URL a copy link could legitimately point at: permalinks and
 * redirect_from aliases from the content files, plus pages the Chobble
 * Template adds, taken from _site/ when a build is on disk. */
const buildUrlMap = () => {
  const pairs = allContentFiles().flatMap((file) =>
    permalinksOf(readPage(file).data)
      .filter((u) => typeof u === "string")
      .map((url) => [normaliseUrl(url), relPath(file)]),
  );
  const built = builtSiteUrls(path("_site")).map((url) => [url, "_site"]);
  // earlier entries win, so a content file beats a built-site duplicate
  return new Map([...pairs, ...built].reverse());
};

/** permalink: false pages are never built, so there is nothing to grade. */
const isGradeable = (rel) =>
  !SKIP_GRADE.has(rel) && readPage(path(rel)).data.permalink !== false;

const resolveFileTarget = (t, gradeable) => {
  const asPath = path(t.replace(/^\.\//, ""));
  if (!existsSync(asPath)) return [];
  if (/\.(md|html)$/.test(asPath)) return [asPath];
  const dirPrefix = `${relPath(asPath)}/`;
  return gradeable
    .filter((rel) => rel.startsWith(dirPrefix))
    .map((rel) => path(rel));
};

const resolveTarget = (t, urlMap, gradeable) => {
  const files = resolveFileTarget(t, gradeable);
  if (files.length) return files;
  const urlPath = t.startsWith(SITE_URL) ? t.slice(SITE_URL.length) : t;
  const rel = urlPath.startsWith("/")
    ? urlMap.get(normaliseUrl(urlPath))
    : null;
  if (!rel || rel === "_site") throw new Error(`cannot resolve target '${t}'`);
  return [path(rel)];
};

const selectPool = (opts, urlMap) => {
  const gradeable = allContentFiles().map(relPath).filter(isGradeable);
  const targets = opts.targets.length
    ? opts.targets.flatMap((t) => resolveTarget(t, urlMap, gradeable))
    : gradeable.map((rel) => path(rel));
  const filtered = targets.filter((file) => {
    if (!opts.prefix && !opts.onlyType) return true;
    const x = extractPage(file);
    return (
      (!opts.prefix || x.url.includes(opts.prefix)) &&
      (!opts.onlyType || x.pageType === opts.onlyType)
    );
  });
  return opts.limit > 0 ? filtered.slice(0, opts.limit) : filtered;
};

// ---------------------------------------------------------------------------
// main
// ---------------------------------------------------------------------------

const usage = () =>
  console.log(`Usage: bun scripts/grade-pages.js [targets] [options]

Targets (default: every gradeable page):
  service-pages/party-marquees.md      a source file
  /party-marquees/                     URL shorthand (redirect_from aliases work too)
  ${SITE_URL}/areas/aldershot/
  locations                            a directory

Options:
  --type <t>      only pages of this type (${ALL_TYPES.join(", ")})
  --prefix <s>    only pages whose URL contains this substring
  --limit <n>     grade at most n pages
  --workers <n>   parallel batch workers (default 4)
  --csv <path>    write batch results to a CSV file
  --json          machine-readable output
  --no-jev        mechanical checks only
  --model <id>    Jev model id (default ${DEFAULT_MODEL})
  --force-type <t> grade the targets as this page type
  --list-checks   print the check schema and exit
  --verbose       dump the Jev request and response
  --help          this message`);

const VALUE_FLAGS = {
  "--prefix": (o, v) => {
    o.prefix = v;
  },
  "--limit": (o, v) => {
    o.limit = Number.parseInt(v, 10) || 0;
  },
  "--workers": (o, v) => {
    o.workers = Math.max(1, Number.parseInt(v, 10) || 4);
  },
  "--csv": (o, v) => {
    o.csv = v;
  },
  "--model": (o, v) => {
    o.model = v;
  },
  "--type": (o, v) => {
    o.onlyType = v;
  },
  "--force-type": (o, v) => {
    o.type = v;
  },
};

const BOOL_FLAGS = {
  "--json": "json",
  "--no-jev": "noJev",
  "--list-checks": "listChecks",
  "--verbose": "verbose",
  "--help": "help",
  "-h": "help",
};

const validateType = (t) => {
  if (t && !ALL_TYPES.includes(t)) {
    throw new Error(`unknown page type '${t}' (${ALL_TYPES.join(", ")})`);
  }
};

/** Consume one argument (and its value, for value flags) into opts. */
const applyArg = (opts, a, rest) => {
  if (VALUE_FLAGS[a]) {
    if (!rest.length) throw new Error(`${a} needs a value`);
    VALUE_FLAGS[a](opts, rest.shift());
    return;
  }
  if (BOOL_FLAGS[a]) {
    opts[BOOL_FLAGS[a]] = true;
    return;
  }
  if (a.startsWith("-")) throw new Error(`unknown option ${a}`);
  opts.targets.push(a);
};

const parseArgs = (argv) => {
  const opts = { targets: [], limit: 0, workers: 4, model: DEFAULT_MODEL };
  const args = [...argv];
  while (args.length) applyArg(opts, args.shift(), args);
  validateType(opts.type);
  validateType(opts.onlyType);
  return opts;
};

const listChecks = () => {
  for (const c of CHECKS) {
    console.log(
      `${c.id.padEnd(26)} ${c.engine.padEnd(5)} w${String(c.weight).padEnd(3)} [${c.types.join(",")}]  ${c.label}${c.critical ? " [CRITICAL]" : ""}`,
    );
  }
  return 0;
};

const run = (opts) => {
  if (opts.help) return usage() ?? 0;
  if (opts.listChecks) return listChecks();
  const urlMap = buildUrlMap();
  const pool = selectPool(opts, urlMap);
  if (!pool.length) {
    console.error("no pages match the given targets and filters");
    return 2;
  }
  const withKey = { ...opts, apiKey: opts.noJev ? null : loadApiKey() };
  if (pool.length === 1 && !opts.json && !opts.csv)
    return runSingle(pool[0], withKey, urlMap);
  return runBatch(pool, withKey, urlMap);
};

const main = async () => {
  try {
    process.exit(await run(parseArgs(process.argv.slice(2))));
  } catch (e) {
    console.error(e.message);
    usage();
    process.exit(2);
  }
};

if (import.meta.main) {
  await main();
}

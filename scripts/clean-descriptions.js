#!/usr/bin/env bun
/**
 * Second-pass cleanup of `description` frontmatter content.
 *
 * - Strip WordPress-export `data-*` attributes from common inline/block tags.
 * - Strip `class="caption"` and `title="..."` from `<a>` (the .caption class
 *   isn't defined in CSS; title is just a hover tooltip).
 * - Decode common decorative HTML entities to their Unicode characters.
 * - Re-run the markdown converter on the cleaned content so that paragraphs
 *   / lists / inline strong / links that are now "clean" become markdown.
 */
import { readdirSync, readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const DIRS = ["locations", "service-pages"];

const ENTITY_MAP = {
  "&#8211;": "–",
  "&#8212;": "—",
  "&#8216;": "‘",
  "&#8217;": "’",
  "&#8220;": "“",
  "&#8221;": "”",
  "&#8230;": "…",
  "&#038;": "&",
  "&#39;": "’",
};

const cleanInlineHtml = (text) => {
  let t = text;
  // Drop any data-* attribute, anywhere in any tag.
  t = t.replace(/\s+data-[\w-]+="[^"]*"/g, "");
  // Drop class="caption" from <a> only.
  t = t.replace(/(<a\b[^>]*?)\s+class="caption"/g, "$1");
  // Drop title="..." from <a> only.
  t = t.replace(/(<a\b[^>]*?)\s+title="[^"]*"/g, "$1");
  // Decode the few decorative entities WordPress's exporter emits.
  for (const [from, to] of Object.entries(ENTITY_MAP)) {
    t = t.split(from).join(to);
  }
  return t;
};

// Inline markdownification — applied across the whole text, not just within
// a converted line. Safe because our patterns require no attributes.
const inlineToMarkdown = (text) => {
  let t = text;
  t = t.replace(/<strong>([^<]+)<\/strong>/g, "**$1**");
  t = t.replace(/<em>([^<]+)<\/em>/g, "*$1*");
  t = t.replace(/<a href="([^"]+)">([^<]+)<\/a>/g, "[$2]($1)");
  return t;
};

const isCleanInline = (text) => {
  const stripped = text
    .replace(/<strong>([^<]*)<\/strong>/g, "$1")
    .replace(/<em>([^<]*)<\/em>/g, "$1")
    .replace(/<a href="[^"]*">([^<]*)<\/a>/g, "$1")
    .replace(/\*\*[^*]+\*\*/g, "x")
    .replace(/\*[^*]+\*/g, "x")
    .replace(/\[[^\]]+\]\([^)]+\)/g, "x");
  return !/<|>/.test(stripped);
};

// Convert <p>x</p> → x and <h\d>x</h\d> → ###... per line, when content is
// already clean inline (no remaining HTML). Skip "spacer" paragraphs whose
// only content is &nbsp; — those depend on the surrounding HTML block to
// render and shouldn't be unwrapped.
const isSpacer = (s) => /^(?:&nbsp;|\s)*$/.test(s);

const lineConvert = (lines) => {
  const out = [];
  let inHtmlBlock = 0;
  let lastEmitWasListItem = false;
  const ensureBlank = () => {
    if (out.length > 0 && out[out.length - 1] !== "") out.push("");
  };
  for (const line of lines) {
    const trimmed = line.trim();

    // Track div/figure/table/aside/section/article nesting so we don't
    // unwrap <p>...</p> that's inside a raw-HTML block.
    const opens = (trimmed.match(/<(div|figure|table|aside|section|article)\b[^>]*?(?<!\/)>/g) || []).length;
    const closes = (trimmed.match(/<\/(div|figure|table|aside|section|article)>/g) || []).length;
    const wasInBlock = inHtmlBlock > 0;
    inHtmlBlock += opens - closes;

    if (wasInBlock || inHtmlBlock > 0) {
      out.push(line);
      lastEmitWasListItem = false;
      continue;
    }

    const cleanH = trimmed.match(/^<(h[1-6])>(.+)<\/\1>$/);
    if (cleanH && isCleanInline(cleanH[2]) && !cleanH[2].includes("<br")) {
      const level = Number.parseInt(cleanH[1].substring(1), 10);
      ensureBlank();
      out.push(`${"#".repeat(level)} ${cleanH[2]}`);
      out.push("");
      lastEmitWasListItem = false;
      continue;
    }

    const cleanP = trimmed.match(/^<p>(.+)<\/p>$/);
    if (cleanP && isCleanInline(cleanP[1]) && !isSpacer(cleanP[1])) {
      ensureBlank();
      out.push(cleanP[1]);
      out.push("");
      lastEmitWasListItem = false;
      continue;
    }

    const cleanLi = trimmed.match(/^<li>(.+)<\/li>$/);
    if (cleanLi && isCleanInline(cleanLi[1])) {
      if (!lastEmitWasListItem) ensureBlank();
      out.push(`- ${cleanLi[1]}`);
      lastEmitWasListItem = true;
      continue;
    }

    if (trimmed === "<ul>" || trimmed === "</ul>") continue;

    if (lastEmitWasListItem) ensureBlank();
    out.push(line);
    lastEmitWasListItem = false;
  }
  return out;
};

const cleanDescription = (raw) => {
  let text = cleanInlineHtml(raw);
  text = inlineToMarkdown(text);
  let lines = text.split("\n");
  lines = lineConvert(lines);
  // Collapse runs of 3+ blank lines down to 2.
  return lines.join("\n").replace(/\n{3,}/g, "\n\n");
};

const splitFile = (src) => {
  const m = src.match(/^---\n([\s\S]*?)\n---\n?([\s\S]*)$/);
  if (!m) throw new Error("no frontmatter");
  return { frontmatter: m[1], body: m[2] };
};

// description: |\n  line1\n  line2\n... up to a non-indented line
const extractDescription = (frontmatter) => {
  const lines = frontmatter.split("\n");
  for (let i = 0; i < lines.length; i++) {
    if (lines[i] === "description: |") {
      const startIdx = i + 1;
      let endIdx = startIdx;
      while (
        endIdx < lines.length &&
        (lines[endIdx] === "" || lines[endIdx].startsWith("  "))
      ) {
        endIdx++;
      }
      const block = lines.slice(startIdx, endIdx);
      const dedented = block.map((l) => l.replace(/^  /, "")).join("\n");
      return { startIdx, endIdx, dedented };
    }
  }
  return null;
};

const reindent = (text) =>
  text
    .split("\n")
    .map((l) => (l === "" ? "" : `  ${l}`))
    .join("\n");

const convert = (filename) => {
  const src = readFileSync(filename, "utf8");
  const { frontmatter, body } = splitFile(src);
  const found = extractDescription(frontmatter);
  if (!found) {
    console.log(`skip ${filename} (no description)`);
    return;
  }
  const cleaned = cleanDescription(found.dedented);
  const reindented = reindent(cleaned).replace(/\n+$/, "");
  const fmLines = frontmatter.split("\n");
  const newFm = [
    ...fmLines.slice(0, found.startIdx),
    ...reindented.split("\n"),
    ...fmLines.slice(found.endIdx),
  ].join("\n");
  writeFileSync(filename, `---\n${newFm}\n---\n${body}`);
  console.log(`cleaned ${filename}`);
};

for (const dir of DIRS) {
  for (const f of readdirSync(dir)) {
    if (f.endsWith(".html")) convert(join(dir, f));
  }
}

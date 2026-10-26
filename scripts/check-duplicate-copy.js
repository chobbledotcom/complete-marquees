#!/usr/bin/env bun

/**
 * Flags sentences of body copy that appear word-for-word on more than one page.
 *
 * Readers work through several location and service pages in a session, so a
 * sentence they have already read on another page reads as boilerplate. This
 * script is the only tool that sees the frontmatter prose blocks: location
 * pages keep their copy inside `description: |-` with an empty template body,
 * and service and root pages keep theirs inside `blocks[].content`, so jscpd
 * (bun run cpd) never reads it - its markdown mode only sees what sits after
 * the frontmatter, and these pages have nothing there. Long near-identical
 * passages across pages are therefore not caught by either tool. This reads
 * every prose sentence from 40 characters up, block scalars and inline values
 * alike, and flags any word-for-word repeat.
 *
 * Only prose is considered. Frontmatter scalars (meta_description, titles,
 * alt text) and list/table scaffolding are skipped, because nobody reads two
 * of those side by side. Reviews and the testimonials page are excluded by
 * default: customer quotes stay verbatim by design. There is no allowlist for
 * the default scan: a genuinely shared line, even a standard inclusion, still
 * needs rewording so every page reads as its own.
 */

import { readdirSync, readFileSync, statSync } from "node:fs";
import { relative } from "node:path";
import { path } from "./utils.js";

// Location and service pages are the ones a visitor genuinely compares in a
// session, so repetition between them is what reads as boilerplate. The
// testimonials page quotes customers verbatim and is only checked under --all.
const DEFAULT_DIRS = ["locations", "service-pages", "pages"];
const DEFAULT_ROOT_FILES = [
  "index.html",
  "packages.md",
  "sizes-prices.md",
  "contact-us.md",
];
const ALL_EXTRA_ROOT_FILES = ["client-testimonials.md"];

// Block-scalar keys whose bodies are prose a visitor actually reads.
// Testimonial/quote bodies are excluded: quotes stay verbatim by design.
const PROSE_KEYS =
  /^(\s*)-?\s*(content|left_content|right_content|intro_content|intro|intro_body|closing_body|description|answer):\s*[|>][-+]?\s*$/;

const ANY_KEY = /^(\s*)-?\s*[a-z_]+:/;

const MIN_SENTENCE_LENGTH = 40;
// Effectively no upper bound: a duplicated sentence is a finding whatever
// its length, and the ceiling only guards against pathological unpunctuated
// lines being treated as one giant sentence.
const MAX_SENTENCE_LENGTH = 2000;

const indentOf = (line) => line.length - line.trimStart().length;

const isBlank = (line) => line.trim().length === 0;

const blockKeyIndent = (line) => {
  const match = PROSE_KEYS.exec(line);
  return match ? match[1].length : null;
};

/**
 * Inline value of a prose key on the same line, quotes stripped. FAQ answers
 * are usually written inline rather than as block scalars, and they are read
 * side by side just as much, so they belong in the scan too.
 */
const INLINE_PROSE_KEY =
  /^(?:\s*)-?\s*(content|left_content|right_content|intro_content|intro|intro_body|closing_body|description|answer):\s*(.+?)\s*$/;

const inlineProseValue = (line) => {
  const match = INLINE_PROSE_KEY.exec(line);
  if (!match) return null;
  const value = match[2].replace(/^['"]|['"]$/g, "").trim();
  return value.length > 0 ? value : null;
};

/** Lines belonging to prose block scalars, with their scaffolding removed. */
const proseLines = (content) => {
  const collected = [];
  let openIndent = null;

  for (const line of content.split("\n")) {
    const keyIndent = blockKeyIndent(line);
    if (keyIndent !== null) {
      openIndent = keyIndent;
      continue;
    }
    if (openIndent === null) {
      const inline = inlineProseValue(line);
      if (inline !== null) collected.push(inline);
      continue;
    }
    if (isBlank(line) || indentOf(line) > openIndent) {
      collected.push(line.trim());
      continue;
    }
    if (ANY_KEY.test(line)) openIndent = null;
  }

  return collected;
};

/** Drop markdown that is not prose: headings, bullets, tables, blockquotes. */
const isProse = (line) => !/^([#>|*]|-\s|\d+\.\s)/.test(line);

const sentencesIn = (content) => {
  const body = proseLines(content).filter(isProse).join(" ");
  return body
    .split(/(?<=[.!?])\s+/)
    .map((sentence) => sentence.replace(/\s+/g, " ").trim())
    .filter(
      (sentence) =>
        sentence.length >= MIN_SENTENCE_LENGTH &&
        sentence.length <= MAX_SENTENCE_LENGTH,
    );
};

/** Content sources: directories (scanned for .md/.html) plus explicit files. */
const contentFiles = (dirs, rootFiles) => {
  const dirFiles = dirs.flatMap((dir) => {
    const dirPath = path(dir);
    return readdirSync(dirPath)
      .filter((filename) => /\.(md|html)$/.test(filename))
      .map((filename) => path(dir, filename));
  });
  const explicit = rootFiles
    .map((filename) => path(filename))
    .filter((filePath) => statSync(filePath).isFile());
  return [...dirFiles, ...explicit];
};

const collectSentences = (dirs, rootFiles) => {
  const seen = new Map();

  for (const filePath of contentFiles(dirs, rootFiles)) {
    const label = relative(path(), filePath);
    for (const sentence of sentencesIn(readFileSync(filePath, "utf8"))) {
      const files = seen.get(sentence) ?? new Set();
      files.add(label);
      seen.set(sentence, files);
    }
  }

  return seen;
};

/** Entries are { text, why } so the reason for each repeat stays on record. */
const readAllowlist = () => {
  try {
    const raw = readFileSync(path(".duplicate-copy.json"), "utf8");
    const entries = JSON.parse(raw).allow ?? [];
    return new Set(entries.map((entry) => entry.text ?? entry));
  } catch {
    return new Set();
  }
};

const report = (duplicates) => {
  for (const [sentence, files] of duplicates) {
    console.log(`\n  "${sentence}"`);
    for (const file of files) console.log(`    ${file}`);
  }
};

const main = () => {
  const dirs = DEFAULT_DIRS;
  const rootFiles = process.argv.includes("--all")
    ? [...DEFAULT_ROOT_FILES, ...ALL_EXTRA_ROOT_FILES]
    : DEFAULT_ROOT_FILES;
  const allowed = readAllowlist();
  const duplicates = [...collectSentences(dirs, rootFiles)]
    .filter(([sentence, files]) => files.size > 1 && !allowed.has(sentence))
    .sort((a, b) => b[1].size - a[1].size);

  if (duplicates.length === 0) {
    console.log("No unexpected duplicate copy found.");
    return;
  }

  console.log(
    `${duplicates.length} sentence(s) appear word-for-word on more than one page:`,
  );
  report(duplicates);
  console.log(
    "\nReword them so each page says it in its own way; there is no allowlist any more.",
  );
  process.exit(1);
};

export const collectDuplicates = (dirs, rootFiles = []) =>
  [...collectSentences(dirs, rootFiles)]
    .filter(([, files]) => files.size > 1)
    .map(([sentence, files]) => ({ text: sentence, files: [...files] }))
    .sort((a, b) => b.files.length - a.files.length);

if (import.meta.main) {
  main();
}

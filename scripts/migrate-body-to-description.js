#!/usr/bin/env bun
/**
 * Convert clean HTML body sections of locations/*.html and service-pages/*.html
 * into markdown stored in a `description` frontmatter field.
 *
 * Conservative: only converts clean structural tags. Preserves all HTML with
 * attributes, Liquid tags, divs/figures/iframes, etc.
 */
import { readdirSync, readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const DIRS = ["locations", "service-pages"];

const splitFile = (src) => {
  const m = src.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  if (!m) throw new Error("no frontmatter");
  return { frontmatter: m[1], body: m[2] };
};

// Inline conversion: only handles plain <strong>, <em>, <a href="url"> with
// NO other attributes. If anything looks suspicious, leave the inline as HTML.
const convertInline = (text) => {
  return text
    .replace(/<strong>([^<]*)<\/strong>/g, "**$1**")
    .replace(/<em>([^<]*)<\/em>/g, "*$1*")
    .replace(/<a href="([^"]*)">([^<]*)<\/a>/g, "[$2]($1)");
};

// Returns true if the inline content uses only characters or our 3 allowed
// inline tags with simple text.
const isCleanInline = (text) => {
  const stripped = text
    .replace(/<strong>([^<]*)<\/strong>/g, "$1")
    .replace(/<em>([^<]*)<\/em>/g, "$1")
    .replace(/<a href="[^"]*">([^<]*)<\/a>/g, "$1");
  return !/<|>/.test(stripped);
};

// Block-level container tags whose content we leave untouched. If a line
// opens one without closing on the same line, gather the whole block.
const BLOCK_TAGS = [
  "div",
  "figure",
  "iframe",
  "style",
  "script",
  "aside",
  "section",
  "article",
  "table",
];

const opensBlock = (line) => {
  for (const tag of BLOCK_TAGS) {
    const openRe = new RegExp(`<${tag}\\b[^>]*?(?<!/)>`);
    const closeRe = new RegExp(`</${tag}>`);
    if (openRe.test(line)) {
      const opens = (line.match(new RegExp(`<${tag}\\b[^>]*?(?<!/)>`, "g")) || []).length;
      const closes = (line.match(new RegExp(`</${tag}>`, "g")) || []).length;
      if (opens > closes) return tag;
    }
  }
  return null;
};

const closesBlock = (line, tag) => {
  const opens = (line.match(new RegExp(`<${tag}\\b[^>]*?(?<!/)>`, "g")) || []).length;
  const closes = (line.match(new RegExp(`</${tag}>`, "g")) || []).length;
  return closes > opens;
};

const convertBody = (body) => {
  const lines = body.split("\n").map((l) => l.trim());
  const out = [];
  const ensureBlank = () => {
    if (out.length > 0 && out[out.length - 1] !== "") out.push("");
  };
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];

    if (line === "") {
      out.push("");
      i++;
      continue;
    }

    // Block container: gather until matched close, preserve verbatim
    const blockTag = opensBlock(line);
    if (blockTag) {
      ensureBlank();
      out.push(line);
      let depth = 1;
      let j = i + 1;
      while (j < lines.length && depth > 0) {
        out.push(lines[j]);
        const inner = opensBlock(lines[j]);
        if (inner === blockTag) depth++;
        if (closesBlock(lines[j], blockTag)) depth--;
        j++;
      }
      out.push("");
      i = j;
      continue;
    }

    if (/^\{[%{]/.test(line)) {
      ensureBlank();
      out.push(line);
      out.push("");
      i++;
      continue;
    }

    const cleanH = line.match(/^<(h[1-6])>(.+)<\/\1>$/);
    if (cleanH && isCleanInline(cleanH[2]) && !cleanH[2].includes("<br")) {
      const level = Number.parseInt(cleanH[1].substring(1), 10);
      ensureBlank();
      out.push(`${"#".repeat(level)} ${convertInline(cleanH[2])}`);
      out.push("");
      i++;
      continue;
    }

    const cleanP = line.match(/^<p>(.+)<\/p>$/);
    if (cleanP && isCleanInline(cleanP[1])) {
      ensureBlank();
      out.push(convertInline(cleanP[1]));
      out.push("");
      i++;
      continue;
    }

    if (line === "<ul>") {
      let j = i + 1;
      const items = [];
      let canConvert = true;
      while (j < lines.length && lines[j] !== "</ul>") {
        const liMatch = lines[j].match(/^<li>(.+)<\/li>$/);
        if (!liMatch || !isCleanInline(liMatch[1])) {
          canConvert = false;
          break;
        }
        items.push(liMatch[1]);
        j++;
      }
      if (canConvert && lines[j] === "</ul>") {
        ensureBlank();
        for (const item of items) out.push(`- ${convertInline(item)}`);
        out.push("");
        i = j + 1;
        continue;
      }
    }

    out.push(line);
    i++;
  }

  return out.join("\n").replace(/\n{3,}/g, "\n\n").trim();
};

// Render markdown into YAML literal block scalar.
const yamlBlock = (markdown) => {
  if (markdown === "") return '""';
  const indented = markdown
    .split("\n")
    .map((l) => (l === "" ? "" : `  ${l}`))
    .join("\n");
  return `|\n${indented}`;
};

const convert = (filename) => {
  const src = readFileSync(filename, "utf8");
  const { frontmatter, body } = splitFile(src);
  const markdown = convertBody(body);
  // Add description to frontmatter, rename existing description -> meta_description
  const renamedFm = frontmatter.replace(
    /^description:\s/m,
    "meta_description: ",
  ).replace(
    /^description_html:\s/m,
    "meta_description_html: ",
  );
  const newFm = `${renamedFm}\ndescription: ${yamlBlock(markdown)}`;
  writeFileSync(filename, `---\n${newFm}\n---\n`);
  console.log(`converted ${filename}`);
};

for (const dir of DIRS) {
  for (const f of readdirSync(dir)) {
    if (f.endsWith(".html")) convert(join(dir, f));
  }
}

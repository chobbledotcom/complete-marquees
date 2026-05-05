#!/usr/bin/env bun
import { readdirSync, readFileSync, writeFileSync, unlinkSync } from "node:fs";
import { join } from "node:path";

const SRC_DIR = "Complete-Marquees";
const DEST_DIR = "locations";

const extract = (re, src) => {
  const m = src.match(re);
  return m ? m[1] : null;
};

const extractBody = (src) => {
  const startIdx = src.indexOf('{% include "breadcrumbs.html" %}');
  if (startIdx === -1) throw new Error("no breadcrumbs include");
  const afterStart = src.indexOf("\n", startIdx) + 1;
  const endIdx = src.indexOf('        </div><!-- ', afterStart);
  if (endIdx === -1) throw new Error("no closing div");
  return src.slice(afterStart, endIdx);
};

const extractFromJsonLd = (src, key) => {
  const re = new RegExp(`"${key}": "([^"]*)"`);
  return extract(re, src);
};

const extractBreadcrumbName = (src) => {
  const m = src.match(/"position": 2,\s*"name": "([^"]*)"/);
  return m ? m[1] : null;
};

const extractMapEmbed = (src) => {
  const m = src.match(/<iframe src="(https:\/\/www\.google\.com\/maps[^"]+)"\s+allowfullscreen/);
  return m ? m[1] : null;
};

const yamlEscape = (s) => `"${s.replace(/\\/g, "\\\\").replace(/"/g, '\\"')}"`;

const convert = (filename) => {
  const srcPath = join(SRC_DIR, filename);
  const slug = filename.replace(/\.html$/, "");
  const src = readFileSync(srcPath, "utf8");

  const titleHtml = extract(/<title>([^<]*)<\/title>/, src);
  const title = extract(/"@graph": \[\{[\s\S]*?"name": "([^"]*)"/, src) || titleHtml;
  const descriptionHtml = extract(/<meta name="description" content="([^"]*)"/, src);
  const description = extractFromJsonLd(src, "description") || descriptionHtml;
  const ogImage = extract(/<meta property="og:image" content="([^"]*)"/, src);
  const thumbnailUrl = extractFromJsonLd(src, "thumbnailUrl");
  const dateModified = extract(/<meta property="article:modified_time" content="([^"]*)"/, src);
  const readingTime = extract(/<meta name="twitter:data1" content="([^"]*)"/, src);
  const datePublished = extractFromJsonLd(src, "datePublished");
  const breadcrumbName = extractBreadcrumbName(src);
  const mapEmbed = extractMapEmbed(src);
  const body = extractBody(src);

  const frontmatterLines = [
    "---",
    `permalink: "/Complete-Marquees/${slug}/"`,
    "layout: location",
    `title: ${yamlEscape(title)}`,
    ...(titleHtml !== title ? [`title_html: ${yamlEscape(titleHtml)}`] : []),
    `description: ${yamlEscape(description)}`,
    ...(descriptionHtml !== description ? [`description_html: ${yamlEscape(descriptionHtml)}`] : []),
    `og_image: ${yamlEscape(ogImage)}`,
    `thumbnail_url: ${yamlEscape(thumbnailUrl)}`,
    `date_published: ${yamlEscape(datePublished)}`,
    `date_modified: ${yamlEscape(dateModified)}`,
    `reading_time: ${yamlEscape(readingTime)}`,
    `breadcrumb_name: ${yamlEscape(breadcrumbName)}`,
  ];
  if (mapEmbed) frontmatterLines.push(`map_embed: ${yamlEscape(mapEmbed)}`);
  frontmatterLines.push("---");

  const out = `${frontmatterLines.join("\n")}\n${body}`;
  writeFileSync(join(DEST_DIR, filename), out);
  unlinkSync(srcPath);
  console.log(`migrated ${slug}`);
};

const files = readdirSync(SRC_DIR).filter((f) => f.endsWith(".html"));
for (const f of files) convert(f);
console.log(`done: ${files.length} files`);

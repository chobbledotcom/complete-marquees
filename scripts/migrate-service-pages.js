#!/usr/bin/env bun
import { readFileSync, writeFileSync } from "node:fs";

const PAGES = process.argv.slice(2).length
  ? process.argv.slice(2)
  : [
      "wedding-marquees.html",
      "party-marquees.html",
      "corporate-event-marquees.html",
      "special-events.html",
      "faqs-complete-marquees.html",
      "about-us-marquee-hire-complete-marquees.html",
      "capri-marquee-hire.html",
      "equipment-marquee-hire.html",
      "privacy.html",
    ];

const extract = (re, src) => {
  const m = src.match(re);
  return m ? m[1] : null;
};

const extractFromJsonLd = (src, key) => {
  const re = new RegExp(`"${key}": "([^"]*)"`);
  return extract(re, src);
};

const extractBreadcrumbName = (src) => {
  const m = src.match(/"position": 2,\s*"name": "([^"]*)"/);
  return m ? m[1] : null;
};

const extractBodyClass = (src) =>
  extract(/<body class="([^"]*)"/, src);

const extractRedirects = (src) => {
  const fmMatch = src.match(/^---\n([\s\S]*?)\n---/);
  if (!fmMatch) return [];
  const m = fmMatch[1].match(/redirect_from:\s*\n((?:\s+-\s.+\n?)+)/);
  if (!m) return [];
  return m[1].trim().split("\n").map((l) => l.trim().replace(/^-\s+/, ""));
};

const extractBody = (src) => {
  const startMarker = '{% include "page-main-open.html" %}';
  const endMarker = '{% include "page-sidebar-close.html" %}';
  const startIdx = src.indexOf(startMarker);
  if (startIdx === -1) throw new Error("no page-main-open");
  const afterStart = src.indexOf("\n", startIdx) + 1;
  const endIdx = src.indexOf(endMarker, afterStart);
  if (endIdx === -1) throw new Error("no page-sidebar-close");
  return src.slice(afterStart, endIdx);
};

const yamlEscape = (s) => s == null ? '""' : `"${s.replace(/\\/g, "\\\\").replace(/"/g, '\\"')}"`;

const convert = (filename) => {
  const src = readFileSync(filename, "utf8");

  const titleHtml = extract(/<title>([^<]*)<\/title>/, src);
  const title = extractFromJsonLd(src, "name") || titleHtml;
  const descriptionHtml = extract(/<meta name="description" content="([^"]*)"/, src);
  const description = extractFromJsonLd(src, "description") || descriptionHtml;
  const ogImage = extract(/<meta property="og:image" content="([^"]*)"/, src);
  const thumbnailUrl = extractFromJsonLd(src, "thumbnailUrl");
  const dateModified = extract(/<meta property="article:modified_time" content="([^"]*)"/, src);
  const readingTime = extract(/<meta name="twitter:data1" content="([^"]*)"/, src);
  const datePublished = extractFromJsonLd(src, "datePublished");
  const breadcrumbName = extractBreadcrumbName(src);
  const bodyClass = extractBodyClass(src);
  const permalink = extract(/permalink:\s*"([^"]+)"/, src);
  const redirects = extractRedirects(src);
  const body = extractBody(src);

  const ogImageWidth = extract(/<meta property="og:image:width" content="([^"]*)"/, src);
  const ogImageHeight = extract(/<meta property="og:image:height" content="([^"]*)"/, src);
  const ogImageType = extract(/<meta property="og:image:type" content="([^"]*)"/, src);
  const imageObjBlock = src.match(/"ImageObject"[\s\S]*?(?=\}, \{)/);
  const imageWidth = imageObjBlock ? extract(/"width": (\d+)/, imageObjBlock[0]) : null;
  const imageHeight = imageObjBlock ? extract(/"height": (\d+)/, imageObjBlock[0]) : null;
  const imageCaption = imageObjBlock ? extract(/"caption": "([^"]*)"/, imageObjBlock[0]) : null;
  const navCurrent = extract(/site-header\.html",\s*current:\s*"([^"]*)"/, src);

  const fmLines = [
    "---",
    `permalink: ${yamlEscape(permalink)}`,
    "layout: yoast-page",
    `title: ${yamlEscape(title)}`,
    ...(titleHtml !== title ? [`title_html: ${yamlEscape(titleHtml)}`] : []),
    `description: ${yamlEscape(description)}`,
    ...(descriptionHtml !== description ? [`description_html: ${yamlEscape(descriptionHtml)}`] : []),
    `breadcrumb_name: ${yamlEscape(breadcrumbName)}`,
    `og_image: ${yamlEscape(ogImage)}`,
    `thumbnail_url: ${yamlEscape(thumbnailUrl)}`,
    `date_published: ${yamlEscape(datePublished)}`,
    `date_modified: ${yamlEscape(dateModified)}`,
    `reading_time: ${yamlEscape(readingTime)}`,
    `body_class: ${yamlEscape(bodyClass)}`,
    ...(navCurrent ? [`nav_current: ${yamlEscape(navCurrent)}`] : []),
    ...(ogImageWidth ? [`og_image_width: ${ogImageWidth}`] : []),
    ...(ogImageHeight ? [`og_image_height: ${ogImageHeight}`] : []),
    ...(ogImageType ? [`og_image_type: ${yamlEscape(ogImageType)}`] : []),
    ...(imageCaption ? [
      `image_caption: ${yamlEscape(imageCaption)}`,
      `image_width: ${imageWidth}`,
      `image_height: ${imageHeight}`,
    ] : []),
  ];
  if (redirects.length) {
    fmLines.push("redirect_from:");
    for (const r of redirects) fmLines.push(`  - ${r}`);
  }
  fmLines.push("---");

  const out = `${fmLines.join("\n")}\n${body}`;
  writeFileSync(filename, out);
  console.log(`migrated ${filename}`);
};

for (const f of PAGES) convert(f);
console.log(`done: ${PAGES.length} files`);

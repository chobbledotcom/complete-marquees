#!/usr/bin/env bun
import { readdirSync, readFileSync, statSync } from "node:fs";
import { join, relative } from "node:path";

const A = process.argv[2] || ".baseline-site";
const B = process.argv[3] || "_site";

const SKIP_DIRS = ["pagefind", "api"];
const SKIP_RE = [
  /\.bundle\.js$/,
  /\.bundle\.css$/,
  /\/assets\/js\//,
  /\/assets\/css\//,
];

const walk = (dir, base = dir, out = []) => {
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    if (SKIP_DIRS.includes(entry.name)) continue;
    const full = join(dir, entry.name);
    if (entry.isDirectory()) walk(full, base, out);
    else out.push(relative(base, full));
  }
  return out;
};

const normalise = (text) => {
  return text
    .replace(/\?cached=\d+/g, "?cached=X")
    .replace(/data-decrypt-key="[^"]+"/g, 'data-decrypt-key="X"')
    .replace(/href="#[A-Za-z0-9_-]+"\s+class="email"\s+data-decrypt-link="">[A-Za-z0-9_-]+/g,
      'href="#X" class="email" data-decrypt-link="">X')
    .replace(/href="#[A-Za-z0-9_-]+"\s+data-decrypt-link="">[A-Za-z0-9_-]+/g,
      'href="#X" data-decrypt-link="">X')
    .replace(/\s+/g, " ")
    .trim();
};

const aFiles = new Set(walk(A));
const bFiles = new Set(walk(B));

const only_a = [...aFiles].filter((f) => !bFiles.has(f)).sort();
const only_b = [...bFiles].filter((f) => !aFiles.has(f)).sort();
const shared = [...aFiles].filter((f) => bFiles.has(f)).sort();

const skipFile = (f) => SKIP_RE.some((re) => re.test(f));

let differing = 0;
const diffs = [];
for (const f of shared) {
  if (skipFile(f)) continue;
  const aPath = join(A, f);
  const bPath = join(B, f);
  if (statSync(aPath).isDirectory() || statSync(bPath).isDirectory()) continue;
  const aText = readFileSync(aPath, "utf8");
  const bText = readFileSync(bPath, "utf8");
  if (aText === bText) continue;
  const aN = normalise(aText);
  const bN = normalise(bText);
  if (aN === bN) continue;
  differing++;
  diffs.push(f);
}

console.log(`only in ${A}:`, only_a.length);
for (const f of only_a) console.log(`  - ${f}`);
console.log(`only in ${B}:`, only_b.length);
for (const f of only_b) console.log(`  + ${f}`);
console.log(`differing (after normalising):`, differing);
for (const f of diffs) console.log(`  ! ${f}`);

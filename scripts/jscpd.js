#!/usr/bin/env bun

/**
 * Keeps jscpd's binary at `.bin/jscpd`, downloading and verifying it on
 * first use. Ported from tickets' scripts/jscpd/install.ts.
 *
 * jscpd v5 is a Rust binary shipped through npm. The platform package bunx
 * picks on glibc Linux (jscpd-linux-x64-gnu) is dynamically linked, so a
 * NixOS machine cannot start it. The musl package is a fully static binary
 * that runs anywhere, so this module fetches that one and pins its checksum.
 */

import { createHash } from "node:crypto";
import { chmodSync, existsSync, mkdirSync, readFileSync, renameSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

export const JSCPD_VERSION = "5.4.2";
export const JSCPD_URL = `https://registry.npmjs.org/jscpd-linux-x64-musl/-/jscpd-linux-x64-musl-${JSCPD_VERSION}.tgz`;
export const JSCPD_SHA256 = "819a9522a7d0ed2e89861f38bc0001355a0c205f360607e209a0d5a51cf79dc7";

const REPO_ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
export const JSCPD_BIN_PATH = join(REPO_ROOT, ".bin", "jscpd");

const decoder = new TextDecoder();
const decode = (bytes) => decoder.decode(bytes).replace(/\0.*$/, "");

const sha256Hex = (bytes) => createHash("sha256").update(bytes).digest("hex");

/** The `package/bin/jscpd` entry's bytes out of an uncompressed ustar
 * buffer: 512-byte headers, name at 0, octal size at 124, data after. */
export const jscpdEntryFromTar = (tar) => {
  for (let at = 0; at + 512 <= tar.length; ) {
    const header = tar.subarray(at, at + 512);
    const name = decode(header.subarray(0, 100));
    const size = parseInt(decode(header.subarray(124, 136)).trim() || "0", 8);
    const dataAt = at + 512;
    if (name === "package/bin/jscpd") {
      return tar.subarray(dataAt, dataAt + size);
    }
    at = dataAt + Math.ceil(size / 512) * 512;
  }
  throw new Error("The jscpd tarball holds no package/bin/jscpd entry");
};

const installBinary = async () => {
  const response = await fetch(JSCPD_URL);
  if (!response.ok) {
    throw new Error(`Failed to download jscpd: HTTP ${response.status}`);
  }
  const tar = Bun.gunzipSync(new Uint8Array(await response.arrayBuffer()));
  const binary = jscpdEntryFromTar(tar);
  if (sha256Hex(binary) !== JSCPD_SHA256) {
    throw new Error("jscpd binary checksum mismatch");
  }
  mkdirSync(dirname(JSCPD_BIN_PATH), { recursive: true });
  const staged = `${JSCPD_BIN_PATH}.${process.pid}.tmp`;
  writeFileSync(staged, binary);
  chmodSync(staged, 0o700);
  renameSync(staged, JSCPD_BIN_PATH);
};

/** The path of a usable jscpd binary, installing one when it is missing or
 * fails its pinned checksum. */
export const ensureJscpd = async () => {
  const usable =
    existsSync(JSCPD_BIN_PATH) &&
    sha256Hex(readFileSync(JSCPD_BIN_PATH)) === JSCPD_SHA256;
  if (!usable) {
    await installBinary();
  }
  return JSCPD_BIN_PATH;
};

const isMainModule = process.argv[1]
  ? import.meta.url === pathToFileURL(process.argv[1]).href
  : false;

if (isMainModule) {
  console.log(await ensureJscpd());
}

import { createHash } from "node:crypto";
import { existsSync } from "node:fs";
import { mkdir, readFile, readdir, rm, writeFile } from "node:fs/promises";
import path from "node:path";
import { Readable } from "node:stream";
import { fileURLToPath } from "node:url";

const moduleRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../../..");
const cwdRoot = path.resolve(process.cwd());
const repoRoot = existsSync(path.join(cwdRoot, "netlify.toml")) || existsSync(path.join(cwdRoot, "data"))
  ? cwdRoot
  : moduleRoot;

function shouldUseNetlifyBlobs() {
  return Boolean(
    process.env.NETLIFY === "true" ||
    process.env.NETLIFY_DEV === "true" ||
    process.env.NETLIFY_SITE_ID ||
    process.env.NETLIFY_BLOBS_CONTEXT
  );
}

function normalizeKey(key) {
  const normalized = String(key ?? "").replaceAll("\\", "/").replace(/^\/+/, "");
  if (!normalized || normalized.split("/").some((part) => part === ".." || part === "")) {
    throw new Error(`Invalid blob key: ${key}`);
  }
  return normalized;
}

function resolveKey(root, key) {
  const normalized = normalizeKey(key);
  const target = path.resolve(root, ...normalized.split("/"));
  const relative = path.relative(root, target);
  if (relative.startsWith("..") || path.isAbsolute(relative)) {
    throw new Error(`Refusing unsafe blob key: ${key}`);
  }
  return target;
}

async function valueToBuffer(value) {
  if (Buffer.isBuffer(value)) {
    return value;
  }
  if (value instanceof ArrayBuffer) {
    return Buffer.from(value);
  }
  if (ArrayBuffer.isView(value)) {
    return Buffer.from(value.buffer, value.byteOffset, value.byteLength);
  }
  if (value && typeof value.arrayBuffer === "function") {
    return Buffer.from(await value.arrayBuffer());
  }
  return Buffer.from(String(value ?? ""));
}

function bufferToType(buffer, type = "text") {
  switch (type) {
    case "arrayBuffer":
      return buffer.buffer.slice(buffer.byteOffset, buffer.byteOffset + buffer.byteLength);
    case "blob":
      return new Blob([buffer]);
    case "json":
      return JSON.parse(buffer.toString("utf8"));
    case "stream":
      return Readable.toWeb(Readable.from(buffer));
    case "text":
    default:
      return buffer.toString("utf8");
  }
}

async function allDiskKeys(root, dir = root) {
  const entries = await readdir(dir, { withFileTypes: true }).catch(() => []);
  const keys = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      keys.push(...await allDiskKeys(root, fullPath));
      continue;
    }
    if (!entry.isFile() || entry.name.endsWith(".metadata.json")) {
      continue;
    }
    keys.push(path.relative(root, fullPath).split(path.sep).join("/"));
  }

  return keys;
}

function etagForBuffer(buffer) {
  return `"${createHash("sha1").update(buffer).digest("hex")}"`;
}

function createDiskStore(name) {
  const root = path.join(repoRoot, ".netlify", "local-blobs", name);

  return {
    kind: "disk",

    async get(key, options = {}) {
      const filePath = resolveKey(root, key);
      try {
        const buffer = await readFile(filePath);
        return bufferToType(buffer, options.type || "text");
      } catch (error) {
        if (error.code === "ENOENT") {
          return null;
        }
        throw error;
      }
    },

    async getJSON(key) {
      return this.get(key, { type: "json" });
    },

    async getWithMetadata(key, options = {}) {
      const filePath = resolveKey(root, key);
      try {
        const buffer = await readFile(filePath);
        const metadataPath = resolveKey(root, `${normalizeKey(key)}.metadata.json`);
        let metadata = {};
        try {
          metadata = JSON.parse(await readFile(metadataPath, "utf8"));
        } catch {
          metadata = {};
        }
        return {
          data: bufferToType(buffer, options.type || "text"),
          etag: etagForBuffer(buffer),
          metadata
        };
      } catch (error) {
        if (error.code === "ENOENT") {
          return null;
        }
        throw error;
      }
    },

    async set(key, value, options = {}) {
      const normalized = normalizeKey(key);
      const filePath = resolveKey(root, normalized);
      const buffer = await valueToBuffer(value);
      await mkdir(path.dirname(filePath), { recursive: true });
      await writeFile(filePath, buffer);

      if (options.metadata) {
        const metadataPath = resolveKey(root, `${normalized}.metadata.json`);
        await mkdir(path.dirname(metadataPath), { recursive: true });
        await writeFile(metadataPath, JSON.stringify(options.metadata, null, 2));
      }

      return { modified: true, etag: etagForBuffer(buffer) };
    },

    async setJSON(key, value, options = {}) {
      return this.set(key, JSON.stringify(value, null, 2), {
        ...options,
        metadata: {
          contentType: "application/json; charset=utf-8",
          ...(options.metadata || {})
        }
      });
    },

    async delete(key) {
      const normalized = normalizeKey(key);
      await rm(resolveKey(root, normalized), { force: true });
      await rm(resolveKey(root, `${normalized}.metadata.json`), { force: true });
    },

    async list(options = {}) {
      const keys = await allDiskKeys(root);
      const prefix = options.prefix
        ? normalizeKey(options.prefix).replace(/\/?$/, "/")
        : "";

      const blobs = [];
      const directories = new Set();

      for (const key of keys) {
        if (prefix && !key.startsWith(prefix)) {
          continue;
        }

        if (options.directories) {
          const rest = prefix ? key.slice(prefix.length) : key;
          const parts = rest.split("/");
          if (parts.length > 1) {
            directories.add(parts[0]);
            continue;
          }
        }

        const buffer = await readFile(resolveKey(root, key));
        blobs.push({ key, etag: etagForBuffer(buffer) });
      }

      return { blobs, directories: [...directories].sort() };
    }
  };
}

async function createNetlifyStore(name) {
  if (!shouldUseNetlifyBlobs()) {
    return null;
  }

  try {
    const { getStore } = await import("@netlify/blobs");
    const store = getStore(name);

    return {
      kind: "netlify",
      get: (key, options = {}) => store.get(key, options),
      getJSON: (key) => store.get(key, { type: "json" }),
      getWithMetadata: (key, options = {}) => store.getWithMetadata(key, options),
      set: (key, value, options = {}) => store.set(key, value, options),
      setJSON: (key, value, options = {}) => store.setJSON(key, value, options),
      delete: (key) => store.delete(key),
      list: (options = {}) => store.list(options)
    };
  } catch {
    return null;
  }
}

export async function getStoreAdapter(name) {
  return (await createNetlifyStore(name)) || createDiskStore(name);
}

export { repoRoot };

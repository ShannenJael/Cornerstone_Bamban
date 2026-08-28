import { readFile } from "node:fs/promises";
import path from "node:path";
import { getStoreAdapter, repoRoot } from "./storage.mjs";

export const STATE_STORE = "cornerstone-state";
export const MEDIA_STORE = "cornerstone-media";

const dataRoot = path.join(repoRoot, "data");

function safeDataPath(relativePath) {
  const normalized = String(relativePath ?? "").replaceAll("\\", "/").replace(/^\/+/, "");
  if (!normalized || normalized.split("/").some((part) => part === ".." || part === "")) {
    throw new Error(`Invalid data path: ${relativePath}`);
  }
  const target = path.resolve(dataRoot, ...normalized.split("/"));
  const relative = path.relative(dataRoot, target);
  if (relative.startsWith("..") || path.isAbsolute(relative)) {
    throw new Error(`Refusing unsafe data path: ${relativePath}`);
  }
  return target;
}

export async function readStaticData(relativePath, encoding = null) {
  const target = safeDataPath(relativePath);
  return readFile(target, encoding || undefined);
}

export async function readStaticJSON(relativePath, fallback) {
  try {
    return JSON.parse(await readStaticData(relativePath, "utf8"));
  } catch {
    return fallback;
  }
}

export async function readStateJSON(key, fallback) {
  try {
    const store = await getStoreAdapter(STATE_STORE);
    const value = await store.getJSON(key);
    if (value !== null) {
      return value;
    }
  } catch {
    // Fall back to the static seed file below.
  }

  return readStaticJSON(key, fallback);
}

export async function writeStateJSON(key, value) {
  const store = await getStoreAdapter(STATE_STORE);
  await store.setJSON(key, value, {
    metadata: {
      contentType: "application/json; charset=utf-8",
      modified: new Date().toISOString()
    }
  });
}

export async function getMediaStore() {
  return getStoreAdapter(MEDIA_STORE);
}

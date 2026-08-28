import { contentTypeFor, jsonResponse } from "./lib/http.mjs";
import { readMediaLibraryFile } from "./lib/media-store.mjs";
import { getMediaStore, readStateJSON, readStaticData } from "./lib/state.mjs";

function cleanDataPath(value) {
  return String(value ?? "")
    .replaceAll("\\", "/")
    .replace(/^\/+/, "")
    .replaceAll("\0", "");
}

function fileResponse(request, data, contentType, size) {
  const headers = {
    "Content-Type": contentType,
    "Cache-Control": "no-store, max-age=0"
  };
  if (size !== undefined) {
    headers["Content-Length"] = String(size);
  }
  return new Response(request.method === "HEAD" ? null : data, {
    status: 200,
    headers
  });
}

async function readMissionFile(pathValue) {
  const media = await getMediaStore();
  const result = await media.getWithMetadata(`mission-files/${pathValue}`, { type: "arrayBuffer" });
  if (!result) {
    return null;
  }
  return {
    data: result.data,
    contentType: result.metadata?.contentType || contentTypeFor(pathValue),
    size: result.metadata?.size
  };
}

export default async function handler(request) {
  if (!["GET", "HEAD", "OPTIONS"].includes(request.method)) {
    return jsonResponse({ success: false, message: "Method not allowed" }, 405);
  }
  if (request.method === "OPTIONS") {
    return jsonResponse({ success: true });
  }

  const url = new URL(request.url);
  const dataPath = cleanDataPath(url.searchParams.get("path"));
  if (!dataPath || dataPath.includes("..")) {
    return jsonResponse({ success: false, message: "File not found" }, 404);
  }

  if (dataPath.startsWith("media-library/")) {
    const mediaFile = await readMediaLibraryFile(dataPath.slice("media-library/".length));
    if (mediaFile) {
      return fileResponse(request, mediaFile.data, mediaFile.contentType, mediaFile.size);
    }
  }

  if (
    dataPath.startsWith("missionary-letter-") ||
    dataPath.startsWith("archive-letter-")
  ) {
    const missionFile = await readMissionFile(dataPath);
    if (missionFile) {
      return fileResponse(request, missionFile.data, missionFile.contentType, missionFile.size);
    }
  }

  if (dataPath.endsWith(".json")) {
    const json = await readStateJSON(dataPath, null);
    if (json !== null) {
      return fileResponse(
        request,
        JSON.stringify(json, null, 2),
        "application/json; charset=utf-8",
        undefined
      );
    }
  }

  try {
    const staticData = await readStaticData(dataPath);
    return fileResponse(request, staticData, contentTypeFor(dataPath), staticData.byteLength);
  } catch {
    return jsonResponse({ success: false, message: "File not found" }, 404);
  }
}

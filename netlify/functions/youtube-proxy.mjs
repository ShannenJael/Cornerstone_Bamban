import { jsonResponse } from "./lib/http.mjs";

const legacyApiKey = "AIzaSyAsVWkfl8u6urcct-ofHJdeR6PQYGtg66U";

function clampMaxResults(value) {
  const parsed = Number.parseInt(String(value || "3"), 10);
  if (Number.isNaN(parsed)) {
    return 3;
  }
  return Math.min(10, Math.max(1, parsed));
}

export default async function handler(request) {
  if (request.method === "OPTIONS") {
    return jsonResponse({ success: true });
  }
  if (request.method !== "GET") {
    return jsonResponse({ error: "Method not allowed" }, 405);
  }

  const url = new URL(request.url);
  const mode = url.searchParams.get("mode") || url.searchParams.get("m") || "";
  const maxResults = clampMaxResults(url.searchParams.get("maxResults"));
  const apiKey = process.env.YOUTUBE_API_KEY || legacyApiKey;

  let apiUrl;
  if (mode === "channelLatest") {
    const channelId = url.searchParams.get("channelId") || url.searchParams.get("cid") || "";
    if (!channelId) {
      return jsonResponse({ error: "Missing channelId" }, 400);
    }
    apiUrl = new URL("https://www.googleapis.com/youtube/v3/search");
    apiUrl.search = new URLSearchParams({
      part: "snippet",
      order: "date",
      type: "video",
      channelId,
      maxResults: String(maxResults),
      key: apiKey
    }).toString();
  } else if (mode === "playlistItems") {
    const playlistId = url.searchParams.get("playlistId") || url.searchParams.get("pid") || "";
    if (!playlistId) {
      return jsonResponse({ error: "Missing playlistId" }, 400);
    }
    apiUrl = new URL("https://www.googleapis.com/youtube/v3/playlistItems");
    apiUrl.search = new URLSearchParams({
      part: "snippet",
      playlistId,
      maxResults: String(maxResults),
      key: apiKey
    }).toString();
  } else {
    return jsonResponse({ error: "Invalid mode" }, 400);
  }

  const response = await fetch(apiUrl);
  const text = await response.text();
  return new Response(text, {
    status: response.status,
    headers: {
      "Content-Type": response.headers.get("content-type") || "application/json; charset=utf-8",
      "Cache-Control": "no-store, max-age=0",
      "Access-Control-Allow-Origin": "*"
    }
  });
}

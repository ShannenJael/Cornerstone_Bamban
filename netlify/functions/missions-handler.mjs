import { validateCredentials } from "./lib/auth.mjs";
import { contentTypeFor, escapeHtml, getClientIp, jsonResponse, parseJsonField } from "./lib/http.mjs";
import { extensionFor, sanitizeFilename } from "./lib/media-store.mjs";
import { getMediaStore, readStateJSON, writeStateJSON } from "./lib/state.mjs";

const CURRENT_KEY = "missionary-letter.json";
const ARCHIVE_KEY = "missionary-letters.json";
const allowedLetterExtensions = new Set(["pdf", "doc", "docx"]);

function invalid(message = "Invalid action") {
  return jsonResponse({ success: false, message }, 400);
}

function timestamp() {
  const date = new Date();
  const pad = (value) => String(value).padStart(2, "0");
  return [
    date.getFullYear(),
    pad(date.getMonth() + 1),
    pad(date.getDate())
  ].join("") + "-" + [
    pad(date.getHours()),
    pad(date.getMinutes()),
    pad(date.getSeconds())
  ].join("");
}

function cleanLetterData(value) {
  return {
    missionaryName: escapeHtml(value?.missionaryName || "").slice(0, 300),
    location: escapeHtml(value?.location || "").slice(0, 300),
    date: escapeHtml(value?.date || "").slice(0, 200),
    content: escapeHtml(value?.content || "").slice(0, 200000),
    pdfFile: escapeHtml(value?.pdfFile || "").slice(0, 300)
  };
}

async function saveLetterFile(file, prefix) {
  if (!file || typeof file.name !== "string" || file.size === 0) {
    return "";
  }

  const ext = extensionFor(file.name);
  if (!allowedLetterExtensions.has(ext)) {
    throw new Error("Only PDF or Word files are allowed.");
  }

  const safeOriginal = sanitizeFilename(file.name);
  const safeName = `${prefix}-${timestamp()}.${ext}`;
  const media = await getMediaStore();
  await media.set(`mission-files/${safeName}`, file, {
    metadata: {
      contentType: file.type || contentTypeFor(safeOriginal),
      originalName: file.name,
      size: file.size,
      modified: Math.floor(Date.now() / 1000)
    }
  });

  return safeName;
}

async function updateArchive(letterData, maxEntries = 50) {
  const archive = await readStateJSON(ARCHIVE_KEY, { letters: [] });
  archive.letters = Array.isArray(archive.letters) ? archive.letters : [];
  archive.letters.unshift({
    missionaryName: letterData.missionaryName || "",
    location: letterData.location || "",
    date: letterData.date || "",
    lastUpdated: letterData.lastUpdated || "",
    pdfFile: letterData.pdfFile || "",
    content: letterData.content || ""
  });
  archive.letters = archive.letters.slice(0, maxEntries);
  await writeStateJSON(ARCHIVE_KEY, archive);
}

async function handleSave(form, request) {
  const letterData = cleanLetterData(parseJsonField(form, "data", {}));

  if (!letterData.missionaryName || !letterData.location || !letterData.date) {
    return invalid("Missionary name, location, and date are required.");
  }

  try {
    const savedFile = await saveLetterFile(form.get("letterPdf"), "missionary-letter");
    if (savedFile) {
      letterData.pdfFile = savedFile;
    }
  } catch (error) {
    return invalid(error.message);
  }

  if (!letterData.content && !letterData.pdfFile) {
    return invalid("Please provide letter content or upload a PDF.");
  }

  letterData.lastUpdated = new Date().toISOString();
  letterData.updatedBy = getClientIp(request);

  const existing = await readStateJSON(CURRENT_KEY, null);
  if (existing) {
    await writeStateJSON(`missionary-letter-backup-${timestamp()}.json`, existing);
  }

  await writeStateJSON(CURRENT_KEY, letterData);
  await updateArchive(letterData);

  return jsonResponse({
    success: true,
    message: "Letter saved successfully",
    data: letterData
  });
}

async function handleArchiveAdd(form) {
  const letterData = cleanLetterData(parseJsonField(form, "data", {}));
  if (!letterData.missionaryName || !letterData.date) {
    return invalid("Missionary name and date are required.");
  }

  try {
    const savedFile = await saveLetterFile(form.get("letterPdf"), "archive-letter");
    if (savedFile) {
      letterData.pdfFile = savedFile;
    }
  } catch (error) {
    return invalid(error.message);
  }

  if (!letterData.content && !letterData.pdfFile) {
    return invalid("Please provide letter content or upload a PDF.");
  }

  const archive = await readStateJSON(ARCHIVE_KEY, { letters: [] });
  archive.letters = Array.isArray(archive.letters) ? archive.letters : [];
  archive.letters.unshift({
    missionaryName: letterData.missionaryName,
    location: letterData.location,
    date: letterData.date,
    lastUpdated: new Date().toISOString(),
    pdfFile: letterData.pdfFile,
    content: letterData.content
  });
  archive.letters = archive.letters.slice(0, 100);
  await writeStateJSON(ARCHIVE_KEY, archive);

  return jsonResponse({ success: true, message: "Letter added to archive." });
}

async function handleArchiveDelete(form) {
  const index = Number.parseInt(String(form.get("index") ?? "-1"), 10);
  if (index < 0) {
    return invalid("Invalid index");
  }

  const archive = await readStateJSON(ARCHIVE_KEY, { letters: [] });
  archive.letters = Array.isArray(archive.letters) ? archive.letters : [];
  if (index >= archive.letters.length) {
    return invalid("Letter not found");
  }

  archive.letters.splice(index, 1);
  await writeStateJSON(ARCHIVE_KEY, archive);

  return jsonResponse({ success: true, message: "Letter removed from archive." });
}

export default async function handler(request) {
  if (request.method === "OPTIONS") {
    return jsonResponse({ success: true });
  }
  if (request.method !== "POST") {
    return invalid("Invalid request method");
  }

  let form;
  try {
    form = await request.formData();
  } catch {
    return invalid("Invalid form data");
  }

  const action = String(form.get("action") || "");

  switch (action) {
    case "login": {
      const username = String(form.get("username") || "").trim();
      const password = String(form.get("password") || "");
      if (validateCredentials("missions", username, password)) {
        return jsonResponse({ success: true, message: "Login successful", username });
      }
      return jsonResponse({ success: false, message: "Invalid credentials" }, 401);
    }
    case "save":
      return handleSave(form, request);
    case "archive_add":
      return handleArchiveAdd(form);
    case "archive_delete":
      return handleArchiveDelete(form);
    default:
      return invalid();
  }
}

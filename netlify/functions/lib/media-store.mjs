import { contentTypeFor, encodePathForUrl } from "./http.mjs";
import { getMediaStore, readStateJSON, writeStateJSON } from "./state.mjs";

export const MEDIA_INDEX_KEY = "media-index.json";

export const DEFAULT_MEDIA_FOLDERS = [
  "Worship Services",
  "Worship Services/Sunday Traditional",
  "Worship Services/Sunday Contemporary",
  "Worship Services/Wednesday Services",
  "Worship Services/Special Services",
  "Ministries",
  "Ministries/Children (Kids)",
  "Ministries/Youth (Students)",
  "Ministries/Adults",
  "Ministries/Missions",
  "Ministries/Seniors",
  "Special Events",
  "Special Events/Easter",
  "Special Events/Christmas",
  "Special Events/VBS",
  "Special Events/Baptisms",
  "Special Events/Community Outreach",
  "Staff & Leadership",
  "Staff & Leadership/Headshots",
  "Staff & Leadership/Team Photos",
  "Staff & Leadership/Pastor Media",
  "Facilities",
  "Facilities/Church Grounds",
  "Facilities/Sanctuary",
  "Facilities/Fellowship Hall",
  "Facilities/Classrooms",
  "Facilities/Parking & Exterior",
  "Technical",
  "Technical/High-Resolution (Print)",
  "Technical/Web-Optimized",
  "Technical/Public-Ready",
  "Technical/Working Files"
];

export const ALLOWED_EXTENSIONS = new Set([
  "jpg",
  "jpeg",
  "png",
  "gif",
  "webp",
  "svg",
  "bmp",
  "tiff",
  "heic",
  "mp4",
  "mov",
  "avi",
  "wmv",
  "webm",
  "mkv",
  "mp3",
  "wav",
  "ogg",
  "aac",
  "m4a",
  "pdf",
  "doc",
  "docx",
  "ppt",
  "pptx",
  "xls",
  "xlsx",
  "psd",
  "ai",
  "prproj",
  "aep"
]);

export function sanitizePath(value) {
  return String(value ?? "")
    .replaceAll("\\", "/")
    .replaceAll("..", "")
    .replaceAll("\0", "")
    .split("/")
    .map((part) => part.replace(/[^a-zA-Z0-9\-_\(\)&\s]/g, "").trim())
    .filter(Boolean)
    .join("/");
}

export function sanitizeFolderName(value) {
  return String(value ?? "")
    .replace(/[^a-zA-Z0-9\-_\(\)&\s]/g, "")
    .trim()
    .slice(0, 50);
}

export function sanitizeFilename(filename) {
  const raw = String(filename ?? "");
  const ext = raw.includes(".") ? raw.split(".").pop().toLowerCase() : "";
  const base = raw.includes(".") ? raw.slice(0, raw.lastIndexOf(".")) : raw;
  const safeBase = base
    .replace(/[^a-zA-Z0-9\-_\s]/g, "")
    .replace(/\s+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 100);

  return `${safeBase || `file-${Date.now()}`}${ext ? `.${ext}` : ""}`;
}

export function extensionFor(filename) {
  return String(filename ?? "").includes(".")
    ? String(filename).split(".").pop().toLowerCase()
    : "";
}

function parentPath(relativePath) {
  const clean = sanitizePath(relativePath);
  if (!clean.includes("/")) {
    return "";
  }
  return clean.slice(0, clean.lastIndexOf("/"));
}

function basename(relativePath) {
  return String(relativePath).split("/").pop();
}

function addParentFolders(folderSet, relativePath) {
  const clean = sanitizePath(relativePath);
  if (!clean) {
    return;
  }

  const parts = clean.split("/");
  let accum = "";
  for (let index = 0; index < parts.length - 1; index += 1) {
    accum = accum ? `${accum}/${parts[index]}` : parts[index];
    folderSet.add(accum);
  }
}

function normalizeIndex(index) {
  const folders = new Set(DEFAULT_MEDIA_FOLDERS);
  const files = Array.isArray(index?.files) ? index.files : [];

  for (const folder of index?.folders || []) {
    const clean = sanitizePath(folder);
    if (clean) {
      folders.add(clean);
      addParentFolders(folders, `${clean}/placeholder`);
    }
  }

  const normalizedFiles = files
    .map((file) => {
      const cleanPath = sanitizePath(file.path || parentPath(file.relativePath || ""));
      const name = sanitizeFilename(file.name || basename(file.relativePath || ""));
      if (!name) {
        return null;
      }
      const relativePath = cleanPath ? `${cleanPath}/${name}` : name;
      addParentFolders(folders, relativePath);
      return {
        name,
        path: cleanPath,
        relativePath,
        key: file.key || `media-library/${relativePath}`,
        size: Number(file.size) || 0,
        contentType: file.contentType || contentTypeFor(name),
        modified: Number(file.modified) || Math.floor(Date.now() / 1000)
      };
    })
    .filter(Boolean);

  return {
    folders: [...folders].sort((a, b) => a.localeCompare(b)),
    files: normalizedFiles.sort((a, b) => a.relativePath.localeCompare(b.relativePath))
  };
}

export async function getMediaIndex() {
  return normalizeIndex(await readStateJSON(MEDIA_INDEX_KEY, { folders: DEFAULT_MEDIA_FOLDERS, files: [] }));
}

export async function saveMediaIndex(index) {
  await writeStateJSON(MEDIA_INDEX_KEY, normalizeIndex(index));
}

export function fileUrl(relativePath) {
  return `/data/media-library/${encodePathForUrl(relativePath)}`;
}

export async function listMedia(pathValue) {
  const currentPath = sanitizePath(pathValue);
  const index = await getMediaIndex();
  const folders = new Set();

  for (const folder of index.folders) {
    if (parentPath(folder) === currentPath) {
      folders.add(basename(folder));
    }
  }

  const files = index.files
    .filter((file) => file.path === currentPath)
    .map((file) => ({
      name: file.name,
      size: file.size,
      url: fileUrl(file.relativePath),
      modified: file.modified
    }))
    .sort((a, b) => a.name.localeCompare(b.name));

  return {
    folders: [...folders].sort((a, b) => a.localeCompare(b)),
    files,
    currentPath
  };
}

export async function createFolder(pathValue, nameValue) {
  const currentPath = sanitizePath(pathValue);
  const folderName = sanitizeFolderName(nameValue);
  if (!folderName) {
    return { success: false, message: "Folder name is required" };
  }

  const folderPath = currentPath ? `${currentPath}/${folderName}` : folderName;
  const index = await getMediaIndex();
  if (!index.folders.includes(folderPath)) {
    index.folders.push(folderPath);
    addParentFolders(new Set(index.folders), `${folderPath}/placeholder`);
    await saveMediaIndex(index);
  }

  return { success: true, message: "Folder created successfully" };
}

export async function listAllFolders() {
  const index = await getMediaIndex();
  return index.folders;
}

function uniqueFilename(index, folderPath, safeName) {
  const ext = extensionFor(safeName);
  const base = ext ? safeName.slice(0, -(ext.length + 1)) : safeName;
  let candidate = safeName;
  let counter = 1;

  while (index.files.some((file) => file.path === folderPath && file.name.toLowerCase() === candidate.toLowerCase())) {
    candidate = `${base}_${counter}${ext ? `.${ext}` : ""}`;
    counter += 1;
  }

  return candidate;
}

export async function uploadMedia(pathValue, file) {
  const currentPath = sanitizePath(pathValue);
  if (!file || typeof file.name !== "string") {
    return { success: false, message: "No file uploaded" };
  }

  const maxFileSize = 50 * 1024 * 1024;
  if (file.size > maxFileSize) {
    return { success: false, message: "File exceeds maximum size of 50MB" };
  }

  const safeName = sanitizeFilename(file.name);
  const ext = extensionFor(safeName);
  if (!ALLOWED_EXTENSIONS.has(ext)) {
    return { success: false, message: `File type not allowed: ${ext}` };
  }

  const index = await getMediaIndex();
  const name = uniqueFilename(index, currentPath, safeName);
  const relativePath = currentPath ? `${currentPath}/${name}` : name;
  const key = `media-library/${relativePath}`;
  const contentType = file.type || contentTypeFor(name);
  const modified = Math.floor(Date.now() / 1000);

  const media = await getMediaStore();
  await media.set(key, file, {
    metadata: {
      contentType,
      originalName: file.name,
      size: file.size,
      modified
    }
  });

  index.folders.push(...DEFAULT_MEDIA_FOLDERS);
  addParentFolders(new Set(index.folders), relativePath);
  index.files.push({
    name,
    path: currentPath,
    relativePath,
    key,
    size: file.size,
    contentType,
    modified
  });
  await saveMediaIndex(index);

  return { success: true, message: "File uploaded successfully", filename: name };
}

export async function deleteMediaFile(pathValue, fileNameValue) {
  const currentPath = sanitizePath(pathValue);
  const fileName = sanitizeFilename(fileNameValue);
  const index = await getMediaIndex();
  const existing = index.files.find((file) => file.path === currentPath && file.name === fileName);

  if (!existing) {
    return { success: false, message: "File not found" };
  }

  const media = await getMediaStore();
  await media.delete(existing.key);
  index.files = index.files.filter((file) => file !== existing);
  await saveMediaIndex(index);

  return { success: true, message: "File deleted successfully" };
}

export async function deleteMediaFolder(pathValue) {
  const folderPath = sanitizePath(pathValue);
  if (!folderPath) {
    return { success: false, message: "Folder path is required" };
  }

  const index = await getMediaIndex();
  if (!index.folders.includes(folderPath)) {
    return { success: false, message: "Folder not found" };
  }

  const media = await getMediaStore();
  const filesToDelete = index.files.filter(
    (file) => file.path === folderPath || file.path.startsWith(`${folderPath}/`)
  );

  await Promise.all(filesToDelete.map((file) => media.delete(file.key)));
  index.files = index.files.filter((file) => !filesToDelete.includes(file));
  index.folders = index.folders.filter(
    (folder) => folder !== folderPath && !folder.startsWith(`${folderPath}/`)
  );
  await saveMediaIndex(index);

  return { success: true, message: "Folder deleted successfully" };
}

export async function moveMediaFile(fromPathValue, toPathValue, fileNameValue) {
  const fromPath = sanitizePath(fromPathValue);
  const toPath = sanitizePath(toPathValue);
  const fileName = sanitizeFilename(fileNameValue);
  const index = await getMediaIndex();
  const existing = index.files.find((file) => file.path === fromPath && file.name === fileName);

  if (!existing) {
    return { success: false, message: "File not found" };
  }

  const newName = uniqueFilename(index, toPath, existing.name);
  const newRelativePath = toPath ? `${toPath}/${newName}` : newName;
  const newKey = `media-library/${newRelativePath}`;
  const media = await getMediaStore();
  const data = await media.get(existing.key, { type: "arrayBuffer" });

  if (data === null) {
    return { success: false, message: "File not found" };
  }

  await media.set(newKey, data, {
    metadata: {
      contentType: existing.contentType,
      size: existing.size,
      modified: Math.floor(Date.now() / 1000),
      movedFrom: existing.relativePath
    }
  });
  await media.delete(existing.key);

  existing.name = newName;
  existing.path = toPath;
  existing.relativePath = newRelativePath;
  existing.key = newKey;
  existing.modified = Math.floor(Date.now() / 1000);
  if (toPath) {
    index.folders.push(toPath);
  }
  await saveMediaIndex(index);

  return { success: true, message: "File moved successfully" };
}

export async function readMediaLibraryFile(relativePathValue) {
  const relativePath = sanitizePath(relativePathValue);
  if (!relativePath) {
    return null;
  }

  const index = await getMediaIndex();
  const entry = index.files.find((file) => file.relativePath === relativePath);
  const key = entry?.key || `media-library/${relativePath}`;
  const media = await getMediaStore();
  const result = await media.getWithMetadata(key, { type: "arrayBuffer" });

  if (!result) {
    return null;
  }

  return {
    data: result.data,
    contentType: entry?.contentType || result.metadata?.contentType || contentTypeFor(relativePath),
    size: entry?.size || result.metadata?.size || undefined
  };
}

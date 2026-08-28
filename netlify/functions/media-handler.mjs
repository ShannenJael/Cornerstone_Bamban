import { validateCredentials } from "./lib/auth.mjs";
import { getClientIp, jsonResponse, parseJsonField, sanitizeText } from "./lib/http.mjs";
import {
  createFolder,
  deleteMediaFile,
  deleteMediaFolder,
  listAllFolders,
  listMedia,
  moveMediaFile,
  uploadMedia
} from "./lib/media-store.mjs";
import { getMediaStore, readStateJSON, writeStateJSON } from "./lib/state.mjs";

function invalid(message = "Invalid action") {
  return jsonResponse({ success: false, message }, 400);
}

function filterVisitationCards(cards, period, startDate, endDate) {
  const now = new Date();

  return cards.filter((card) => {
    const cardDate = new Date(card.timestamp);
    if (Number.isNaN(cardDate.getTime())) {
      return false;
    }

    switch (period) {
      case "week": {
        const weekStart = new Date(now);
        weekStart.setDate(now.getDate() - now.getDay() + 1);
        weekStart.setHours(0, 0, 0, 0);
        return cardDate >= weekStart;
      }
      case "month": {
        const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
        return cardDate >= monthStart;
      }
      case "year": {
        const yearStart = new Date(now.getFullYear(), 0, 1);
        return cardDate >= yearStart;
      }
      case "custom": {
        if (!startDate || !endDate) {
          return true;
        }
        const start = new Date(startDate);
        const end = new Date(endDate);
        end.setHours(23, 59, 59, 999);
        return cardDate >= start && cardDate <= end;
      }
      default:
        return true;
    }
  });
}

async function handleVisitationSave(form, request) {
  const data = await readStateJSON("visitation-cards.json", { cards: [] });
  const card = {
    id: `vc_${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`,
    personName: sanitizeText(form.get("personName"), 300),
    address: sanitizeText(form.get("address"), 500),
    distance: sanitizeText(form.get("distance"), 100),
    q1Where: sanitizeText(form.get("q1Where"), 500),
    q2SavedBaptized: sanitizeText(form.get("q2SavedBaptized"), 20),
    q3NeedRide: sanitizeText(form.get("q3NeedRide"), 20),
    q4FollowUp: sanitizeText(form.get("q4FollowUp"), 20),
    status: parseJsonField(form, "status", []),
    age: sanitizeText(form.get("age"), 50),
    children: sanitizeText(form.get("children"), 20),
    comments: sanitizeText(form.get("comments"), 3000),
    visitorName: sanitizeText(form.get("visitorName"), 300),
    timestamp: new Date().toISOString(),
    ip: getClientIp(request)
  };

  if (!card.personName || !card.address || !card.visitorName) {
    return invalid("Name, address, and your name are required.");
  }

  data.cards = Array.isArray(data.cards) ? data.cards : [];
  data.cards.push(card);
  await writeStateJSON("visitation-cards.json", data);

  return jsonResponse({ success: true, message: "Visitation card saved successfully.", id: card.id });
}

async function handleVisitationList(form) {
  const data = await readStateJSON("visitation-cards.json", { cards: [] });
  const period = String(form.get("period") || "all");
  const cards = filterVisitationCards(
    Array.isArray(data.cards) ? data.cards : [],
    period,
    form.get("startDate"),
    form.get("endDate")
  ).sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

  return jsonResponse({ success: true, cards, total: cards.length });
}

async function handleVisitationPdfSave(form) {
  const file = form.get("file");
  if (!file || typeof file.name !== "string") {
    return invalid("No PDF uploaded.");
  }

  const safeName = file.name.replace(/[^a-zA-Z0-9._-]/g, "_");
  if (!safeName.toLowerCase().endsWith(".pdf")) {
    return invalid("Only PDF files are allowed.");
  }

  const key = `visitation-pdfs/${Date.now()}-${safeName}`;
  const media = await getMediaStore();
  await media.set(key, file, {
    metadata: {
      contentType: "application/pdf",
      originalName: file.name,
      size: file.size,
      modified: Math.floor(Date.now() / 1000)
    }
  });

  return jsonResponse({ success: true, message: "PDF saved.", path: key.split("/").pop() });
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
      if (validateCredentials("media", username, password)) {
        return jsonResponse({ success: true, message: "Login successful", username });
      }
      return jsonResponse({ success: false, message: "Invalid credentials" }, 401);
    }
    case "list": {
      const result = await listMedia(form.get("path"));
      return jsonResponse({ success: true, ...result });
    }
    case "upload": {
      return jsonResponse(await uploadMedia(form.get("path"), form.get("file")));
    }
    case "createFolder": {
      return jsonResponse(await createFolder(form.get("path"), form.get("name")));
    }
    case "deleteFolder": {
      return jsonResponse(await deleteMediaFolder(form.get("path")));
    }
    case "listAllFolders": {
      return jsonResponse({ success: true, folders: await listAllFolders() });
    }
    case "delete": {
      return jsonResponse(await deleteMediaFile(form.get("path"), form.get("file")));
    }
    case "move": {
      return jsonResponse(await moveMediaFile(form.get("fromPath"), form.get("toPath"), form.get("file")));
    }
    case "visitation_save":
      return handleVisitationSave(form, request);
    case "visitation_list":
      return handleVisitationList(form);
    case "visitation_pdf_save":
      return handleVisitationPdfSave(form);
    default:
      return invalid();
  }
}

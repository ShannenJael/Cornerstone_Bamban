import { getClientIp, jsonResponse, sanitizeText } from "./lib/http.mjs";
import { readStateJSON, writeStateJSON } from "./lib/state.mjs";

async function postWebhook(payload) {
  if (!process.env.CONTACT_WEBHOOK_URL) {
    return;
  }

  const response = await fetch(process.env.CONTACT_WEBHOOK_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    throw new Error(`Webhook failed with ${response.status}`);
  }
}

export default async function handler(request) {
  if (request.method === "OPTIONS") {
    return jsonResponse({ success: true });
  }
  if (request.method !== "POST") {
    return jsonResponse({ success: false, message: "Method not allowed" }, 405);
  }

  let form;
  try {
    form = await request.formData();
  } catch {
    return jsonResponse({ success: false, message: "Invalid form data" }, 400);
  }

  if (String(form.get("website") || "").trim()) {
    return jsonResponse({ success: false, message: "Spam detected" }, 400);
  }

  const submission = {
    id: `contact_${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`,
    name: sanitizeText(form.get("name"), 300),
    email: sanitizeText(form.get("email"), 300),
    phone: sanitizeText(form.get("phone"), 100),
    message: sanitizeText(form.get("message"), 10000),
    sourcePath: request.headers.get("referer") || "",
    submittedAt: new Date().toISOString(),
    ip: getClientIp(request),
    to: process.env.CONTACT_TO_EMAIL || ""
  };

  if (!submission.name || !submission.email || !submission.message) {
    return jsonResponse({ success: false, message: "Please fill in all required fields" }, 400);
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(submission.email)) {
    return jsonResponse({ success: false, message: "Please enter a valid email address" }, 400);
  }

  const state = await readStateJSON("contact-submissions.json", { submissions: [] });
  state.submissions = Array.isArray(state.submissions) ? state.submissions : [];
  state.submissions.unshift(submission);
  state.submissions = state.submissions.slice(0, 500);
  await writeStateJSON("contact-submissions.json", state);

  try {
    await postWebhook(submission);
  } catch {
    return jsonResponse({
      success: true,
      message: "Thank you! Your message was received. The notification webhook needs attention."
    });
  }

  return jsonResponse({
    success: true,
    message: "Thank you! Your message has been received successfully."
  });
}

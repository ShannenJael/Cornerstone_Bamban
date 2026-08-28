import { clearSessionCookie, createSessionCookie, readSession, validateCredentials } from "./lib/auth.mjs";
import { escapeHtml, htmlResponse } from "./lib/http.mjs";
import { repoRoot } from "./lib/storage.mjs";
import { readFile } from "node:fs/promises";
import path from "node:path";

function redirect(location, headers = {}) {
  return new Response(null, {
    status: 303,
    headers: {
      Location: location,
      ...headers
    }
  });
}

function loginPage(error = "") {
  return htmlResponse(`<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ushers Help Login</title>
  <style>
    body{font-family:Arial,sans-serif;background:#0a0a0a;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:20px}
    .login-card{background:#1a1a1a;border:1px solid #2a2a2a;border-radius:10px;padding:32px;width:100%;max-width:360px;box-shadow:0 10px 30px rgba(0,0,0,.4)}
    h1{margin:0 0 16px;font-size:1.4rem;color:#14AFB1;text-align:center}
    label{display:block;margin:12px 0 6px;font-size:.95rem}
    input{width:100%;padding:10px 12px;border-radius:6px;border:1px solid #333;background:#0f0f0f;color:#fff;box-sizing:border-box}
    .error{background:rgba(220,53,69,.2);border:1px solid #dc3545;color:#ffb3b3;padding:10px;border-radius:6px;margin-bottom:12px;text-align:center}
    button{width:100%;padding:12px;margin-top:16px;border:0;border-radius:6px;background:#14AFB1;color:#fff;font-size:1rem;cursor:pointer}
  </style>
</head>
<body>
  <form class="login-card" method="post" action="/pages/ushers-help.php">
    <h1>Ushers Help Login</h1>
    ${error ? `<div class="error">${escapeHtml(error)}</div>` : ""}
    <label for="username">Username</label>
    <input id="username" name="username" type="text" autocomplete="username" required>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit">Sign In</button>
  </form>
</body>
</html>`);
}

export default async function handler(request) {
  const url = new URL(request.url);
  if (url.searchParams.has("logout")) {
    return redirect("/pages/ushers-help.php", {
      "Set-Cookie": clearSessionCookie("ushers")
    });
  }

  if (request.method === "POST") {
    const form = await request.formData();
    const username = String(form.get("username") || "").trim();
    const password = String(form.get("password") || "");
    if (validateCredentials("ushers", username, password)) {
      return redirect("/pages/ushers-help.php", {
        "Set-Cookie": createSessionCookie("ushers", username)
      });
    }
    return loginPage("Invalid username or password.");
  }

  if (!readSession(request, "ushers")) {
    return loginPage();
  }

  const contentPath = path.join(repoRoot, "pages", "ushers-help-content.html");
  const html = await readFile(contentPath, "utf8");
  return htmlResponse(html.replace("</body>", '<p style="text-align:center;margin:24px"><a href="/pages/ushers-help.php?logout=1">Logout</a></p></body>'));
}

import { createHmac, timingSafeEqual } from "node:crypto";

const staffUsers = {
  "thedixonmissions@gmail.com": "DixonMissions2026!",
  "christnemarie.cezar@gmail.com": "ChristneMissions!",
  "Pascualshannenjael@gmail.com": "shannenlovesJesus!"
};

const authConfig = {
  media: {
    env: "MEDIA_USERS_JSON",
    fallback: staffUsers
  },
  missions: {
    env: "MISSIONS_USERS_JSON",
    fallback: staffUsers
  },
  visitation: {
    env: "VISITATION_ADMIN_USERS_JSON",
    fallback: {
      VisitationAdmin: "HarvestVisit2026!"
    }
  },
  ushers: {
    env: "USHERS_USERS_JSON",
    fallback: {
      UshersHelp: "HarvestUshers2026!"
    }
  }
};

function parseUsers(raw) {
  if (!raw || !raw.trim()) {
    return null;
  }

  try {
    const parsed = JSON.parse(raw);
    if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
      return Object.fromEntries(
        Object.entries(parsed)
          .filter(([, password]) => typeof password === "string")
          .map(([user, password]) => [String(user), password])
      );
    }
  } catch {
    // Try delimiter syntax below.
  }

  const pairs = raw.split(/[;,]/).map((pair) => pair.trim()).filter(Boolean);
  const users = {};
  for (const pair of pairs) {
    const separator = pair.includes("=") ? "=" : ":";
    const index = pair.indexOf(separator);
    if (index <= 0) {
      continue;
    }
    users[pair.slice(0, index).trim()] = pair.slice(index + 1).trim();
  }
  return Object.keys(users).length ? users : null;
}

function getUsers(kind) {
  const config = authConfig[kind];
  if (!config) {
    return {};
  }
  return parseUsers(process.env[config.env]) || config.fallback;
}

function safeCompare(a, b) {
  const left = Buffer.from(String(a));
  const right = Buffer.from(String(b));
  if (left.length !== right.length) {
    return false;
  }
  return timingSafeEqual(left, right);
}

export function validateCredentials(kind, username, password) {
  const users = getUsers(kind);
  const expected = users[String(username ?? "").trim()];
  return typeof expected === "string" && safeCompare(expected, password ?? "");
}

function secret() {
  return process.env.SESSION_SECRET || process.env.URL || "cornerstone-netlify-local-session";
}

function sign(payload) {
  return createHmac("sha256", secret()).update(payload).digest("base64url");
}

function cookieName(scope) {
  return `cbc_${scope}_session`;
}

function parseCookies(request) {
  const header = request.headers.get("cookie") || "";
  return Object.fromEntries(
    header
      .split(";")
      .map((part) => part.trim())
      .filter(Boolean)
      .map((part) => {
        const index = part.indexOf("=");
        return index === -1
          ? [part, ""]
          : [part.slice(0, index), decodeURIComponent(part.slice(index + 1))];
      })
  );
}

export function createSessionCookie(scope, username, maxAge = 1800) {
  const payload = Buffer.from(JSON.stringify({
    scope,
    username,
    exp: Date.now() + maxAge * 1000
  })).toString("base64url");

  return `${cookieName(scope)}=${encodeURIComponent(`${payload}.${sign(payload)}`)}; Path=/; Max-Age=${maxAge}; HttpOnly; SameSite=Lax`;
}

export function clearSessionCookie(scope) {
  return `${cookieName(scope)}=; Path=/; Max-Age=0; HttpOnly; SameSite=Lax`;
}

export function readSession(request, scope) {
  const cookie = parseCookies(request)[cookieName(scope)];
  if (!cookie || !cookie.includes(".")) {
    return null;
  }

  const [payload, signature] = cookie.split(".");
  if (!payload || !signature || !safeCompare(sign(payload), signature)) {
    return null;
  }

  try {
    const data = JSON.parse(Buffer.from(payload, "base64url").toString("utf8"));
    if (data.scope !== scope || data.exp < Date.now()) {
      return null;
    }
    return data;
  } catch {
    return null;
  }
}

import { copyFile, mkdir, rm, readdir, stat } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const outDir = path.join(root, "dist");

const excludedDirectories = new Set([
  ".git",
  ".netlify",
  ".vscode",
  ".idea",
  "node_modules",
  "netlify",
  "scripts",
  "php",
  "dist"
]);

const excludedFiles = new Set([
  ".dockerignore",
  ".env",
  ".env.local",
  ".env.example",
  ".gitignore",
  ".htaccess",
  ".htaccess-redirect",
  "Dockerfile",
  "Dockerfile.txt",
  "netlify.toml",
  "package.json",
  "package-lock.json",
  "make_pages.py"
]);

function isInside(parent, child) {
  const relative = path.relative(parent, child);
  return relative === "" || (!relative.startsWith("..") && !path.isAbsolute(relative));
}

async function copyTree(from, to) {
  await mkdir(to, { recursive: true });
  const entries = await readdir(from, { withFileTypes: true });

  for (const entry of entries) {
    const source = path.join(from, entry.name);
    const target = path.join(to, entry.name);

    if (entry.isDirectory()) {
      if (excludedDirectories.has(entry.name)) {
        continue;
      }
      await copyTree(source, target);
      continue;
    }

    if (!entry.isFile()) {
      continue;
    }

    if (excludedFiles.has(entry.name) || entry.name.endsWith(".php")) {
      continue;
    }

    await mkdir(path.dirname(target), { recursive: true });
    await copyFile(source, target);
  }
}

const resolvedOut = path.resolve(outDir);
if (!isInside(root, resolvedOut) || resolvedOut === root) {
  throw new Error(`Refusing to clean unsafe output directory: ${resolvedOut}`);
}

await rm(resolvedOut, { recursive: true, force: true });
await copyTree(root, resolvedOut);

const outputStats = await stat(resolvedOut);
if (!outputStats.isDirectory()) {
  throw new Error("Netlify build output was not created.");
}

console.log(`Prepared static Netlify publish directory: ${path.relative(root, resolvedOut)}`);

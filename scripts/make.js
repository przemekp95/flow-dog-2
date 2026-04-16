#!/usr/bin/env node

const fs = require("node:fs");
const path = require("node:path");
const { spawnSync } = require("node:child_process");

function die(message, code = 1) {
  process.stderr.write(`${message}\n`);
  process.exit(code);
}

function parseArgs(argv) {
  let makefilePath = "Makefile";
  const vars = new Map();
  const targets = [];
  let showVersion = false;
  let showHelp = false;

  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];

    if (arg === "--version" || arg === "-v") {
      showVersion = true;
      continue;
    }

    if (arg === "--help" || arg === "-h") {
      showHelp = true;
      continue;
    }

    if (arg === "-f") {
      index += 1;
      if (index >= argv.length) {
        die("make: option '-f' requires an argument", 2);
      }
      makefilePath = argv[index];
      continue;
    }

    if (/^[A-Za-z_][A-Za-z0-9_]*=/.test(arg)) {
      const separatorIndex = arg.indexOf("=");
      vars.set(arg.slice(0, separatorIndex), arg.slice(separatorIndex + 1));
      continue;
    }

    if (arg.startsWith("-")) {
      die(`make: unsupported option '${arg}'`, 2);
    }

    targets.push(arg);
  }

  return { makefilePath, vars, targets, showVersion, showHelp };
}

function expandVariables(value, vars) {
  return value.replace(/\$\(([^)]+)\)/g, (_, name) => {
    if (vars.has(name)) {
      return vars.get(name);
    }

    if (Object.prototype.hasOwnProperty.call(process.env, name)) {
      return process.env[name];
    }

    return "";
  });
}

function parseMakefile(makefilePath, cliVars) {
  const makefileDir = path.dirname(makefilePath);
  const content = fs.readFileSync(makefilePath, "utf8");
  const lines = content.split(/\r?\n/);
  const vars = new Map(cliVars);
  const targets = new Map();
  const order = [];
  let currentTarget = null;
  let currentCommand = null;

  function pushCommand() {
    if (currentTarget === null || currentCommand === null) {
      return;
    }

    targets.get(currentTarget).commands.push(currentCommand);
    currentCommand = null;
  }

  for (const rawLine of lines) {
    const line = rawLine.replace(/\r$/, "");

    if (/^\t/.test(line)) {
      if (currentTarget === null) {
        continue;
      }

      const recipe = line.slice(1);

      if (currentCommand === null) {
        currentCommand = recipe;
      } else if (currentCommand.endsWith("\\")) {
        currentCommand = `${currentCommand}\n${recipe}`;
      } else {
        pushCommand();
        currentCommand = recipe;
      }

      if (!currentCommand.endsWith("\\")) {
        pushCommand();
      }

      continue;
    }

    pushCommand();
    currentTarget = null;

    const trimmed = line.trim();
    if (trimmed === "" || trimmed.startsWith("#")) {
      continue;
    }

    const assignmentMatch = line.match(/^([A-Za-z_][A-Za-z0-9_]*)\s*(\?=|:=|=)\s*(.*)$/);
    if (assignmentMatch) {
      const [, name, operator, rawValue] = assignmentMatch;
      if (operator === "?=" && vars.has(name)) {
        continue;
      }

      const expandedValue = operator === ":=" ? expandVariables(rawValue, vars) : rawValue;
      vars.set(name, expandedValue);
      continue;
    }

    const targetMatch = line.match(/^([^\s:#]+):\s*(.*)$/);
    if (!targetMatch) {
      continue;
    }

    const [, targetName, depsRaw] = targetMatch;
    if (targetName === ".PHONY") {
      continue;
    }

    const deps = depsRaw.trim() === "" ? [] : depsRaw.trim().split(/\s+/);
    targets.set(targetName, { deps, commands: [] });
    order.push(targetName);
    currentTarget = targetName;
  }

  pushCommand();

  return {
    makefileDir,
    vars,
    targets,
    defaultTarget: order[0] ?? null,
  };
}

function runTargets(parsed, targetNames) {
  const visited = new Set();
  const running = new Set();

  function runTarget(targetName) {
    if (visited.has(targetName)) {
      return;
    }

    const target = parsed.targets.get(targetName);
    if (!target) {
      die(`make: *** No rule to make target '${targetName}'.  Stop.`, 2);
    }

    if (running.has(targetName)) {
      die(`make: circular dependency detected for target '${targetName}'`, 2);
    }

    running.add(targetName);
    for (const dependency of target.deps) {
      runTarget(dependency);
    }
    running.delete(targetName);

    for (const rawCommand of target.commands) {
      const command = expandVariables(rawCommand, parsed.vars);
      const result = spawnSync("/usr/bin/bash", ["-lc", command], {
        cwd: parsed.makefileDir,
        env: process.env,
        stdio: "inherit",
      });

      if (result.status !== 0) {
        process.exit(result.status ?? 1);
      }
    }

    visited.add(targetName);
  }

  for (const targetName of targetNames) {
    runTarget(targetName);
  }
}

const { makefilePath, vars: cliVars, targets, showVersion, showHelp } = parseArgs(
  process.argv.slice(2),
);

if (showVersion) {
  process.stdout.write("make (repo-local wrapper) 1.0.0\n");
  process.exit(0);
}

if (showHelp) {
  process.stdout.write(
    "Usage: make [-f FILE] [VAR=value ...] [target ...]\n" +
      "Repo-local fallback wrapper for the project's Makefile targets.\n",
  );
  process.exit(0);
}

const absoluteMakefilePath = path.resolve(process.cwd(), makefilePath);

if (!fs.existsSync(absoluteMakefilePath)) {
  die(`make: ${makefilePath}: No such file or directory`, 2);
}

const parsed = parseMakefile(absoluteMakefilePath, cliVars);
const targetNames = targets.length > 0 ? targets : [parsed.defaultTarget];

if (!targetNames[0]) {
  die("make: *** No targets.  Stop.", 2);
}

runTargets(parsed, targetNames);

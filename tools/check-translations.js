#!/usr/bin/env node
/**
 * STB Atelier – Übersetzungs-Check
 * --------------------------------------------------------
 * Prüft, ob translations.js für de/en/it wirklich dieselben Keys enthält.
 * Bisher fiel ein fehlender Key nicht auf: script.js zeigt bei einem
 * fehlenden Key einfach den zuletzt gesetzten Text weiter an
 * (`if (t[key] !== undefined) …`), ohne Warnung.
 *
 * Aufruf (optional, Node.js nötig – keine Auswirkung auf die Website
 * selbst, reines Entwickler-Tool):
 *   node tools/check-translations.js
 *
 * Exit-Code 0 = alles vollständig, 1 = mindestens ein Key fehlt irgendwo.
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = path.join(__dirname, '..', 'translations.js');
const code = fs.readFileSync(file, 'utf8');

// translations.js deklariert STB_TRANSLATIONS mit `const` im Top-Level-Scope.
// Das hängt sich (wie im Browser bei <script>) NICHT an das globale Objekt
// an – deshalb den Wert stattdessen als letzten Ausdruck zurückgeben lassen.
const sandbox = {};
vm.createContext(sandbox);
const translations = vm.runInContext(
  code + '\nSTB_TRANSLATIONS;',
  sandbox,
  { filename: 'translations.js' }
);
if (!translations || typeof translations !== 'object') {
  console.error('❌ STB_TRANSLATIONS wurde in translations.js nicht gefunden.');
  process.exit(1);
}

const langs = Object.keys(translations);
if (langs.length === 0) {
  console.error('❌ Keine Sprachen in STB_TRANSLATIONS gefunden.');
  process.exit(1);
}

const keysByLang = {};
const allKeys = new Set();
for (const lang of langs) {
  keysByLang[lang] = new Set(Object.keys(translations[lang]));
  keysByLang[lang].forEach((k) => allKeys.add(k));
}

let missingCount = 0;
let emptyCount = 0;
const sortedKeys = Array.from(allKeys).sort();

for (const key of sortedKeys) {
  const missingIn = langs.filter((l) => !keysByLang[l].has(key));
  if (missingIn.length > 0) {
    missingCount++;
    console.log(`fehlt in [${missingIn.join(', ')}]: "${key}"`);
    continue;
  }
  // Leere Strings fallen separat auf (kein harter Fehler, aber meist ein Versehen).
  for (const lang of langs) {
    const value = translations[lang][key];
    if (typeof value === 'string' && value.trim() === '') {
      emptyCount++;
      console.log(`leer in [${lang}]: "${key}"`);
    }
  }
}

console.log('');
console.log(`Sprachen geprüft: ${langs.join(', ')}`);
console.log(`Keys insgesamt: ${allKeys.size}`);

if (missingCount === 0 && emptyCount === 0) {
  console.log('✅ Alle Keys sind in allen Sprachen vorhanden und nicht leer.');
  process.exit(0);
}

if (missingCount > 0) {
  console.log(`❌ ${missingCount} Key(s) fehlen in mindestens einer Sprache (siehe oben).`);
}
if (emptyCount > 0) {
  console.log(`⚠️  ${emptyCount} leere(r) Wert(e) gefunden (siehe oben).`);
}
process.exit(missingCount > 0 ? 1 : 0);

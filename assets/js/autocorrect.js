/**
 * autocorrect.js
 * A tiny, extensible dictionary-based autocorrect for common Tagalog
 * text-speak / typos. This is intentionally simple (exact word match,
 * case-insensitive) rather than a full spellchecker, so it's cheap to
 * run on every keystroke.
 *
 * Add more entries any time — key = what people type, value = the
 * corrected/expanded word.
 */
const AUTOCORRECT_DICTIONARY = {
  "wla": "wala",
  "pde": "pwede",
  "pwd": "pwede",
  "kmusta": "kumusta",
  "kamusta": "kumusta",
  "san": "saan",
  "d2": "dito",
  "dto": "dito",
  "diyan2": "diyan",
  "sya": "siya",
  "cya": "siya",
  "eto": "ito",
  "yun": "iyon",
  "yan": "iyan",
  "nlng": "na lang",
  "nalng": "na lang",
  "lng": "lang",
  "kng": "kung",
  "pra": "para",
  "prin": "pa rin",
  "dpat": "dapat",
  "tlga": "talaga",
  "tlaga": "talaga",
  "grabe2": "grabe",
  "hnd": "hindi",
  "hnde": "hindi",
  "di": "hindi",
  "bkit": "bakit",
  "knino": "kanino",
  "knya": "kanya",
  "nia": "niya",
  "nya": "niya",
  "mgnda": "maganda",
  "mgndang": "magandang",
  "gnyan": "ganyan",
  "gnun": "ganun",
  "gnito": "ganito",
  "tpos": "tapos",
  "tpos2": "tapos",
  "pgkatapos": "pagkatapos",
  "ksama": "kasama",
  "kht": "kahit",
  "mrami": "marami",
  "ngyon": "ngayon",
  "ngaun": "ngayon",
  "bkn": "baka"
};

/**
 * Applies autocorrect to the *last completed word* in a textarea's value,
 * i.e. the word immediately before a boundary character (space, newline,
 * or common punctuation) that was just typed.
 *
 * @param {HTMLTextAreaElement} el
 * @returns {{corrected: boolean, from: string, to: string}} info about what changed, if anything.
 */
function applyAutocorrect(el) {
  const value = el.value;
  if (value.length === 0) return { corrected: false };

  const lastChar = value[value.length - 1];
  const boundary = /[\s.,!?;:]/.test(lastChar);
  if (!boundary) return { corrected: false };

  // Grab the word right before the boundary character.
  const withoutBoundary = value.slice(0, -1);
  const match = withoutBoundary.match(/(\S+)$/);
  if (!match) return { corrected: false };

  const word = match[1];
  const lower = word.toLowerCase();
  const replacement = AUTOCORRECT_DICTIONARY[lower];

  if (!replacement) return { corrected: false };

  // Preserve a leading capital if the original word had one.
  const finalReplacement = word[0] === word[0].toUpperCase()
    ? replacement.charAt(0).toUpperCase() + replacement.slice(1)
    : replacement;

  const start = match.index;
  const newValue =
    withoutBoundary.slice(0, start) + finalReplacement + lastChar;

  el.value = newValue;
  // Keep the caret at the end (this tool assumes typing forward, not editing mid-text).
  el.selectionStart = el.selectionEnd = newValue.length;

  return { corrected: true, from: word, to: finalReplacement };
}

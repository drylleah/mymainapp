<?php
// myapp/index.php
// Main UI: single input pane only. There is no visible output pane at all.
// The translation lives only in memory in the browser tab. It leaves the
// page only through the clipboard (Copy) or a press-and-hold reveal (Peek),
// and is never rendered as persistent text on screen.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cipher Console :: TL &rarr; EN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#0d1117;
    --panel:#131a24;
    --border:#232b36;
    --text:#dbe2ea;
    --muted:#6b7684;
    --accent:#e8a33d;
    --accent2:#37d0b0;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    background:var(--bg);
    color:var(--text);
    font-family:'IBM Plex Mono', monospace;
    display:flex;
    justify-content:center;
    padding:24px 12px;
  }
  .console{
    width:100%;
    max-width:560px;
    background:var(--panel);
    border:1px solid var(--border);
    border-radius:10px;
    overflow:hidden;
  }
  .console__header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:14px 18px;
    border-bottom:1px solid var(--border);
  }
  .brand__mark{color:var(--accent); margin-right:8px;}
  .brand__text{font-family:'Sora',sans-serif; font-weight:700; letter-spacing:.06em; font-size:13px;}
  .meta__pill{
    font-size:11px;
    padding:3px 8px;
    border:1px solid var(--border);
    border-radius:20px;
    color:var(--muted);
    margin-right:8px;
  }
  .meta__pill.is-busy{color:var(--accent); border-color:var(--accent);}
  .meta__pill.is-ready{color:var(--accent2); border-color:var(--accent2);}
  .meta__route{font-size:11px; color:var(--muted);}

  .console__body{padding:18px;}
  .pane__label{
    display:flex;
    gap:8px;
    font-size:11px;
    letter-spacing:.08em;
    color:var(--muted);
    margin-bottom:8px;
  }
  .pane__index{color:var(--accent);}
  .pane__textarea{
    width:100%;
    min-height:220px;
    resize:vertical;
    background:#0a0f16;
    border:1px solid var(--border);
    border-radius:8px;
    color:var(--text);
    font-family:'IBM Plex Mono', monospace;
    font-size:14px;
    line-height:1.5;
    padding:14px;
    outline:none;
  }
  .pane__textarea:focus{border-color:var(--accent);}

  .toolbar{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:14px;
  }
  .footer__hint{font-size:11px; color:var(--muted); margin-right:auto;}

  .btn{
    font-family:'IBM Plex Mono', monospace;
    font-size:12px;
    padding:9px 16px;
    border-radius:6px;
    cursor:pointer;
    border:1px solid var(--border);
    background:transparent;
    color:var(--text);
    user-select:none;
    -webkit-user-select:none;
  }
  .btn:disabled{opacity:.4; cursor:not-allowed;}
  .btn--primary{background:var(--accent); color:#1a1305; border-color:var(--accent); font-weight:600;}
  .btn--primary:not(:disabled):hover{filter:brightness(1.08);}
  .btn--ghost{color:var(--accent2); border-color:var(--accent2);}
  .btn--ghost:not(:disabled):active{background:rgba(55,208,176,.12);}

  .toast{
    font-size:11px;
    color:var(--accent2);
    opacity:0;
    transition:opacity .2s;
  }
  .toast.show{opacity:1;}

  /* Peek reveal: a small floating tooltip, only visible while button is held down */
  .peek-bubble{
    position:absolute;
    max-width:260px;
    background:#0a0f16;
    border:1px solid var(--accent2);
    color:var(--text);
    font-size:12.5px;
    line-height:1.4;
    padding:10px 12px;
    border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.4);
    pointer-events:none;
    display:none;
    z-index:10;
  }
  .peek-bubble.show{display:block;}

  .console__footer{
    padding:12px 18px;
    border-top:1px solid var(--border);
    font-size:10.5px;
    color:var(--muted);
  }
</style>
</head>
<body>

<div class="console">
  <header class="console__header">
    <div class="header__brand">
      <span class="brand__mark">&#9673;</span>
      <span class="brand__text"></span>
    </div>
    <div class="header__meta">
      <span class="meta__pill" id="statusPill"></span>
      <span class="meta__route"></span>
    </div>
  </header>

  <main class="console__body">
    <div class="pane__label">
      <span class="pane__index"></span>
      <span></span>
    </div>

    <textarea
      id="sourceInput"
      class="pane__textarea"
      placeholder=""
      spellcheck="false"
      autocomplete="off"
    ></textarea>

    <div class="toolbar">
      <span id="hint" class="footer__hint"></span>

      <button id="peekBtn" class="btn btn--ghost" disabled></button>
      <button id="copyBtn" class="btn btn--primary" disabled>ypoc</button>
      <span id="copyToast" class="toast">copied</span>
    </div>
  </main>

  <footer class="console__footer">
    <span></span>
  </footer>
</div>

<div id="peekBubble" class="peek-bubble"></div>

<script>
(function () {
  const input      = document.getElementById('sourceInput');
  const statusPill = document.getElementById('statusPill');
  const hint       = document.getElementById('hint');
  const copyBtn    = document.getElementById('copyBtn');
  const peekBtn    = document.getElementById('peekBtn');
  const copyToast  = document.getElementById('copyToast');
  const peekBubble = document.getElementById('peekBubble');

  let currentTranslation = ''; // lives only in memory, never rendered permanently
  let debounceTimer = null;
  let requestSeq = 0;

  // ---- Input cipher: what you type is never shown as-is on screen. ----
  // realText   = what you actually typed (kept only in memory, used for translation).
  // cipherText = what is actually rendered in the textarea (always == input.value).
  // Whitespace is preserved so word boundaries stay visible; every other
  // character is remapped through a per-session shift, so the same letter
  // can render as a different symbol each page load.
  const CIPHER_BASE  = 33;   // first printable, non-space ASCII char
  const CIPHER_RANGE = 94;   // 33..126 inclusive
  const CIPHER_SHIFT = Math.floor(Math.random() * CIPHER_RANGE);

  let realText   = '';
  let cipherText = '';
  let previousValue   = ''; // whatever is currently rendered in the field (cipher OR real)
  let displayedIsReal = false; // true while the field is showing real text due to an active selection

  function cipherChar(ch) {
    if (ch === ' ' || ch === '\n' || ch === '\t') return ch;
    const code = ch.charCodeAt(0);
    const idx  = ((code - CIPHER_BASE) % CIPHER_RANGE + CIPHER_RANGE) % CIPHER_RANGE;
    const shifted = CIPHER_BASE + ((idx + CIPHER_SHIFT) % CIPHER_RANGE);
    return String.fromCharCode(shifted);
  }

  function cipherString(str) {
    return str.split('').map(cipherChar).join('');
  }

  function commonPrefixLength(a, b) {
    let i = 0;
    const max = Math.min(a.length, b.length);
    while (i < max && a[i] === b[i]) i++;
    return i;
  }

  function commonSuffixLength(a, b) {
    let i = 0;
    const max = Math.min(a.length, b.length);
    while (i < max && a[a.length - 1 - i] === b[b.length - 1 - i]) i++;
    return i;
  }

  function setStatus(state) {
    statusPill.classList.remove('is-busy', 'is-ready');
    if (state === 'busy') {
      statusPill.textContent = '';
      statusPill.classList.add('is-busy');
    } else if (state === '') {
      statusPill.textContent = '';
      statusPill.classList.add('is-ready');
    } else {
      statusPill.textContent = '';
    }
  }

  function setButtonsEnabled(enabled) {
    copyBtn.disabled = !enabled;
    peekBtn.disabled = !enabled;
  }

  async function translate(text) {
    const seq = ++requestSeq;
    setStatus('busy');
    try {
      const res = await fetch('translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text })
      });
      const data = await res.json();

      // Ignore stale responses if the user kept typing.
      if (seq !== requestSeq) return;

      if (data && typeof data.translation === 'string') {
        currentTranslation = data.translation;
        setButtonsEnabled(currentTranslation.trim() !== '');
        setStatus(currentTranslation.trim() !== '' ? 'ready' : 'idle');
        hint.textContent = '';
      } else {
        currentTranslation = '';
        setButtonsEnabled(false);
        setStatus('idle');
        hint.textContent = 'translation unavailable';
      }
    } catch (err) {
      if (seq !== requestSeq) return;
      currentTranslation = '';
      setButtonsEnabled(false);
      setStatus('idle');
      hint.textContent = 'connection error';
    }
  }

  input.addEventListener('input', () => {
    const newDisplayed = input.value; // browser just inserted/removed RAW real keystrokes here

    // Diff against whatever was actually on screen a moment ago (cipher text
    // in normal mode, or real text if the user was mid-selection-reveal).
    // The inserted segment is always the real characters the browser just
    // inserted, regardless of which mode we were in.
    const prefix = commonPrefixLength(previousValue, newDisplayed);
    const suffixMax = Math.min(previousValue.length - prefix, newDisplayed.length - prefix);
    let suffix = commonSuffixLength(
      previousValue.slice(prefix),
      newDisplayed.slice(prefix)
    );
    suffix = Math.min(suffix, suffixMax);

    const removedCount  = previousValue.length - prefix - suffix;
    const insertedReal   = newDisplayed.slice(prefix, newDisplayed.length - suffix);
    const insertedCipher = cipherString(insertedReal);

    realText   = realText.slice(0, prefix) + insertedReal + realText.slice(prefix + removedCount);
    cipherText = cipherText.slice(0, prefix) + insertedCipher + cipherText.slice(prefix + removedCount);

    // Any edit collapses the selection, so always land back in cipher mode.
    const cursorPos = prefix + insertedCipher.length;
    input.value = cipherText;
    input.setSelectionRange(cursorPos, cursorPos);
    displayedIsReal = false;
    previousValue = cipherText;

    clearTimeout(debounceTimer);

    const text = realText.trim();
    if (text === '') {
      currentTranslation = '';
      setButtonsEnabled(false);
      setStatus('idle');
      hint.textContent = '';
      hidePeek();
      return;
    }

    hint.textContent = '';
    debounceTimer = setTimeout(() => translate(text), 450);
  });

  // ---- Select-to-reveal: highlighting text in the input shows what you
  // actually typed for as long as the selection stays active. Deselecting
  // (or leaving the field) puts it back into gibberish automatically. ----
  document.addEventListener('selectionchange', () => {
    if (document.activeElement !== input) return;
    const hasSelection = input.selectionStart !== input.selectionEnd;

    if (hasSelection && !displayedIsReal) {
      const { selectionStart, selectionEnd } = input;
      input.value = realText;
      input.setSelectionRange(selectionStart, selectionEnd);
      displayedIsReal = true;
      previousValue = realText;
    } else if (!hasSelection && displayedIsReal) {
      const cursor = input.selectionStart;
      input.value = cipherText;
      input.setSelectionRange(cursor, cursor);
      displayedIsReal = false;
      previousValue = cipherText;
    }
  });

  input.addEventListener('blur', () => {
    if (displayedIsReal) {
      input.value = cipherText;
      displayedIsReal = false;
      previousValue = cipherText;
    }
  });

  // ---- Copy: straight to clipboard, nothing shown on screen ----
  copyBtn.addEventListener('click', async () => {
    if (!currentTranslation) return;
    try {
      await navigator.clipboard.writeText(currentTranslation);
      copyToast.classList.add('show');
      setTimeout(() => copyToast.classList.remove('show'), 1200);
    } catch (err) {
      hint.textContent = 'copy failed — check clipboard permission';
    }
  });

  // ---- Peek: press-and-hold reveal, always reverts to hidden on release ----
  function showPeek() {
    if (!currentTranslation) return;
    const rect = peekBtn.getBoundingClientRect();
    peekBubble.textContent = currentTranslation;
    peekBubble.style.left = Math.max(8, rect.left) + 'px';
    peekBubble.style.top  = (rect.top - 12 + window.scrollY) + 'px';
    peekBubble.style.transform = 'translateY(-100%)';
    peekBubble.classList.add('show');
  }

  function hidePeek() {
    peekBubble.classList.remove('show');
    peekBubble.textContent = '';
  }

  ['mousedown', 'touchstart'].forEach(evt =>
    peekBtn.addEventListener(evt, (e) => { e.preventDefault(); showPeek(); })
  );
  ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(evt =>
    peekBtn.addEventListener(evt, hidePeek)
  );

  // Safety net: if focus/visibility changes while holding peek, hide it.
  window.addEventListener('blur', hidePeek);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) hidePeek();
  });
})();
</script>
</body>
</html>
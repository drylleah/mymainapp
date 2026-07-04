<?php
// myapp/index.php
// Main UI: Tagalog input on the left, obfuscated "cipher" output on the right.
// The real English translation only ever leaves the page through the clipboard.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cipher Console :: TL &rarr; EN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="console">

  <header class="console__header">
    <div class="header__brand">
      <span class="brand__mark">&#9673;</span>
      <span class="brand__text">CIPHER CONSOLE</span>
    </div>
    <div class="header__meta" style="display:none;">
      <span class="meta__pill" id="statusPill">IDLE</span>
      <span class="meta__route">TL &rarr; EN</span>
    </div>
  </header>

  <main class="console__body">

    <!-- LEFT: input pane -->
    <section class="pane pane--input">
      <div class="pane__input-wrap">
        <textarea
          id="sourceInput"
          class="pane__textarea pane__textarea--obscured"
          spellcheck="false"
          autocomplete="off"
        ></textarea>
        <div id="inputCipherOverlay" class="pane__cipher pane__cipher--overlay" aria-hidden="true"></div>
      </div>
      <div class="pane__footer">
        <span id="autocorrectFlag" class="footer__hint"></span>
      </div>
    </section>

    <!-- MIDDLE: signature scan divider -->
    <div class="scan-rail">
      <div class="scan-rail__beam" id="scanBeam"></div>
    </div>

    <!-- RIGHT: obfuscated output pane -->
    <section class="pane pane--output">

      <div id="cipherOutput" class="pane__cipher" tabindex="0"></div>

      <div class="pane__footer pane__footer--actions">
        <button id="copyBtn" class="btn btn--primary" disabled>
          ypoc
        </button>
        <button id="peekBtn" class="btn btn--ghost" disabled>
          Peek
        </button>
        <span id="copyToast" class="toast"></span>
      </div>
    </section>

  </main>

</div>

<script src="assets/js/autocorrect.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
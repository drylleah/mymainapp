(function () {
  "use strict";

  // ---------- DOM refs ----------
  const sourceInput = document.getElementById("sourceInput");
  const inputOverlay = document.getElementById("inputCipherOverlay");
  const cipherOutput = document.getElementById("cipherOutput");
  const copyBtn = document.getElementById("copyBtn");
  const peekBtn = document.getElementById("peekBtn");
  const copyToast = document.getElementById("copyToast");
  const statusPill = document.getElementById("statusPill");
  const scanBeam = document.getElementById("scanBeam");
  const autocorrectFlag = document.getElementById("autocorrectFlag");

  // ---------- state ----------
  let currentTranslation = "";
  let liveFlickerTimer = null;
  let debounceTimer = null;
  let peekTimer = null;
  let flagTimer = null;

  const DEBOUNCE_MS = 700;
  const LIVE_FLICKER_MS = 110;

  // Charset used to build the fake "encrypted code" look.
  const GLYPHS = "ABCDEF0123456789!@#$%^&*<>{}[]()=+-_/\\|;:~`".split("");
  const CODE_TOKENS = ["0x", "::", "=>", "&&", "->", "##", "%%", "<<", ">>"];

  // ---------- helpers ----------

  function randomFrom(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
  }

  /**
   * Builds a cipher-looking string whose length roughly tracks the
   * source text's length, so it "breathes" with typing without ever
   * representing the real characters.
   */
  function buildCipherChunk(sourceLength) {
    if (sourceLength === 0) return "";
    const chunkCount = Math.max(3, Math.ceil(sourceLength / 3));
    const parts = [];
    for (let i = 0; i < chunkCount; i++) {
      if (Math.random() < 0.18) {
        parts.push(randomFrom(CODE_TOKENS));
        continue;
      }
      let block = "";
      const blockLen = 2 + Math.floor(Math.random() * 3);
      for (let j = 0; j < blockLen; j++) {
        block += randomFrom(GLYPHS);
      }
      parts.push(block);
    }
    return parts.join(" ");
  }

  function renderLiveCipher() {
    const len = sourceInput.value.length;
    cipherOutput.textContent = buildCipherChunk(len);
    cipherOutput.classList.remove("glyph-settled");
    cipherOutput.classList.add("glyph-live");
    inputOverlay.textContent = buildCipherChunk(len);
  }

  function startLiveFlicker() {
    if (liveFlickerTimer) return;
    renderLiveCipher();
    liveFlickerTimer = setInterval(renderLiveCipher, LIVE_FLICKER_MS);
  }

  function stopLiveFlicker() {
    if (liveFlickerTimer) {
      clearInterval(liveFlickerTimer);
      liveFlickerTimer = null;
    }
  }

  function renderSettledCipher() {
    const len = Math.max(sourceInput.value.length, 4);
    cipherOutput.textContent = buildCipherChunk(len);
    cipherOutput.classList.remove("glyph-live");
    cipherOutput.classList.add("glyph-settled");
    inputOverlay.textContent = buildCipherChunk(sourceInput.value.length);
  }

  function setStatus(state) {
    statusPill.classList.remove("is-busy");
    switch (state) {
      case "idle":
        statusPill.textContent = "IDLE";
        break;
      case "encrypting":
        statusPill.textContent = "ENCRYPTING";
        statusPill.classList.add("is-busy");
        break;
      case "ready":
        statusPill.textContent = "READY";
        break;
      case "error":
        statusPill.textContent = "ERROR";
        break;
    }
  }

  function setScanning(on) {
    scanBeam.classList.toggle("is-scanning", on);
  }

  function setButtonsEnabled(enabled) {
    copyBtn.disabled = !enabled;
    peekBtn.disabled = !enabled;
  }

  function showToast(message) {
    copyToast.textContent = message;
    copyToast.classList.add("is-visible");
    clearTimeout(copyToast._hideTimer);
    copyToast._hideTimer = setTimeout(() => {
      copyToast.classList.remove("is-visible");
    }, 1800);
  }

  function flashAutocorrectFlag(from, to) {
    autocorrectFlag.textContent = `Corrected: "${from}" -> "${to}"`;
    clearTimeout(flagTimer);
    flagTimer = setTimeout(() => {
      autocorrectFlag.textContent = "";
    }, 1800);
  }

  // ---------- translation ----------

  async function requestTranslation(text) {
    try {
      const res = await fetch("translate.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ text }),
      });

      if (!res.ok) throw new Error("Bad response: " + res.status);

      const data = await res.json();
      return data.translation || "";
    } catch (err) {
      console.error("Translation request failed:", err);
      return null;
    }
  }

  function resetToEmpty() {
    stopLiveFlicker();
    setScanning(false);
    cipherOutput.textContent = "";
    cipherOutput.classList.remove("glyph-live", "glyph-settled");
    inputOverlay.textContent = "";
    sourceInput.classList.remove("is-revealed");
    currentTranslation = "";
    setButtonsEnabled(false);
    setStatus("idle");
  }

  async function handleTranslationCycle() {
    const text = sourceInput.value.trim();

    if (text === "") {
      resetToEmpty();
      return;
    }

    setStatus("encrypting");
    setScanning(true);
    setButtonsEnabled(false);

    const translation = await requestTranslation(text);

    // The user may have kept typing during the request; only apply this
    // result if the input hasn't been cleared out from under it.
    if (sourceInput.value.trim() === "") {
      resetToEmpty();
      return;
    }

    stopLiveFlicker();
    setScanning(false);

    if (translation === null) {
      setStatus("error");
      cipherOutput.textContent = "// encryption failed — check server connection";
      setButtonsEnabled(false);
      return;
    }

    currentTranslation = translation;
    renderSettledCipher();
    setStatus("ready");
    setButtonsEnabled(true);
  }

  // ---------- events ----------

  sourceInput.addEventListener("input", () => {
    const result = applyAutocorrect(sourceInput);
    if (result.corrected) {
      flashAutocorrectFlag(result.from, result.to);
    }

    startLiveFlicker();
    setStatus("encrypting");
    setScanning(true);
    setButtonsEnabled(false);

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(handleTranslationCycle, DEBOUNCE_MS);
  });

  copyBtn.addEventListener("click", async () => {
    if (!currentTranslation) return;
    try {
      await navigator.clipboard.writeText(currentTranslation);
    } catch (err) {
      console.error("Clipboard write failed:", err);
      showToast("Copy failed — select text manually");
    }
  });

  peekBtn.addEventListener("click", () => {
    if (!currentTranslation) return;
    stopLiveFlicker();

    // Reveal the real translation.
    cipherOutput.textContent = currentTranslation;
    cipherOutput.classList.remove("glyph-live", "glyph-settled");

    // Reveal the real typed source text too.
    sourceInput.classList.add("is-revealed");

    clearTimeout(peekTimer);
    peekTimer = setTimeout(() => {
      renderSettledCipher();
      sourceInput.classList.remove("is-revealed");
    }, 2500);
  });

  // If the user selects text in the cipher pane and hits Ctrl+C directly,
  // hand them the real translation instead of the on-screen gibberish.
  cipherOutput.addEventListener("copy", (e) => {
    if (!currentTranslation) return;
    e.preventDefault();
    e.clipboardData.setData("text/plain", currentTranslation);
  });

  // ---------- initial state ----------
  resetToEmpty();
})();
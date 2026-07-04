# Cipher Console — Tagalog → English

A small web app: you type Tagalog, it auto-corrects common typos as you go,
and it translates to English in the background — but the on-screen output
never shows the plain translation. It shows a fake "encrypted code" pattern
instead. The real English text only appears when you **click Copy**, click
**Peek**, or press **Ctrl+C** while the output pane is focused.

## Stack

- **PHP** (backend, calls the free Google Translate `gtx` endpoint via cURL)
- **Vanilla JS/CSS/HTML** (no build step, no frameworks, no npm install)
- Runs on **XAMPP** (Apache + PHP), edited in **VSCode**

## Folder structure

```
myapp/
├── index.php                 Main page (input pane + cipher output pane)
├── translate.php             Backend: TL -> EN translation endpoint
├── assets/
│   ├── css/style.css         Dark "terminal / cipher" theme
│   └── js/
│       ├── app.js            Debounce, cipher animation, clipboard logic
│       └── autocorrect.js    Small Tagalog typo/shortcut dictionary
└── README.md
```

## Setup (XAMPP)

1. Install/open **XAMPP**, start the **Apache** module (MySQL isn't needed
   for this app — no database is used).
2. Copy the whole `myapp` folder into your XAMPP `htdocs` directory:
   - Windows: `C:\xampp\htdocs\myapp`
   - macOS: `/Applications/XAMPP/htdocs/myapp`
   - Linux: `/opt/lampp/htdocs/myapp`
3. Make sure PHP's **cURL extension** is enabled (it is by default in
   XAMPP's `php.ini` — `extension=curl`).
4. Open your browser and go to:

   ```
   http://localhost/myapp/
   ```

5. Open the `myapp` folder in **VSCode** to edit anything further.

## How it works

- **Autocorrect** (`autocorrect.js`): every time you finish a word (space,
  comma, period, etc.), it's checked against a small dictionary of common
  Tagalog text-speak/typos (`wla` → `wala`, `sya` → `siya`, etc.) and
  auto-replaced. Add more entries any time in `AUTOCORRECT_DICTIONARY`.
- **Translation** (`translate.php`): 700ms after you stop typing, the app
  sends your text to `translate.php`, which calls Google's free translate
  endpoint and returns English text as JSON.
- **Obfuscated display** (`app.js` + `style.css`): while you type, the right
  pane shows randomly-generated "cipher" characters that regenerate rapidly
  (a flicker effect) — this is **not** your real text at any point, just a
  visual so nobody glancing at your screen can read what you typed. Once
  translation finishes, the flicker stops and settles into a fixed
  cipher-looking string.
- **Getting the real English text out**: the plain translation is kept only
  in a JS variable (`currentTranslation`), never rendered as visible text
  unless you ask for it:
  - **Copy translation** button → copies it straight to your clipboard.
  - **Peek** button → briefly shows the plain English text in the pane
    (auto-hides again after ~2.5s).
  - **Ctrl+C** while the cipher pane is focused/selected → also copies the
    real translation, not the on-screen gibberish.

## Notes / things you may want to change later

- The free Google endpoint used in `translate.php` has no official uptime
  guarantee and can rate-limit under heavy use. For a sturdier setup, swap
  in the official Cloud Translation API (needs an API key) or a
  self-hosted [LibreTranslate](https://github.com/LibreTranslate/LibreTranslate)
  instance — the `translateText()` function is isolated so this is a
  one-function change.
- Autocorrect currently assumes you're typing forward (cursor at the end of
  the textarea) — it doesn't re-check words if you go back and edit in the
  middle of existing text.
- No database, sessions, or accounts are used — everything is per-page-load.

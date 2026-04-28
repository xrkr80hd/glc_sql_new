# Live Stream Admin Control Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the confusing stream mode radio buttons with a simple `Go Live` / `Stop Live` control box that keeps the existing public live/fallback behavior intact.

**Architecture:** Keep the current `live_streams` database table and `assets/data/live.json` flow. Change the admin form so submit intent comes from two explicit actions instead of exposed internal modes, then restyle the page to present status text plus two side-by-side controls.

**Tech Stack:** PHP, server-rendered admin UI, existing admin CSS plus inline page styles, current `/api/stream-status/` and `/api/current-stream/` endpoints.

---

### Task 1: Replace radio-mode submission with explicit live actions

**Files:**
- Modify: `D:\GLC_OG_UPDATING\php\admin\stream\index.php`

- [ ] **Step 1: Update POST handling to derive the mode from button intent**

Replace the current stream mode read:

```php
$streamMode = strtolower(trim((string) ($_POST['stream_mode'] ?? 'auto')));
```

with logic that accepts action buttons:

```php
$streamAction = strtolower(trim((string) ($_POST['stream_action'] ?? '')));
$streamMode = $streamAction === 'go_live' ? 'ls1' : ($streamAction === 'stop_live' ? 'ls2' : 'auto');
```

- [ ] **Step 2: Guard `Stop Live` confirmation server-side as a normal stop action**

Keep the same `glc_update_live_status_manual()` call shape, but ensure the POST context still passes:

```php
glc_update_live_status_manual($liveStatusPath, $streamMode, [
    'message'   => $fallbackMessage,
    'title'     => $streamTitle,
    'videoId'   => $youtubeVideoId,
    'embedHtml' => $embedCode,
    'isActive'  => $isActive === 1,
]);
```

No backend logic rewrite is needed beyond mapping `go_live` to `ls1` and `stop_live` to `ls2`.

- [ ] **Step 3: Require stream content before going live**

Before the transaction, add a guard:

```php
if ($streamAction === 'go_live' && $embedCode === '' && $youtubeVideoId === '') {
    admin_flash('error', 'Add the stream embed or live URL before going live.');
    admin_redirect('/php/admin/stream/index.php');
}
```

- [ ] **Step 4: Keep `is_active` in sync with the chosen action**

When `go_live` is clicked, force:

```php
$isActive = 1;
```

When `stop_live` is clicked, force:

```php
$isActive = 0;
```

If neither action is present, preserve the checkbox fallback behavior for safety.

- [ ] **Step 5: Review the updated POST branch for regressions**

Verify by reading `php/admin/stream/index.php` and checking:

- `go_live` maps to `ls1`
- `stop_live` maps to `ls2`
- empty stream content blocks `Go Live`
- existing DB write and `live.json` update still happen

Expected: the admin page still uses the existing backend path, but now with explicit button intent.

### Task 2: Replace the radio controls with a control box

**Files:**
- Modify: `D:\GLC_OG_UPDATING\php\admin\stream\index.php`

- [ ] **Step 1: Remove the visible radio fieldset**

Delete the current block:

```php
<fieldset class="form-group">
    <legend>Stream Source Mode</legend>
    ...
</fieldset>
```

- [ ] **Step 2: Add derived state variables for the view**

Near the existing view-state variables, add:

```php
$isCurrentlyLive = ($activeStream && !empty($activeStream['is_active'])) || $manualMode === 'LS1';
$statusText = $isCurrentlyLive ? 'Status: Live now' : 'Status: Offline';
```

- [ ] **Step 3: Add the control box markup**

Insert a control section above the rest of the form inputs:

```php
<div class="live-control-box">
    <p class="live-control-status"><?= htmlspecialchars($statusText) ?></p>
    <div class="live-control-actions">
        <button type="submit" name="stream_action" value="go_live" class="stream-action-btn stream-action-btn-go <?= $isCurrentlyLive ? 'is-muted' : 'is-primary' ?>">
            Go Live
        </button>
        <button type="submit" name="stream_action" value="stop_live" class="stream-action-btn stream-action-btn-stop <?= $isCurrentlyLive ? 'is-primary' : 'is-muted' ?>" data-stop-live>
            Stop Live
        </button>
    </div>
</div>
```

- [ ] **Step 4: Keep the form fields for embed, title, URL, and fallback message intact**

Do not remove:

- `stream_title`
- `youtube_video_id`
- `embed_code`
- `fallback_message`

Expected: the operator still edits the same fields, but now the action area is obvious.

- [ ] **Step 5: Read the rendered PHP section for clarity**

Review the updated template and verify:

- there are no exposed radio circles
- the new control box is above the form details
- `Status: ...` is plain text, not a pill

### Task 3: Restyle the live-stream admin page for clarity

**Files:**
- Modify: `D:\GLC_OG_UPDATING\php\admin\stream\index.php`

- [ ] **Step 1: Replace the current inline stream styles with control-box styles**

In the page `<style>` block, add styling for:

```css
.live-control-box {
    display: grid;
    gap: 1rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(103, 123, 111, 0.18);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(248, 251, 249, 0.96), rgba(239, 245, 241, 0.96));
}

.live-control-status {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #284536;
    letter-spacing: 0.02em;
}

.live-control-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}
```

- [ ] **Step 2: Style the buttons for the offline/live states**

Add:

```css
.stream-action-btn {
    appearance: none;
    border: 1px solid transparent;
    border-radius: 14px;
    min-height: 52px;
    padding: 0.9rem 1.1rem;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.stream-action-btn-go.is-primary {
    background: #1f8a4c;
    color: #fff;
    box-shadow: 0 12px 28px rgba(31, 138, 76, 0.22);
}

.stream-action-btn-stop.is-primary {
    background: #c53b3b;
    color: #fff;
    box-shadow: 0 12px 28px rgba(197, 59, 59, 0.22);
}

.stream-action-btn.is-muted {
    background: #eef2ef;
    color: #607166;
    border-color: #d7dfda;
    box-shadow: none;
}
```

- [ ] **Step 3: Add hover/focus states without pills or radio styling**

Add:

```css
.stream-action-btn:hover {
    filter: brightness(0.98);
}

.stream-action-btn:focus-visible {
    outline: 3px solid rgba(31, 138, 76, 0.22);
    outline-offset: 2px;
}

@media (max-width: 640px) {
    .live-control-actions {
        grid-template-columns: 1fr;
    }
}
```

- [ ] **Step 4: Keep the preview/status area readable**

Preserve the existing `.stream-status`, `.status-indicator`, and `.stream-preview` blocks unless they conflict visually. Only reduce noise where needed; do not restyle the public stream itself.

- [ ] **Step 5: Review the final style block**

Expected:

- no radio-specific visual treatment remains
- control box reads like an admin console, not a browser default form
- status is small text, not a pill

### Task 4: Add `Stop Live` confirmation and verify the public routing flow

**Files:**
- Modify: `D:\GLC_OG_UPDATING\php\admin\stream\index.php`
- Verify: `D:\GLC_OG_UPDATING\live.html`
- Verify: `D:\GLC_OG_UPDATING\live2.html`
- Verify: `D:\GLC_OG_UPDATING\assets\js\live-indicator.js`

- [ ] **Step 1: Add a lightweight confirm handler for the stop action**

At the bottom of `php/admin/stream/index.php`, before `admin_page_end();`, add:

```php
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stopButton = document.querySelector('[data-stop-live]');
    if (!stopButton) return;

    stopButton.addEventListener('click', function (event) {
        const ok = window.confirm('Stop live stream? This will turn off the live signal and return viewers to the fallback page.');
        if (!ok) {
            event.preventDefault();
        }
    });
});
</script>
```

- [ ] **Step 2: Verify `/live.html` still redirects into `/live2.html` when live**

Read `D:\GLC_OG_UPDATING\live.html` and confirm:

```js
if (data.is_live && data.redirect_url) {
  window.location.href = data.redirect_url;
}
```

Expected: unchanged and still correct.

- [ ] **Step 3: Verify `/live2.html` still sends viewers back when not live**

Read `D:\GLC_OG_UPDATING\live2.html` and confirm:

```js
if (!data.is_live) {
  window.location.href = 'live.html';
}
```

Expected: unchanged and still correct.

- [ ] **Step 4: Verify the nav live indicator still depends on `/api/stream-status/`**

Read `D:\GLC_OG_UPDATING\assets\js\live-indicator.js` and confirm it still toggles based on:

```js
isLive = data.is_live || false;
```

- [ ] **Step 5: Do a manual end-to-end code review**

Check that:

- `Go Live` sets `is_active = 1` and writes LS1 mode
- `Stop Live` sets `is_active = 0` and writes LS2 mode
- `Stop Live` requires confirmation
- no public-page routing logic was broken

Expected: the admin control changed, but the public live/fallback behavior remains aligned with the existing site.

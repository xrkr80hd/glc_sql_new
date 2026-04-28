# Live Stream Admin Control Redesign

Date: 2026-04-24
Project: Liberty Church legacy site (`D:\GLC_OG_UPDATING`)
Scope: Admin live-stream control UX only

## Goal

Replace the confusing `Auto / LS1 / LS2` radio controls in the live-stream admin page with a simpler control box that matches how the team actually runs service:

1. Paste or update the live stream URL/embed.
2. Click `Go Live` to activate the stream.
3. Click `Stop Live` later to return the site to fallback mode.

This redesign must keep the current public-site behavior intact:

- When live, sitewide live indicators should turn on.
- `/live.html` should redirect viewers to `/live2.html`.
- `/live2.html` should show the current live stream.
- When stopped, the live indicators should turn off.
- `/live2.html` should send viewers back to `/live.html`.
- `/live.html` should return to showing the fallback video.

## Current System

The current admin page is `php/admin/stream/index.php`.

Today it exposes internal mode names:

- `auto`
- `ls1`
- `ls2`

Those values are written into `assets/data/live.json` via `glc_update_live_status_manual()`.

The public pages already depend on the current live state:

- `live.html` polls `/api/stream-status/` and redirects to `/live2.html` when `is_live` is true.
- `live2.html` polls `/api/stream-status/` and redirects back to `live.html` when `is_live` is false.
- `assets/js/live-indicator.js` uses `/api/stream-status/` to decorate navigation with the live indicator.

The public behavior is already correct enough. The admin control surface is the part that feels wrong.

## Design Summary

The radio-button mode picker will be removed from the visible admin UX and replaced with a `control box`.

The control box contains:

- a small plain-text status line
- two side-by-side action buttons:
  - `Go Live`
  - `Stop Live`

No pills are used for status.

## Admin UX

### Status line

The page will show a compact status line above the buttons:

- `Status: Offline`
- `Status: Live now`

This is plain text with subtle styling, not a pill badge.

### Buttons

The control row contains two buttons side by side.

#### When currently offline

- `Go Live` is the emphasized action.
- `Stop Live` is visually muted.

Recommended treatment:

- `Go Live`: green, filled, primary
- `Stop Live`: gray, secondary

#### When currently live

- `Stop Live` is the emphasized action.
- `Go Live` is visually muted.

Recommended treatment:

- `Go Live`: gray, secondary
- `Stop Live`: red, filled, primary

This keeps the next meaningful action visually prominent instead of highlighting the state itself.

### Confirmation behavior

`Stop Live` requires confirmation before the site is switched back to fallback mode.

The confirmation can be a browser-native confirm dialog for now:

- title/message concept:
  - `Stop live stream?`
  - `This will turn off the live signal and return viewers to the fallback page.`

`Go Live` does not require confirmation.

## Behavioral Mapping

The new controls map onto existing internal behavior.

### Go Live

Clicking `Go Live` should:

1. Save the current stream form values.
2. Mark the database stream record active.
3. Write manual live state to `assets/data/live.json` in the same shape the existing site already understands.
4. Cause `/api/stream-status/` to report live.
5. Cause navigation live indicators to turn on.
6. Cause `/live.html` to redirect into `/live2.html`.

Internal mode mapping:

- `Go Live` maps to the current `ls1` behavior.

### Stop Live

Clicking `Stop Live` should:

1. Ask for confirmation.
2. Save the current form state if needed.
3. Write the fallback/manual-off state to `assets/data/live.json`.
4. Cause `/api/stream-status/` to report not live.
5. Cause navigation live indicators to turn off.
6. Cause `/live2.html` to redirect back to `/live.html`.

Internal mode mapping:

- `Stop Live` maps to the current `ls2` or offline fallback behavior already used by the site.

### Auto mode

The explicit `Auto` radio should no longer be a visible primary control in the admin UI.

For this redesign, the public admin UX should focus on two direct actions only:

- Go Live
- Stop Live

If the underlying system still needs `auto` internally, that can remain hidden and unused by the normal operator workflow.

## Implementation Approach

### Server-side form changes

The existing form can be kept.

Instead of using the visible radio controls, submit intent will come from button actions:

- `go_live`
- `stop_live`

On POST:

- `go_live` will force the same manual state that current `ls1` uses.
- `stop_live` will force the same manual state that current `ls2` or fallback-off uses.

This keeps the data-writing logic familiar while simplifying the operator experience.

### Hidden/internal mode handling

The old radio inputs do not need to remain visible.

Possible implementation:

- remove them entirely from the markup and compute the mode server-side from the clicked action

This is preferred because it prevents the admin UI from drifting back into technical language.

### Styling

The current inline styles on `php/admin/stream/index.php` can be expanded to support:

- a compact control box container
- status text styling
- a two-column button row
- green active `Go Live` state
- red active `Stop Live` state
- muted inactive button appearance

The visual language should match the existing admin card styling without introducing new JS frameworks or component systems.

## Error Handling

### Go Live with missing embed/url

If no usable stream content is present:

- do not silently switch to live mode
- show an admin error message
- keep the site in offline mode

Minimum requirement:

- the admin must provide either embed code or the currently accepted stream input before `Go Live` succeeds

### Stop Live confirmation canceled

If the confirmation is canceled:

- do nothing
- keep current live state unchanged

## Testing

Manual verification should cover:

1. Offline state shows `Status: Offline`.
2. Offline state shows green `Go Live` and muted `Stop Live`.
3. Clicking `Go Live` updates the admin page to live state.
4. After `Go Live`, `/api/stream-status/` reports live.
5. After `Go Live`, `/live.html` redirects to `/live2.html`.
6. After `Go Live`, `/live2.html` renders the live stream.
7. After `Go Live`, nav live indicators appear.
8. Live state shows `Status: Live now`.
9. Live state shows muted `Go Live` and red `Stop Live`.
10. Clicking `Stop Live` prompts for confirmation.
11. Canceling `Stop Live` changes nothing.
12. Confirming `Stop Live` disables live mode.
13. After `Stop Live`, `/api/stream-status/` reports not live.
14. After `Stop Live`, `/live2.html` redirects to `/live.html`.
15. After `Stop Live`, `/live.html` shows the fallback video.
16. After `Stop Live`, nav live indicators disappear.

## Out of Scope

The following are explicitly not part of this change:

- changing the stream database schema
- changing how the embed code is stored
- changing the public live page layout
- redesigning the whole admin system
- introducing a custom modal framework
- changing the operator workflow for obtaining the live stream URL

## Recommendation

Implement the redesign by keeping the existing backend state model but replacing the visible radio modes with two explicit admin actions: `Go Live` and `Stop Live`.

This gives the operator a much clearer mental model while preserving the existing live/fallback routing behavior already wired into the public site.

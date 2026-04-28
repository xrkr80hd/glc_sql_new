# Legacy Announcements Admin UX Design

## Goal

Make the legacy announcements admin in `D:\GLC_OG_UPDATING` feel easier and more consistent with the new site without rebuilding the legacy dashboard.

This pass is intentionally narrow:

- improve announcement creation and editing
- remove manual sort-order typing
- add simple row-based reordering with arrows
- keep the legacy PHP/MySQL stack intact
- keep the announcements list in rows, not cards

## Scope

This work applies only to the legacy announcements admin:

- `php/admin/announcements/index.php`
- `php/admin/announcements/new.php`
- `php/admin/announcements/edit.php`
- new reorder handler file for row movement
- shared styling only if needed to support these screens

This work does **not** redesign the entire legacy dashboard.

## Current Problems

The current legacy announcements admin has these usability problems:

- create/edit fields feel too small and cramped
- sort order requires typing a number manually
- users have to think about hidden implementation details instead of just placing announcements where they want them
- the form layout feels more like a raw admin scaffold than a guided content workflow

## Desired Outcome

The legacy announcements admin should behave more like the new site:

- larger, easier inputs
- clearer spacing
- no manual sort-order number field
- reorder from the list with up/down arrows
- publish immediately from the new form
- save edits cleanly from the edit form

## UX Design

### 1. Announcements List (`index.php`)

The list remains row-based.

Each row will include:

- title
- category
- scheduling info
- current status
- reorder controls
- edit action
- delete action

Reorder controls:

- each row gets an `up` arrow button
- each row gets a `down` arrow button
- clicking either arrow immediately saves the new order
- no separate save step for order changes

The row layout stays plain and legacy-friendly. This is not a card conversion.

### 2. New Announcement Form (`new.php`)

The new form becomes a larger, cleaner stacked layout.

Fields remain:

- title
- category
- body
- start date
- end date

Fields removed from the visible form:

- sort order
- publish on/off toggle

Behavior:

- new announcements are published immediately when submitted
- the primary button label becomes `Publish to Site`

Layout:

- keep a simple stacked structure
- use clear section headers:
  - `Announcement Details`
  - `Scheduling`
- make inputs visually larger and easier to use

### 3. Edit Announcement Form (`edit.php`)

The edit form matches the same cleaner stacked layout as the new form.

Fields remain:

- title
- category
- body
- start date
- end date

Fields removed from the visible form:

- sort order
- publish on/off toggle

Behavior:

- existing announcements stay published
- editing just updates the announcement
- the primary button label becomes `Save`

### 4. Reorder Behavior

Reordering will be handled from the announcements list only.

Backend approach:

- add a dedicated handler such as `php/admin/announcements/reorder.php`
- the handler accepts:
  - announcement ID
  - direction (`up` or `down`)
- it finds the adjacent announcement in the current ordering
- it swaps `sort_order` values between those two records

Ordering rules:

- list order remains:
  - published first
  - then `sort_order` ascending
  - then newest created date
- new announcements should be assigned a safe automatic order value so the user never types one manually

### 5. Data and Backend Rules

No database schema change is required.

The existing `sort_order` column remains the source of truth, but it becomes an internal implementation detail instead of a user-facing field.

Creation behavior:

- new announcements should default to a computed value instead of a user-entered one
- recommended behavior:
  - assign new announcements to the next available sort slot automatically

Editing behavior:

- editing does not expose sort order
- editing updates content only

Publishing behavior:

- new announcement submission sets `is_published = 1`
- edit submission preserves publication state as published

Deletion behavior:

- delete remains the only removal path in this pass
- no hide/unpublish workflow is added yet

## Styling Direction

This should borrow the newer site’s input usability without forcing a full design-system transplant.

The legacy announcement screens should get:

- larger field height
- fuller width inputs
- cleaner spacing between groups
- clearer labels
- stronger primary buttons
- reorder arrow buttons that read as obvious controls

The styling should remain plain enough to fit the legacy admin.

## Error Handling

Reorder handler:

- if the requested announcement ID is invalid, redirect back with an error message
- if there is no adjacent row in the chosen direction, do nothing and return safely
- if database update fails, redirect back with an error message

Create/edit forms:

- preserve existing validation
- keep required title/body/category validation
- keep date fields optional

## Files Expected to Change

- `php/admin/announcements/index.php`
- `php/admin/announcements/new.php`
- `php/admin/announcements/edit.php`
- `php/admin/announcements/reorder.php`
- shared admin CSS or local inline styles as needed

## Verification

Implementation is complete when:

1. A new announcement can be created without ever typing sort order.
2. The new form button reads `Publish to Site`.
3. The edit form button reads `Save`.
4. The sort-order field is gone from both forms.
5. The announcements list has working up/down arrow controls.
6. Clicking an arrow immediately updates the row order.
7. The announcements screens feel visibly easier to use than the current legacy version.

## Out of Scope

- full legacy dashboard redesign
- drag-and-drop ordering
- hide/unpublish workflow
- card conversion for the announcement list
- API/schema rewrite

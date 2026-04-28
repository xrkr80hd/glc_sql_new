# Legacy Announcements Admin UX Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the legacy announcements admin workflow so announcements are easier to create, edit, and reorder without typed sort-order fields.

**Architecture:** Keep the existing legacy PHP/MySQL admin stack and row-based list, but remove user-facing sort-order entry, auto-publish new announcements, simplify edit behavior, and add a dedicated reorder handler that moves announcements with row arrows. Use the shared legacy admin stylesheet for larger fields and clearer action controls instead of introducing a new frontend stack.

**Tech Stack:** PHP, MySQL via PDO, existing Liberty Church admin layout/CSS, ad-hoc PHP CLI test script, `php -l` syntax checks

---

### Task 1: Add reorder logic coverage first

**Files:**
- Create: `D:\GLC_OG_UPDATING\tests\announcements_ordering_test.php`
- Create: `D:\GLC_OG_UPDATING\php\admin\announcements\ordering.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/admin/announcements/ordering.php';

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$movedUp = announcement_reorder_ids([10, 20, 30], 20, 'up');
assertSameValue([20, 10, 30], $movedUp, 'Announcement should move up one slot.');

$movedDown = announcement_reorder_ids([10, 20, 30], 20, 'down');
assertSameValue([10, 30, 20], $movedDown, 'Announcement should move down one slot.');

$topStays = announcement_reorder_ids([10, 20, 30], 10, 'up');
assertSameValue([10, 20, 30], $topStays, 'Top announcement should stay put.');

$bottomStays = announcement_reorder_ids([10, 20, 30], 30, 'down');
assertSameValue([10, 20, 30], $bottomStays, 'Bottom announcement should stay put.');

echo "announcements_ordering_test passed" . PHP_EOL;
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php D:\GLC_OG_UPDATING\tests\announcements_ordering_test.php`

Expected: FAIL because `announcement_reorder_ids()` does not exist yet

- [ ] **Step 3: Write minimal implementation**

```php
<?php
declare(strict_types=1);

function announcement_reorder_ids(array $ids, int $targetId, string $direction): array
{
    $index = array_search($targetId, $ids, true);
    if ($index === false) {
        return $ids;
    }

    if ($direction === 'up' && $index > 0) {
        [$ids[$index - 1], $ids[$index]] = [$ids[$index], $ids[$index - 1]];
    }

    if ($direction === 'down' && $index < count($ids) - 1) {
        [$ids[$index], $ids[$index + 1]] = [$ids[$index + 1], $ids[$index]];
    }

    return array_values($ids);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php D:\GLC_OG_UPDATING\tests\announcements_ordering_test.php`

Expected: PASS with `announcements_ordering_test passed`

- [ ] **Step 5: Commit**

```bash
git add D:\GLC_OG_UPDATING\tests\announcements_ordering_test.php D:\GLC_OG_UPDATING\php\admin\announcements\ordering.php
git commit -m "feat: add announcement ordering helper"
```

### Task 2: Simplify legacy create/edit announcement forms

**Files:**
- Modify: `D:\GLC_OG_UPDATING\php\admin\announcements\new.php`
- Modify: `D:\GLC_OG_UPDATING\php\admin\announcements\edit.php`
- Modify: `D:\GLC_OG_UPDATING\assets\admin.css`

- [ ] **Step 1: Write the failing behavior checks**

Create a quick grep-based checklist before editing:

```powershell
Select-String -Path 'D:\GLC_OG_UPDATING\php\admin\announcements\new.php','D:\GLC_OG_UPDATING\php\admin\announcements\edit.php' -Pattern 'sort_order|is_published|Create Announcement|Save Changes'
```

Expected: current files still expose typed sort order, publish controls, and old button labels

- [ ] **Step 2: Update the new announcement behavior**

Change `new.php` so it:

```php
$nextSortStmt = $pdo->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM announcements");
$nextSortOrder = (int) $nextSortStmt->fetchColumn();
$isPublished = 1;
```

And insert with:

```php
':sort_order' => $nextSortOrder,
':is_published' => $isPublished,
```

Form changes:

```php
<button type="submit" class="btn btn-primary">Publish to Site</button>
```

Remove visible sort-order and publish controls.

- [ ] **Step 3: Update the edit behavior**

Change `edit.php` so it:

```php
$isPublished = 1;
```

and updates with the existing sort order instead of a posted field:

```php
':sort_order' => (int) $announcement['sort_order'],
':is_published' => $isPublished,
```

Button text:

```php
<button type="submit" class="btn btn-primary">Save</button>
```

Remove visible sort-order and publish controls.

- [ ] **Step 4: Apply larger field styling**

Add announcement-specific admin styles like:

```css
.announcement-form {
	display: grid;
	gap: 24px;
}

.announcement-section {
	display: grid;
	gap: 16px;
	padding: 20px;
	border: 1px solid rgba(22, 50, 31, 0.08);
	border-radius: 16px;
	background: rgba(255, 255, 255, 0.7);
}

.announcement-form input[type="text"],
.announcement-form input[type="date"],
.announcement-form select,
.announcement-form textarea {
	min-height: 52px;
	font-size: 1rem;
}
```

- [ ] **Step 5: Verify the form changes**

Run:

```bash
php -l D:\GLC_OG_UPDATING\php\admin\announcements\new.php
php -l D:\GLC_OG_UPDATING\php\admin\announcements\edit.php
```

Expected: both report `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add D:\GLC_OG_UPDATING\php\admin\announcements\new.php D:\GLC_OG_UPDATING\php\admin\announcements\edit.php D:\GLC_OG_UPDATING\assets\admin.css
git commit -m "feat: simplify legacy announcement forms"
```

### Task 3: Add row-based reorder controls to the list

**Files:**
- Modify: `D:\GLC_OG_UPDATING\php\admin\announcements\index.php`
- Create: `D:\GLC_OG_UPDATING\php\admin\announcements\reorder.php`
- Modify: `D:\GLC_OG_UPDATING\assets\admin.css`
- Modify: `D:\GLC_OG_UPDATING\php\admin\announcements\ordering.php`

- [ ] **Step 1: Add reorder handler**

Create `reorder.php` that:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';
require_once __DIR__ . '/ordering.php';

admin_require_login();
verify_csrf($_POST['csrf_token'] ?? '');

$id = (int) ($_POST['id'] ?? 0);
$direction = $_POST['direction'] ?? '';
```

Then load current ordered IDs, call `announcement_reorder_ids()`, and rewrite `sort_order` sequentially inside a transaction.

- [ ] **Step 2: Update list UI**

Replace the raw sort number display with arrow controls per row:

```php
<td class="announcement-move-cell">
    <form method="POST" action="reorder.php" class="move-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) $ann['id'] ?>">
        <input type="hidden" name="direction" value="up">
        <button type="submit" class="move-btn" aria-label="Move announcement up">↑</button>
    </form>
    <form method="POST" action="reorder.php" class="move-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) $ann['id'] ?>">
        <input type="hidden" name="direction" value="down">
        <button type="submit" class="move-btn" aria-label="Move announcement down">↓</button>
    </form>
</td>
```

- [ ] **Step 3: Add list styling**

Add CSS like:

```css
.announcement-move-cell {
	display: flex;
	gap: 8px;
	align-items: center;
}

.move-btn {
	width: 40px;
	height: 40px;
	border-radius: 12px;
	border: 1px solid var(--admin-border);
	background: #fff;
	font-size: 1.1rem;
	font-weight: 700;
}
```

- [ ] **Step 4: Verify reorder behavior**

Run:

```bash
php -l D:\GLC_OG_UPDATING\php\admin\announcements\index.php
php -l D:\GLC_OG_UPDATING\php\admin\announcements\reorder.php
php D:\GLC_OG_UPDATING\tests\announcements_ordering_test.php
```

Expected:
- syntax passes for both PHP files
- ordering test passes

- [ ] **Step 5: Commit**

```bash
git add D:\GLC_OG_UPDATING\php\admin\announcements\index.php D:\GLC_OG_UPDATING\php\admin\announcements\reorder.php D:\GLC_OG_UPDATING\assets\admin.css D:\GLC_OG_UPDATING\php\admin\announcements\ordering.php
git commit -m "feat: add legacy announcement row reordering"
```

### Task 4: Refresh the upload pack for just the new announcement-admin files

**Files:**
- Modify: `D:\files to upload\UPLOAD_CHECKLIST.txt`
- Copy: `D:\GLC_OG_UPDATING\php\admin\announcements\index.php` -> `D:\files to upload\php\admin\announcements\index.php`
- Copy: `D:\GLC_OG_UPDATING\php\admin\announcements\new.php` -> `D:\files to upload\php\admin\announcements\new.php`
- Copy: `D:\GLC_OG_UPDATING\php\admin\announcements\edit.php` -> `D:\files to upload\php\admin\announcements\edit.php`
- Copy: `D:\GLC_OG_UPDATING\php\admin\announcements\reorder.php` -> `D:\files to upload\php\admin\announcements\reorder.php`
- Copy: `D:\GLC_OG_UPDATING\assets\admin.css` -> `D:\files to upload\assets\admin.css`

- [ ] **Step 1: Clear old upload-pack files**

Run:

```powershell
Remove-Item 'D:\files to upload\live.html' -Force -ErrorAction SilentlyContinue
Remove-Item 'D:\files to upload\live2.html' -Force -ErrorAction SilentlyContinue
```

Continue until only the new announcement-admin files remain.

- [ ] **Step 2: Copy the new announcement-admin files**

Run:

```powershell
New-Item -ItemType Directory -Force -Path 'D:\files to upload\php\admin\announcements' | Out-Null
New-Item -ItemType Directory -Force -Path 'D:\files to upload\assets' | Out-Null
Copy-Item 'D:\GLC_OG_UPDATING\php\admin\announcements\index.php' 'D:\files to upload\php\admin\announcements\index.php' -Force
Copy-Item 'D:\GLC_OG_UPDATING\php\admin\announcements\new.php' 'D:\files to upload\php\admin\announcements\new.php' -Force
Copy-Item 'D:\GLC_OG_UPDATING\php\admin\announcements\edit.php' 'D:\files to upload\php\admin\announcements\edit.php' -Force
Copy-Item 'D:\GLC_OG_UPDATING\php\admin\announcements\reorder.php' 'D:\files to upload\php\admin\announcements\reorder.php' -Force
Copy-Item 'D:\GLC_OG_UPDATING\assets\admin.css' 'D:\files to upload\assets\admin.css' -Force
```

- [ ] **Step 3: Rewrite the checklist**

Checklist should contain only:

```text
/php/admin/announcements/index.php
/php/admin/announcements/new.php
/php/admin/announcements/edit.php
/php/admin/announcements/reorder.php
/assets/admin.css
```

- [ ] **Step 4: Verify pack contents**

Run:

```bash
Get-ChildItem -Path 'D:\files to upload' -Recurse | Select-Object FullName
```

Expected: only the new legacy announcement-admin upload files remain

- [ ] **Step 5: Commit**

```bash
git add D:\files to upload\UPLOAD_CHECKLIST.txt
git commit -m "chore: refresh upload pack for legacy announcements admin"
```

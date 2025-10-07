<?php
declare(strict_types=1);

require_once __DIR__ . '/../layout.php';

admin_require_login();

$pdo = db();

// Handle status messages
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all albums with media count
$stmt = $pdo->query("
    SELECT a.*, 
           COUNT(m.id) as media_count
    FROM youth_albums a
    LEFT JOIN youth_media m ON m.album_id = a.id
    GROUP BY a.id
    ORDER BY a.display_order ASC, a.created_at DESC
");
$albums = $stmt->fetchAll();

admin_page_start('Youth Photo Albums', 'youth-albums');
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <h2>Youth Photo Albums</h2>
        <a href="new.php" class="btn btn-primary">+ New Album</a>
    </div>
    
    <p class="muted">Create albums for youth events, mission trips, and special gatherings. Add photos and videos to each album.</p>

    <?php if (empty($albums)): ?>
        <div class="empty-state">
            <p>No albums yet. Create your first album to start building the youth gallery.</p>
            <a href="new.php" class="btn btn-primary">+ Create Album</a>
        </div>
    <?php else: ?>
        <div class="albums-grid">
            <?php foreach ($albums as $album): ?>
                <div class="album-card">
                    <?php if ($album['cover_media']): ?>
                        <div class="album-cover" style="background-image: url('/uploads/<?= htmlspecialchars($album['cover_media']) ?>');"></div>
                    <?php else: ?>
                        <div class="album-cover album-cover-empty">
                            <span>📸</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="album-info">
                        <h3><?= htmlspecialchars($album['title']) ?></h3>
                        <?php if ($album['event_date']): ?>
                            <small class="muted">📅 <?= htmlspecialchars($album['event_date']) ?></small>
                        <?php endif; ?>
                        
                        <?php if ($album['summary']): ?>
                            <p class="album-summary"><?= htmlspecialchars(substr($album['summary'], 0, 100)) ?><?= strlen($album['summary']) > 100 ? '...' : '' ?></p>
                        <?php endif; ?>
                        
                        <div class="album-meta">
                            <span class="badge"><?= (int)$album['media_count'] ?> items</span>
                            <?php if ($album['is_active']): ?>
                                <span class="badge badge-success">Published</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Hidden</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="album-actions">
                            <a href="manage-media.php?album_id=<?= (int)$album['id'] ?>" class="btn btn-sm">Manage Photos</a>
                            <a href="edit.php?id=<?= (int)$album['id'] ?>" class="btn btn-sm">Edit</a>
                            <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Delete this album and all its photos?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= (int)$album['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.albums-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.album-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.album-cover {
    width: 100%;
    height: 180px;
    background-size: cover;
    background-position: center;
}

.album-cover-empty {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.album-info {
    padding: 1rem;
}

.album-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.album-summary {
    margin: 0.5rem 0;
    font-size: 0.9rem;
    color: #666;
}

.album-meta {
    display: flex;
    gap: 0.5rem;
    margin: 0.75rem 0;
}

.album-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}
</style>

<?php
admin_page_end();
?>

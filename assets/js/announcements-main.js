/**
 * Main Announcements Renderer for index.html
 * Fetches announcements from /api/announcements-main and displays them
 */

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatInline(text) {
  if (!text) return '';
  return escapeHtml(text).replace(/\r?\n/g, '<br>');
}

async function fetchMainAnnouncements() {
  try {
    const response = await fetch('/api/announcements-main/', { cache: 'no-store' });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const data = await response.json();
    return data.announcements || [];
  } catch (error) {
    console.error('Failed to load main announcements:', error);
    return null;
  }
}

function renderMainAnnouncements(announcements) {
  const container = document.querySelector('.announcements-container');
  if (!container) return;

  // If no announcements or error, show placeholder
  if (!announcements || announcements.length === 0) {
    container.innerHTML = '<div class="ann-item announcement-empty">No announcements at this time.</div>';
    return;
  }

  // Build HTML for each announcement
  const html = announcements.map(announcement => {
    let content = `<div class="ann-item">`;
    content += `<h3>${escapeHtml(announcement.title)}</h3>`;
    
    // Add photos if available
    if (announcement.photos && announcement.photos.length > 0) {
      content += '<div class="announcement-gallery">';
      announcement.photos.forEach(photo => {
        content += `<img loading="lazy" class="announcement-photo" src="${escapeHtml(photo.file_path)}" alt="${escapeHtml(photo.alt || '')}">`;
      });
      content += '</div>';
    }
    
    content += `<p>${formatInline(announcement.body)}</p>`;
    content += `</div>`;
    
    return content;
  }).join('');

  container.innerHTML = html;
}

// Auto-load announcements when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
  const announcements = await fetchMainAnnouncements();
  renderMainAnnouncements(announcements);
});

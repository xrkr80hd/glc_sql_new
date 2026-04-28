const API_BASE = (document.body && document.body.dataset && document.body.dataset.apiBase) || '/api';

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatInline(text) {
  if (!text) {
    return '';
  }
  return escapeHtml(text).replace(/\r?\n/g, '<br>');
}

function formatParagraphs(text) {
  if (!text) {
    return '';
  }
  const segments = text
    .split(/\r?\n\s*\r?\n/)
    .map((segment) => segment.trim())
    .filter(Boolean);

  if (segments.length === 0) {
    return `<p>${formatInline(text.trim())}</p>`;
  }

  return segments.map((segment) => `<p>${formatInline(segment)}</p>`).join('');
}

function formatDateValue(value) {
  if (!value) {
    return '';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function setScripture(scripture) {
  const textEl = document.getElementById('scripture-text');
  const refEl = document.getElementById('scripture-reference');
  const scriptureCard = document.querySelector('.scripture-card');

  if (!textEl || !refEl) {
    return;
  }

  if (!scripture) {
    textEl.textContent = 'Hang tight—our next scripture is coming soon.';
    refEl.textContent = '';
    if (scriptureCard) {
      scriptureCard.dataset.state = 'empty';
    }
    return;
  }

  textEl.innerHTML = formatInline(scripture.scripture_text || scripture.text || '');
  refEl.textContent = scripture.scripture_reference || scripture.reference || '';
  if (scriptureCard) {
    scriptureCard.dataset.state = 'loaded';
  }
}

function setDevotional(scripture) {
  const devotionalEl = document.getElementById('devotional-text');
  if (!devotionalEl) {
    return;
  }

  const devotional = scripture && (scripture.devotional || scripture.devo || '');
  devotionalEl.innerHTML = devotional
    ? formatParagraphs(devotional)
    : '<p>Our next devotional drops right after service. Check back soon!</p>';
}

function renderAnnouncements(announcements) {
  const grid = document.getElementById('announcements-grid');
  if (!grid) {
    return;
  }

  if (!announcements || announcements.length === 0) {
    grid.innerHTML = `
      <article class="announcement-card empty">
        <p class="muted">Announcements are coming soon. Stay tuned for our next hangout!</p>
      </article>`;
    return;
  }

  grid.innerHTML = announcements.map((item) => {
    const eventDate = item.event_date || item.start_date || '';
    const dateLabel = eventDate
      ? `<div class="announcement-meta">📅 ${escapeHtml(formatDateValue(eventDate))}</div>`
      : '';
    const contentText = item.content || item.body || '';
    const content = contentText ? `<div class="announcement-copy">${formatInline(contentText)}</div>` : '';
    return `
      <article class="announcement-card">
        ${dateLabel}
        <h3>${escapeHtml(item.title || 'Youth Event')}</h3>
        ${content}
      </article>`;
  }).join('\n');
}

function renderTicker(announcements) {
  const track = document.querySelector('.youth-ticker .ticker-track');
  if (!track) {
    return;
  }

  const phrases = (announcements || [])
    .map((item) => item.title || '')
    .filter(Boolean);

  const fallback = ['Sundays @ 9:20 AM — Youth Devotion', 'Pop-Up Events — Check back for more info'];
  const items = phrases.length ? phrases : fallback;

  const buildItems = () => items.map((text) => `
      <div class="ticker-item">${escapeHtml(text)}</div>`).join('');

  // Duplicate items for seamless loop
  track.innerHTML = buildItems() + buildItems();
}

function buildStageMarkup(album, mediaItems, initialIndex) {
  if (!album) {
    return `
      <div class="stage-placeholder">
        <p class="muted">Gallery coming soon. Check back after our next youth hangout!</p>
      </div>`;
  }

  const safeIndex = Number.isFinite(initialIndex) ? Math.max(0, initialIndex) : 0;
  const selectedMedia = mediaItems[safeIndex] || null;

  const mediaMarkup = selectedMedia
    ? selectedMedia.type === 'video'
      ? `<video src="${escapeHtml(selectedMedia.url)}" controls preload="metadata"></video>`
      : `<img src="${escapeHtml(selectedMedia.url)}" alt="${escapeHtml(selectedMedia.caption || album.title)}">`
    : `<img src="${escapeHtml(album.cover || 'assets/youth-backdrop.png')}" alt="${escapeHtml(album.title)}">`;

  const summary = album.summary ? formatParagraphs(album.summary) : '<p class="muted">Highlights from this hangout are coming soon.</p>';
  const eventDate = formatDateValue(album.event_date);
  const eventMeta = eventDate ? `<div class="stage-meta">📅 ${escapeHtml(eventDate)}</div>` : '';

  return `
    <div class="stage-media">
      ${mediaMarkup}
    </div>
    <div class="stage-details">
      <h3>${escapeHtml(album.title)}</h3>
      ${summary}
      ${eventMeta}
    </div>`;
}

function renderGallery(albums) {
  const selectEl = document.getElementById('youth-album-select');
  const stageEl = document.getElementById('gallery-stage');
  const mediaGridEl = document.getElementById('gallery-media-grid');

  if (!selectEl || !stageEl || !mediaGridEl) {
    return;
  }

  if (!albums || albums.length === 0) {
    selectEl.innerHTML = '<option value="">Albums coming soon</option>';
    selectEl.disabled = true;
    stageEl.classList.add('empty');
    stageEl.innerHTML = buildStageMarkup(null, [], 0);
    mediaGridEl.innerHTML = '';
    return;
  }

  selectEl.disabled = false;
  selectEl.innerHTML = albums.map((album, index) => `<option value="${album.id}"${index === 0 ? ' selected' : ''}>${escapeHtml(album.title)}</option>`).join('');

  let currentAlbumId = albums[0].id;
  let currentMediaIndex = 0;

  function findAlbum(id) {
    return albums.find((album) => String(album.id) === String(id));
  }

  function updateStage(albumId, mediaIndex = 0) {
    const album = findAlbum(albumId);
    if (!album) {
      return;
    }

    const mediaItems = album.media || [];
    currentAlbumId = album.id;
    currentMediaIndex = Math.min(mediaIndex, Math.max(mediaItems.length - 1, 0));

    stageEl.classList.toggle('empty', mediaItems.length === 0);
    stageEl.dataset.selectedAlbum = String(album.id);
    stageEl.innerHTML = buildStageMarkup(album, mediaItems, currentMediaIndex);

    if (mediaItems.length === 0) {
      mediaGridEl.innerHTML = '<div class="media-empty"><p class="muted">Media will be added soon.</p></div>';
      return;
    }

    mediaGridEl.innerHTML = mediaItems.map((media, index) => {
      const isActive = index === currentMediaIndex;
      const thumb = media.type === 'video'
        ? `<div class="media-thumb media-thumb-video"><video src="${escapeHtml(media.url)}" muted preload="metadata" aria-hidden="true"></video><span class="media-flag">▶</span></div>`
        : `<div class="media-thumb"><img src="${escapeHtml(media.url)}" alt="${escapeHtml(media.caption || album.title)}"></div>`;
      return `
        <button class="media-item${isActive ? ' is-active' : ''}" type="button" data-album-id="${album.id}" data-media-index="${index}">
          ${thumb}
          <span class="sr-only">${escapeHtml(media.caption || album.title)}</span>
        </button>`;
    }).join('');
  }

  selectEl.addEventListener('change', (event) => {
    const albumId = event.target.value;
    updateStage(albumId, 0);
  });

  mediaGridEl.addEventListener('click', (event) => {
    const button = event.target.closest('.media-item');
    if (!button) {
      return;
    }

    const albumId = button.dataset.albumId;
    const mediaIndex = Number.parseInt(button.dataset.mediaIndex || '0', 10) || 0;

    updateStage(albumId, mediaIndex);
  });

  updateStage(currentAlbumId, currentMediaIndex);
}

async function fetchYouthData() {
  const endpoint = `${API_BASE.replace(/\/$/, '')}/youth`;
  try {
    const response = await fetch(endpoint, { cache: 'no-store' });
    if (!response.ok) {
      throw new Error(`Request failed: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error('Unable to load youth content:', error);
    return null;
  }
}

function readSeededData() {
  const scriptEl = document.getElementById('youthGalleryData');
  if (!scriptEl) {
    return { albums: [] };
  }
  try {
    return JSON.parse(scriptEl.textContent || 'null') || { albums: [] };
  } catch (error) {
    console.warn('Invalid seeded youth gallery JSON:', error);
    return { albums: [] };
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const fallback = readSeededData();
  const data = await fetchYouthData();
  const payload = data || { scripture: null, announcements: [], albums: fallback.albums || [] };

  setScripture(payload.scripture || null);
  setDevotional(payload.scripture || null);
  renderAnnouncements(payload.announcements || []);
  renderTicker(payload.announcements || []);
  renderGallery(payload.albums || []);
});

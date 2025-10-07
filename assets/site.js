/*
  Liberty Church Website Jav// Rick Astley filter
const BLOCKED_VIDEO_IDS = ['dQw4w9WgXcQ', 'oHg5SJYRHA0', 'xvFZjo5PgG0', 'QB7ACr7pUuE', 'j5a0jTc9S10'];

function isRickAstley(videoId) {
	return BLOCKED_VIDEO_IDS.includes(videoId);
}

function youtubeEmbed(urlOrId, autoplay=false){
	const raw = String(urlOrId||'').trim();
	if(!raw) return '';
	// Extract ID if full URL
	const m=/(?:v=|youtu\.be\/|embed\/)([\w-]{11})/.exec(raw);
	const id=m?m[1]:raw;
	if(!/^[A-Za-z0-9_-]{11}$/.test(id)) return '';
	if(isRickAstley(id)) {
		return `<div style="padding: 2rem; text-align: center; background: #f3f4f6; border-radius: 8px;"><p>🙅‍♂️ This content is not available for playback.</p><p><em>Content has been filtered.</em></p></div>`;
	}
	const params = new URLSearchParams({rel:'0',modestbranding:'1'});
	if(autoplay) params.set('autoplay','1');
	const src=`https://www.youtube.com/embed/${id}?${params.toString()}`;
	return `<iframe src="${src}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>`;
}
  "In the beginning was the Word, and the Word was with God, and the Word was God." - John 1:1
  
  Lord Jesus, You are the ultimate Word that brings life.
  Let this code serve Your purposes, connecting hearts to Your love
  and making Your house a place of digital welcome. Amen.
*/

/*
  Liberty Church Website JavaScript
  
  "In the beginning was the Word, and the Word was with God, and the Word was God." - John 1:1
  
  Lord Jesus, You are the ultimate Word that brings life.
  Let this code serve Your purposes, connecting hearts to Your love
  and making Your house a place of digital welcome. Amen.
*/

async function fetchText(path){
	const res=await fetch(path,{cache:'no-store'});
	if(!res.ok) throw new Error(`Failed to load ${path}: ${res.status}`);
	return res.text();
}
async function fetchJSON(path){
	const res=await fetch(path,{cache:'no-store'});
	if(!res.ok) throw new Error(`Failed to load ${path}: ${res.status}`);
	return res.json();
}
function setText(id, text){ const el=document.getElementById(id); if(el&&text!=null) el.textContent=text; }
function setHref(id, href){ const el=document.getElementById(id); if(el&&href) el.href=href; }
function youtubeEmbed(urlOrId,{autoplay=false}={}){
	const raw = String(urlOrId||'').trim();
	if(!raw) return '';
	// Extract ID if full URL
	const m=/(?:v=|youtu\.be\/|embed\/)([\w-]{11})/.exec(raw);
	const id=m?m[1]:raw;
	if(!/^[A-Za-z0-9_-]{11}$/.test(id)) return '';
	const params = new URLSearchParams({rel:'0',modestbranding:'1'});
	if(autoplay) params.set('autoplay','1');
	const src=`https://www.youtube.com/embed/${id}?${params.toString()}`;
	return `<iframe src="${src}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>`;
}
function vimeoEmbed(urlOrId){
	const raw = String(urlOrId||'').trim();
	const m=/vimeo\.com\/(?:video\/)?(\d+)/.exec(raw);
	const id=m?m[1]:raw;
	const validId=/^\d+$/.test(id);
	if(!validId) return '';
	const src=`https://player.vimeo.com/video/${id}`;
	return `<iframe src="${src}" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
}
function boxcastEmbed(channelId, showId){
	const src=showId?`https://boxcast.tv/view-embed/${showId}?showTitle=0&showDescription=0&showHighlights=0&showCountdown=0&mute=0&autoplay=0`:`https://boxcast.tv/channel-embed/${channelId}?showTitle=0&showDescription=0&showHighlights=0&showCountdown=0&mute=0&autoplay=0`;
	return `<iframe src="${src}" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
}
function pickEmbed(live){
	if(!live||!live.provider) return '';
	const {provider, videoIdOrUrl, channelId, showId}=live;
	if(provider==='youtube') return youtubeEmbed(videoIdOrUrl);
	if(provider==='vimeo') return vimeoEmbed(videoIdOrUrl);
	if(provider==='boxcast') return boxcastEmbed(channelId, showId);
	return '';
}

async function fetchYoutubeLiveVideoId(channelId, apiKey){
	if(!channelId || !apiKey) return { videoId:null, ok:false, status:0 };
	try {
		const q = new URLSearchParams({ part:'snippet', channelId, eventType:'live', type:'video', key:apiKey, order:'date', maxResults:'1' });
		const resp = await fetch(`https://www.googleapis.com/youtube/v3/search?${q.toString()}`);
		if(!resp.ok){
			return { videoId:null, ok:false, status:resp.status };
		}
		const data = await resp.json();
		const item = data.items && data.items[0];
		if(item && item.id && item.id.videoId) return { videoId:item.id.videoId, ok:true, status:200 };
		return { videoId:null, ok:true, status:200 };
	} catch(e){
		return { videoId:null, ok:false, status:0 };
	}
}
function render(targetId, html){ const el=document.getElementById(targetId); if(el) el.innerHTML=`<div class="content">${html||''}</div>`; }
function renderList(el, items){ if(!el) return; el.innerHTML=''; items.forEach(t=>{const li=document.createElement('li'); li.textContent=t; el.appendChild(li)}); }

document.addEventListener('DOMContentLoaded', async ()=>{
	// Performance: Intersection Observer for lazy animations
	const observerOptions = {
		threshold: 0.1,
		rootMargin: '0px 0px -50px 0px'
	};
	
	const animateOnScroll = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				entry.target.style.animationPlayState = 'running';
				animateOnScroll.unobserve(entry.target);
			}
		});
	}, observerOptions);

	// Observe all sections for scroll animations
	document.querySelectorAll('.section').forEach(section => {
		section.style.animationPlayState = 'paused';
		animateOnScroll.observe(section);
	});

	// Performance: Preload critical resources
	const preloadVideo = () => {
		const heroVideo = document.getElementById('heroVideo');
		if (heroVideo && !heroVideo.src) {
			// Preload with connection hint
			const link = document.createElement('link');
			link.rel = 'preload';
			link.as = 'video';
			link.type = 'video/mp4';
			document.head.appendChild(link);
		}
	};

	// Performance: Image lazy loading enhancement
	const lazyImages = document.querySelectorAll('img[loading="lazy"]');
	if ('IntersectionObserver' in window) {
		const imageObserver = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const img = entry.target;
					if (img.dataset.src) {
						img.src = img.dataset.src;
						img.classList.add('loaded');
						imageObserver.unobserve(img);
					}
				}
			});
		}, observerOptions);

		lazyImages.forEach(img => imageObserver.observe(img));
	}

	// Enhanced smooth scrolling for internal links
	document.querySelectorAll('a[href^="#"]').forEach(link => {
		link.addEventListener('click', (e) => {
			const targetId = link.getAttribute('href').slice(1);
			const targetElement = document.getElementById(targetId);
			if (targetElement) {
				e.preventDefault();
				targetElement.scrollIntoView({
					behavior: 'smooth',
					block: 'start'
				});
				// Update URL without triggering navigation
				history.pushState(null, '', `#${targetId}`);
			}
		});
	});

	// Add ripple effect to buttons
	document.querySelectorAll('.btn').forEach(button => {
		button.addEventListener('click', function(e) {
			const ripple = document.createElement('span');
			const rect = this.getBoundingClientRect();
			const size = Math.max(rect.width, rect.height);
			const x = e.clientX - rect.left - size / 2;
			const y = e.clientY - rect.top - size / 2;
			
			ripple.style.cssText = `
				position: absolute;
				width: ${size}px;
				height: ${size}px;
				left: ${x}px;
				top: ${y}px;
				background: rgba(255, 255, 255, 0.3);
				border-radius: 50%;
				transform: scale(0);
				animation: ripple 0.6s linear;
				pointer-events: none;
			`;
			
			this.style.position = 'relative';
			this.style.overflow = 'hidden';
			this.appendChild(ripple);
			
			setTimeout(() => ripple.remove(), 600);
		});
	});

	preloadVideo();
	// Mobile nav: supports either #primaryNav (.nav-links) or legacy #mainNav (with <ul>)
	const toggle=document.querySelector('.nav-toggle');
	const primary=document.getElementById('primaryNav');
	const legacy=document.getElementById('mainNav');
	const nav = primary || legacy;
	function closeNav(){ if(!nav) return; nav.classList.remove('open'); if(toggle) toggle.setAttribute('aria-expanded','false'); }
	if(toggle && nav){
		toggle.addEventListener('click', e=>{
			e.stopPropagation();
			const exp = toggle.getAttribute('aria-expanded')==='true';
			toggle.setAttribute('aria-expanded', String(!exp));
			nav.classList.toggle('open');
		});
		(nav.querySelectorAll('a')||[]).forEach(a=> a.addEventListener('click', ()=>{
			if(window.matchMedia('(max-width:899px)').matches) closeNav();
		}));
		// Close on outside click
		document.addEventListener('click', (ev)=>{
			if(!nav.classList.contains('open')) return;
			if(ev.target===toggle || toggle.contains(ev.target)) return;
			if(nav.contains(ev.target)) return;
			closeNav();
		});
		// Close on Escape
		document.addEventListener('keydown', (ev)=>{ if(ev.key==='Escape') closeNav(); });
	}

	setText('year', String(new Date().getFullYear()));

	// Randomize homepage hero video
	(function(){
		const v = document.getElementById('heroVideo');
		if(!v) return;
		const vids = [
			'./assets/hero_vids/bible_hero.mp4',
			'./assets/hero_vids/the_cross_hero.mp4',
			'./assets/hero_vids/worship_hero.mp4',
			'./assets/hero_vids/worship_hero_1.mp4'
		];
		const pick = vids[Math.floor(Math.random()*vids.length)];
		v.src = pick;
		// When metadata loads, make it visible smoothly
		v.addEventListener('loadeddata', ()=>{
			v.classList.add('ready');
		}, { once:true });
	})();

	// Load config (create if missing)
	let cfg={};
	try { cfg = await fetchJSON('./site.config.json'); } catch(e){ console.warn('No site.config.json found, using defaults'); }
	const { church={}, links={}, livestream={}, latestMessage={}, serviceTimes=[], contact={} } = cfg;

	setHref('directionsLink', links.maps);
	setHref('openMaps2', links.maps);
	setHref('planVisitLink', links.planVisit||'#visit');
	setHref('youtubeLink', links.youtube); setHref('facebookLink', links.facebook); setHref('instagramLink', links.instagram);

	const addr = church.address || '100 McKeithen Dr, Alexandria, LA 71303';
	setText('address', addr); setText('footerAddress', addr);
	renderList(document.getElementById('serviceTimes'), serviceTimes.length?serviceTimes:[
		'Sundays @ 9:20 AM – Youth Devotion',
		'Sundays @ 10:00 AM – Worship Service'
	]);

	// Livestream auto-detect + adaptive polling / caching / just-ended state
	let isLive=false; let currentLiveId=null; let liveHTML='';
	let checking=true; // initial loading state
	let justEnded=false; // within post-service window after transition
	const JUST_ENDED_WINDOW_MIN=15; // minutes
	const SERVICE_START_CST='10:00'; // HH:MM central
	const FAST_INTERVAL_MS=30000; // 30s pre/during service
	const MID_INTERVAL_MS=90000;  // 90s shortly after / tail window
	const SLOW_INTERVAL_MS=180000; // 3m outside window
	// Backoff configuration
	const BACKOFF_BASE_MS = 15000; // starting penalty (15s)
	const BACKOFF_MAX_MS = 8 * 60 * 1000; // cap at 8 minutes
	let consecFailures = 0; // consecutive API failure counter
	let pollTimer=null;
	const badge=document.getElementById('liveBadge');
	const navLiveDot=document.getElementById('navLiveDot');

	function nowCentral(){
		// Approximate Central Time by offsetting from local if needed. Simpler: use local time assuming server in CT or user near CT.
		// For robust production you'd use Intl / timezone libs; here we keep simple.
		return new Date();
	}
	function minutesSince(dt){ return (Date.now()-dt)/60000; }
	function loadCache(){
		try{ return JSON.parse(localStorage.getItem('liveStatusCache')||'null'); }catch(e){ return null; }
	}
	function saveCache(obj){ try{ localStorage.setItem('liveStatusCache', JSON.stringify(obj)); }catch(e){} }
	function markServiceEnded(){ try{ localStorage.setItem('lastServiceEnd', String(Date.now())); }catch(e){} }
	function getLastServiceEnd(){ try{ const v=localStorage.getItem('lastServiceEnd'); return v?Number(v):0; }catch(e){ return 0; } }

	function serviceWindowInfo(){
		// Determine if we are near service time (fast polling) or in tail window.
		// We'll treat the window: 9:30-11:45 FAST, 11:45-12:15 MID.
		const d=nowCentral();
		const h=d.getHours();
		const m=d.getMinutes();
		const totalM = h*60+m; // minutes since midnight
		const fastStart=9*60+30; // 9:30
		const serviceNominalStart=10*60; // 10:00 (for potential logic extension)
		const fastEnd=11*60+45; // 11:45
		const midEnd=12*60+15; // 12:15
		return { isFast: totalM>=fastStart && totalM<=fastEnd, isMid: totalM>fastEnd && totalM<=midEnd };
	}

	function currentBackoffDelay(){
		if(consecFailures===0) return 0;
		// Exponential: base * 2^(n-1)
		const raw = BACKOFF_BASE_MS * Math.pow(2, consecFailures-1);
		const capped = Math.min(raw, BACKOFF_MAX_MS);
		// Jitter: +/- 20%
		const jitter = capped * (0.4*Math.random() - 0.2);
		return Math.max(0, Math.round(capped + jitter));
	}

	async function fetchLive(){
		checking=true;
		const onLivePage = /live\.html$/i.test(location.pathname);
		const state={ changed:false };
		let nextIsLive=false; let nextId=null; let nextHTML='';
		if(livestream.provider==='youtube' && livestream.auto){
			const hasApiKey = livestream.apiKey && livestream.apiKey !== 'YOUR_YOUTUBE_DATA_API_KEY';
			const channelId = livestream.channelId;
			const placeholderChannel = /^UCx{4,}$/i.test(channelId||'');
			if(hasApiKey && channelId && !placeholderChannel){
				const res = await fetchYoutubeLiveVideoId(channelId, livestream.apiKey);
				if(res.ok){
					if(res.videoId && !isRickAstley(res.videoId)){ nextIsLive=true; nextId=res.videoId; nextHTML=youtubeEmbed(res.videoId,{autoplay:onLivePage}); }
					// Reset failures on any successful HTTP
					consecFailures = 0;
				}else{
					consecFailures++;
				}
			}else if(channelId && !placeholderChannel){
				const params = new URLSearchParams({rel:'0',modestbranding:'1'});
				if(onLivePage) params.set('autoplay','1');
				nextHTML = `<iframe src="https://www.youtube.com/embed/live_stream?channel=${channelId}&${params.toString()}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>`;
				// Without API we can't be certain.
				nextIsLive=false; nextId=null;
			}else{
				nextIsLive=false; nextId=null; nextHTML='';
			}
		}else{
			nextHTML=pickEmbed(livestream); nextIsLive=!!nextHTML; nextId=null;
		}
		// Detect transition live->offline for just-ended
		if(isLive && !nextIsLive){ markServiceEnded(); }
		isLive=nextIsLive; currentLiveId=nextId; liveHTML=nextHTML; checking=false;
		const lastEnd=getLastServiceEnd();
		justEnded = !isLive && lastEnd && minutesSince(lastEnd) <= JUST_ENDED_WINDOW_MIN;
		// Save cache snapshot
		saveCache({ ts:Date.now(), isLive, videoId:currentLiveId, html:liveHTML, justEnded });
		// Update nav badge styles
		if(isLive){
			if(badge) badge.hidden=false; if(navLiveDot){ navLiveDot.hidden=false; navLiveDot.classList.remove('idle'); }
		}else{
			if(badge) badge.hidden=true; if(navLiveDot){ navLiveDot.hidden=false; navLiveDot.classList.add('idle'); }
		}
	}

	function scheduleNext(){
		const { isFast, isMid } = serviceWindowInfo();
		let interval=SLOW_INTERVAL_MS;
		if(isFast) interval=FAST_INTERVAL_MS; else if(isMid) interval=MID_INTERVAL_MS;
		// Apply backoff penalty (only for API failures path). If in fast window, still respect at least fast interval.
		const penalty = currentBackoffDelay();
		if(penalty>0){
			interval = Math.max(interval, penalty);
		}
		pollTimer = setTimeout(async ()=>{ await fetchLive(); renderLiveState(); scheduleNext(); }, interval);
	}

	// Initialize from cache if fresh (<60s) to avoid blank & quota hit
	(function initFromCache(){
		const c=loadCache();
		if(c && Date.now()-c.ts < 60000){
			isLive=c.isLive; currentLiveId=c.videoId; liveHTML=c.html; justEnded=c.justEnded; checking=false;
			if(isLive){ if(badge) badge.hidden=false; if(navLiveDot){ navLiveDot.hidden=false; navLiveDot.classList.remove('idle'); } }
			else { if(badge) badge.hidden=true; if(navLiveDot){ navLiveDot.hidden=false; navLiveDot.classList.add('idle'); } }
		}else{
			checking=true;
		}
	})();

	await fetchLive(); // initial real fetch (will overwrite cache if existed)
	scheduleNext();

	// Latest message fallback when not live — only embed if IDs are valid
	let latestHTML='';
	const pl = (latestMessage?.playlistId||'').trim();
	const isValidPlaylist = /^[A-Za-z0-9_-]{12,}$/.test(pl); // conservative gate
	if(isValidPlaylist){
		latestHTML = `<iframe src="https://www.youtube.com/embed/videoseries?list=${pl}&rel=0&modestbranding=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>`;
	} else if(latestMessage?.provider==='youtube' && latestMessage.videoIdOrUrl){
		// Will return '' if it's just a channel or /live URL
		latestHTML = youtubeEmbed(latestMessage.videoIdOrUrl);
	} else if(latestMessage?.provider==='vimeo'){
		latestHTML = vimeoEmbed(latestMessage.videoIdOrUrl);
	}

	// Removed local MP4 fallback: when offline we will display the latest message embed instead.

	// Home page latest message (index.html)
	const latestTarget = document.getElementById('latestEmbed');
	if(latestTarget){
		latestTarget.innerHTML = latestHTML ? `<div class="content">${latestHTML}</div>` : '';
	}

	// New: Latest sermon summary (homepage) using sermons.json recent[0]
	const latestSermonBlock = document.getElementById('latestSermonBlock');
	if(latestSermonBlock){
		try {
			const sermons = await fetchJSON('./assets/data/sermons.json');
			const recent = sermons.recent||[];
			if(recent.length){
				const first = recent[0];
				const date = first.publishedAt? new Date(first.publishedAt).toLocaleDateString():'';
				const desc = (first.description||'').split(/\n+/).find(p=>p.trim().length>40)||'';
				latestSermonBlock.innerHTML = `
					<div>
						<h3 style="margin-top:0">${first.title||'Latest Message'}</h3>
						<p class="muted small" style="margin-top:4px">${date}</p>
						<p>${desc?desc:''}</p>
						<a class="btn" href="https://www.youtube.com/watch?v=${first.id}" target="_blank" rel="noopener">Watch on YouTube</a>
					</div>`;
			}else{
				latestSermonBlock.innerHTML = '<p class="muted">No recent message available yet.</p>';
			}
		}catch(e){ latestSermonBlock.innerHTML = '<p class="muted">Unable to load latest message.</p>'; }
	}

	// Live page embed with new states
	const liveContainer = document.getElementById('livestreamEmbed');
	function renderLiveState(){
		if(!liveContainer) return;
		if(checking){
			liveContainer.innerHTML = `<div class="content"><div class="live-checking"><div class="ring" aria-hidden="true"></div><div>Checking live status…</div></div></div>`; return;
		}
		if(isLive){
			const vid=currentLiveId; const onLivePage=/live\.html$/i.test(location.pathname);
			const iframe= vid? youtubeEmbed(vid,{autoplay:onLivePage}) : (liveHTML||'');
			liveContainer.innerHTML = `<div class="content">${iframe}<span class="live-indicator player-live-indicator"><span class="dot"></span>LIVE</span></div>`; return;
		}
		if(justEnded){
			liveContainer.innerHTML = `<div class="content"><div class="live-just-ended"><p>Service just ended</p><small>Thanks for joining us! Latest message will remain available below.</small></div></div>`; return;
		}
		// Offline fallback
		if(latestHTML){
			liveContainer.innerHTML = `<div class="content">${latestHTML}</div>`;
		}else{
			liveContainer.innerHTML = `<div class="content"><p class="muted" style="margin:12px">Not live right now. Check back soon.</p></div>`;
		}
	}
	if(liveContainer){ renderLiveState(); }

	// Give page: wire email link if present
	const giveEmail=document.getElementById('giveEmailLink');
	if(giveEmail){
		const email = (contact.email||'GoLibertyChurch@gmail.com');
		const subj = encodeURIComponent('Question about Online Giving');
		giveEmail.href = `mailto:${email}?subject=${subj}`;
	}

	// Plan Your Visit form => only wire mailto if explicitly requested
	const form=document.getElementById('visitForm');
	if(form && form.dataset.mailto === 'true'){
		const email = (contact.email||'GoLibertyChurch@gmail.com');
		const status=document.getElementById('visitFormStatus');
		const setStatus = (t)=>{ if(status) status.textContent=t; };
		const emailLink=document.getElementById('contactEmailLink'); if(emailLink) emailLink.href=`mailto:${email}`;
		const fallback=document.getElementById('visitEmailFallback'); if(fallback) fallback.href=`mailto:${email}?subject=${encodeURIComponent('Plan Your Visit')}`;
		form.addEventListener('submit', (e)=>{
			e.preventDefault();
			const name=document.getElementById('vfName').value.trim();
			const em=document.getElementById('vfEmail').value.trim();
			const phone=document.getElementById('vfPhone').value.trim();
			const date=document.getElementById('vfDate').value;
			const count=document.getElementById('vfCount').value;
			const notes=document.getElementById('vfNotes').value.trim();
			if(!name||!em){ setStatus('Please enter your name and email.'); return; }
			const subject=`Plan Your Visit: ${name}`;
			const body=`Name: ${name}%0D%0AEmail: ${em}%0D%0APhone: ${phone}%0D%0ADate: ${date}%0D%0AParty Size: ${count}%0D%0ANotes: ${encodeURIComponent(notes)}`;
			window.location.href=`mailto:${email}?subject=${encodeURIComponent(subject)}&body=${body}`;
			setStatus('Opening your email app to send the details...');
		});
	}

	// Data-driven content is now handled by PHP includes
	// Announcements, events, etc. are loaded via PHP database queries
	// CSV functionality removed in favor of database-driven approach

	// Welcome modal logic (homepage)
	(function(){
		const modal = document.getElementById('welcomeModal');
		if(!modal) return; // only on index
		const openBtn = document.getElementById('reopenWelcome');
		const closeBtn = modal.querySelector('.modal-close');
		const backdrop = modal.querySelector('.modal-backdrop');
		const contentWrap = document.getElementById('welcomeContent');
		let loaded=false;
		let lastFocus=null;
		function lockScroll(on){
			if(on){ document.body.classList.add('modal-open'); }
			else { document.body.classList.remove('modal-open'); }
		}
		function loadContent(){
			if(loaded) return; loaded=true;
			fetch('./assets/welcome.html',{cache:'no-store'}).then(r=>r.ok?r.text():Promise.reject()).then(html=>{
				if(contentWrap) contentWrap.innerHTML=html;
			}).catch(()=>{ if(contentWrap) contentWrap.innerHTML='<p class="muted">Welcome message unavailable right now.</p>'; });
		}
		function open(){
			if(modal.getAttribute('aria-hidden')==='false') return;
			lastFocus = document.activeElement;
			loadContent();
			modal.setAttribute('aria-hidden','false');
			modal.classList.add('open');
			lockScroll(true);
			// One-time signature animation trigger after content likely injected
			setTimeout(()=>{
				const sigWrap = modal.querySelector('.signature-wrapper');
				if(sigWrap && !sigWrap.dataset.animated){
					requestAnimationFrame(()=>{
						requestAnimationFrame(()=>{
							sigWrap.classList.add('signature-animate');
							sigWrap.dataset.animated='true';
						});
					});
				}
			},450);
			// focus heading
			const h = modal.querySelector('#welcomeTitle'); if(h) h.focus({preventScroll:true});
			trap();
			try{ localStorage.setItem('welcomeSeen','1'); }catch(e){}
		}
		function close(){
			if(modal.getAttribute('aria-hidden')==='true') return;
			modal.setAttribute('aria-hidden','true');
			modal.classList.remove('open');
			lockScroll(false);
			if(lastFocus && typeof lastFocus.focus==='function') lastFocus.focus();
		}
		function trap(){
			const focusables = modal.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
			if(!focusables.length) return;
			const first = focusables[0];
			const last = focusables[focusables.length-1];
			modal.addEventListener('keydown', function onKey(e){
				if(modal.getAttribute('aria-hidden')==='true'){ modal.removeEventListener('keydown', onKey); return; }
				if(e.key==='Tab'){
					if(e.shiftKey && document.activeElement===first){ e.preventDefault(); last.focus(); }
					else if(!e.shiftKey && document.activeElement===last){ e.preventDefault(); first.focus(); }
				}
				if(e.key==='Escape'){ close(); }
			});
		}
		if(openBtn) openBtn.addEventListener('click', open);
		if(closeBtn) closeBtn.addEventListener('click', close);
		if(backdrop) backdrop.addEventListener('click', close);
		// Auto-open only once per user (localStorage flag)
		try{
			const seen = localStorage.getItem('welcomeSeen');
			if(!seen){ setTimeout(open, 800); }
		}catch(e){ /* ignore storage issues */ }
	})();
});


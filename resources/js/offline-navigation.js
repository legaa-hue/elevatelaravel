// Simple offline/online banner with pending sync count
import offlineSync from './offline-sync';

class OfflineNavigationBanner {
	constructor() {
		this.bannerEl = null;
		this.pendingEl = null;
		this.statusEl = null;
		this.intervalId = null;
	}

	init() {
		// Create banner UI
		this.createBanner();
		this.updateStatus(navigator.onLine);

		// Listen for online/offline events
		window.addEventListener('online', () => {
			this.updateStatus(true);
			// Kick off a sync when back online
			try { offlineSync.syncAll(); } catch {}
		});
		window.addEventListener('offline', () => this.updateStatus(false));

		// Poll pending count every 15s
		this.intervalId = setInterval(async () => {
			try {
				const count = await offlineSync.getPendingCount();
				this.setPendingCount(count);
			} catch {}
		}, 15000);
	}

	createBanner() {
		if (this.bannerEl) return;
		const el = document.createElement('div');
		el.setAttribute('id', 'offline-status-banner');
		el.style.cssText = `
			position: fixed;
			left: 12px;
			bottom: calc(16px + env(safe-area-inset-bottom, 0px));
			z-index: 2140;
			background: rgba(17,24,39,0.94);
			color: #fff;
			border-radius: 8px;
			padding: 8px 12px;
			display: flex;
			align-items: center;
			gap: 10px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.25);
			font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial, "Apple Color Emoji", "Segoe UI Emoji";
			font-size: 12px;
			pointer-events: none; /* don't block clicks of underlying UI */
			white-space: nowrap;
		`;

		const dot = document.createElement('span');
		dot.style.cssText = `
			width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;`;
		el.appendChild(dot);

		const status = document.createElement('span');
		status.textContent = 'Online';
		el.appendChild(status);

		const sep = document.createElement('span');
		sep.textContent = '•';
		sep.style.opacity = '0.6';
		el.appendChild(sep);

		const pending = document.createElement('span');
		pending.textContent = 'Pending: 0';
		el.appendChild(pending);

		document.body.appendChild(el);

		this.bannerEl = el;
		this.statusEl = status;
		this.pendingEl = pending;
		this.dotEl = dot;
	}

	updateStatus(isOnline) {
		if (!this.bannerEl) return;
		this.statusEl.textContent = isOnline ? 'Online' : 'Offline';
		this.dotEl.style.background = isOnline ? '#10b981' : '#f59e0b';
		this.bannerEl.style.opacity = isOnline ? '0.9' : '1';
		this.bannerEl.style.background = isOnline ? 'rgba(17,24,39,0.94)' : 'rgba(120,53,15,0.95)';
	}

	setPendingCount(count) {
		if (!this.pendingEl) return;
		this.pendingEl.textContent = `Pending: ${count}`;
	}

	destroy() {
		if (this.intervalId) clearInterval(this.intervalId);
		if (this.bannerEl && this.bannerEl.parentNode) {
			this.bannerEl.parentNode.removeChild(this.bannerEl);
		}
		this.bannerEl = null;
		this.pendingEl = null;
		this.statusEl = null;
		this.dotEl = null;
	}
}

const offlineNavigation = new OfflineNavigationBanner();
export default offlineNavigation;

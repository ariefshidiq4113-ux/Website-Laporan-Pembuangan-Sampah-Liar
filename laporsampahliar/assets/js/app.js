/**
 * assets/js/app.js
 * LaporSampahLiar – Global JavaScript
 */

'use strict';

// ============================================
// TOAST NOTIFICATIONS
// ============================================
window.Toast = {
  container: null,
  init() {
    if (!document.getElementById('toast-container')) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      document.body.appendChild(this.container);
    } else {
      this.container = document.getElementById('toast-container');
    }
  },
  show(title, message = '', type = 'success', duration = 4000) {
    this.init();
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type !== 'success' ? type : ''}`;
    toast.innerHTML = `
      <span class="toast-icon">${icons[type] || icons.info}</span>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        ${message ? `<div class="toast-msg">${message}</div>` : ''}
      </div>
      <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#94a3b8;margin-left:.5rem">✕</button>
    `;
    this.container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all .3s';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }
};

// ============================================
// MODAL SYSTEM
// ============================================
window.Modal = {
  open(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('active'); document.body.style.overflow = 'hidden'; }
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('active'); document.body.style.overflow = ''; }
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay.active').forEach(m => {
      m.classList.remove('active');
    });
    document.body.style.overflow = '';
  }
};

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// ============================================
// SIDEBAR (Admin)
// ============================================
window.Sidebar = {
  toggle() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (!sidebar) return;
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('show');
  },
  close() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
  }
};

// ============================================
// PHOTO UPLOAD PREVIEW
// ============================================
window.PhotoUpload = {
  init(inputId, gridId, maxFiles = 5) {
    const input = document.getElementById(inputId);
    const grid  = document.getElementById(gridId);
    const area  = input?.closest('.photo-upload-area');
    if (!input || !grid) return;

    this.files = [];
    this.maxFiles = maxFiles;

    input.addEventListener('change', (e) => this.handleFiles(e.target.files, grid));

    if (area) {
      area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('drag-over'); });
      area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
      area.addEventListener('drop', e => {
        e.preventDefault(); area.classList.remove('drag-over');
        this.handleFiles(e.dataTransfer.files, grid);
      });
    }
  },

  handleFiles(fileList, grid) {
    const MAX = 5 * 1024 * 1024;
    Array.from(fileList).forEach(file => {
      if (this.files.length >= this.maxFiles) {
        Toast.show('Batas foto', `Maksimal ${this.maxFiles} foto`, 'warning'); return;
      }
      if (!file.type.startsWith('image/')) { Toast.show('Format salah', 'Hanya file gambar', 'error'); return; }
      if (file.size > MAX) { Toast.show('File terlalu besar', 'Maksimal 5MB', 'error'); return; }

      this.files.push(file);
      const reader = new FileReader();
      reader.onload = e => this.addThumb(e.target.result, this.files.length - 1, grid);
      reader.readAsDataURL(file);
    });
  },

  addThumb(src, idx, grid) {
    const div = document.createElement('div');
    div.className = 'photo-thumb'; div.dataset.idx = idx;
    div.innerHTML = `<img src="${src}" alt="Foto ${idx+1}"><button class="remove-btn" onclick="PhotoUpload.remove(${idx}, this)">✕</button>`;
    grid.appendChild(div);
  },

  remove(idx, btn) {
    this.files.splice(idx, 1);
    btn.closest('.photo-thumb').remove();
    // Re-index
    document.querySelectorAll('.photo-thumb').forEach((el, i) => {
      el.dataset.idx = i;
      el.querySelector('.remove-btn')?.setAttribute('onclick', `PhotoUpload.remove(${i}, this)`);
    });
  },

  getFiles() { return this.files; }
};

// ============================================
// GPS / GEOLOCATION
// ============================================
window.GPS = {
  map: null,
  marker: null,
  watching: false,

  init(mapId, latId, lngId, addrId) {
    if (!document.getElementById(mapId)) return;
    // Load Leaflet if not loaded
    if (!window.L) { this._loadLeaflet(() => this._initMap(mapId, latId, lngId, addrId)); }
    else { this._initMap(mapId, latId, lngId, addrId); }
  },

  _loadLeaflet(cb) {
    if (document.querySelector('link[href*="leaflet"]')) { cb(); return; }
    const css  = document.createElement('link');
    css.rel = 'stylesheet'; css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);
    const js = document.createElement('script');
    js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    js.onload = cb; document.head.appendChild(js);
  },

  _initMap(mapId, latId, lngId, addrId) {
    const defaultLat = -6.2088, defaultLng = 106.8456;
    this.map = L.map(mapId, { zoomControl: true }).setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(this.map);

    // Custom marker icon
    const icon = L.divIcon({
      className: '', iconSize: [36, 36], iconAnchor: [18, 36],
      html: `<div style="width:36px;height:36px;background:var(--primary);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3)"><div style="transform:rotate(45deg);text-align:center;line-height:30px;font-size:.9rem">📍</div></div>`
    });

    this.map.on('click', e => {
      const { lat, lng } = e.latlng;
      this._setLocation(lat, lng, latId, lngId, addrId, icon);
    });

    this.getCurrentLocation(latId, lngId, addrId, icon);
  },

  getCurrentLocation(latId, lngId, addrId, icon) {
    const statusEl = document.getElementById('gps-status');
    if (statusEl) { statusEl.querySelector('.map-dot')?.classList.add('active'); }

    if (!navigator.geolocation) {
      Toast.show('GPS tidak tersedia', 'Browser tidak mendukung geolokasi', 'warning');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      pos => {
        const { latitude, longitude } = pos.coords;
        this._setLocation(latitude, longitude, latId, lngId, addrId, icon || this._defaultIcon());
        if (statusEl) statusEl.querySelector('.map-dot')?.classList.add('active');
        Toast.show('Lokasi ditemukan', 'Posisi GPS berhasil dideteksi', 'success');
      },
      err => {
        Toast.show('GPS gagal', 'Tidak dapat mengambil lokasi', 'warning');
        if (statusEl) statusEl.querySelector('.map-dot')?.classList.remove('active');
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  },

  _defaultIcon() {
    return L.divIcon({ className: '', iconSize: [36,36], iconAnchor: [18,36],
      html: '<div style="width:32px;height:32px;background:#16a34a;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3)"></div>'
    });
  },

  _setLocation(lat, lng, latId, lngId, addrId, icon) {
    if (latId) document.getElementById(latId).value = lat.toFixed(8);
    if (lngId) document.getElementById(lngId).value = lng.toFixed(8);

    if (this.marker) this.map.removeLayer(this.marker);
    this.marker = L.marker([lat, lng], { icon: icon || this._defaultIcon() }).addTo(this.map);
    this.map.setView([lat, lng], 17);

    if (addrId) this._reverseGeocode(lat, lng, addrId);
  },

  _reverseGeocode(lat, lng, addrId) {
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=id`)
      .then(r => r.json())
      .then(data => {
        if (data.display_name && document.getElementById(addrId)) {
          document.getElementById(addrId).value = data.display_name;
        }
        // Auto-fill fields
        const a = data.address || {};
        if (document.getElementById('kelurahan')) document.getElementById('kelurahan').value = a.village || a.suburb || '';
        if (document.getElementById('kecamatan')) document.getElementById('kecamatan').value = a.city_district || a.municipality || '';
        if (document.getElementById('kota'))      document.getElementById('kota').value      = a.city || a.county || '';
        if (document.getElementById('provinsi'))  document.getElementById('provinsi').value  = a.state || '';
      })
      .catch(() => {});
  }
};

// ============================================
// VIEW-ONLY MAP (Laporan Detail)
// ============================================
window.MapView = {
  init(mapId, lat, lng, title = 'Lokasi Laporan') {
    const el = document.getElementById(mapId);
    if (!el || !lat || !lng) return;
    if (!window.L) {
      GPS._loadLeaflet(() => this._render(mapId, lat, lng, title));
    } else {
      this._render(mapId, lat, lng, title);
    }
  },
  _render(mapId, lat, lng, title) {
    const map = L.map(mapId, { zoomControl: true, dragging: true }).setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(title).openPopup();
  }
};

// ============================================
// FORM VALIDATION
// ============================================
window.Validate = {
  form(formId, rules) {
    let valid = true;
    // Clear previous errors
    document.querySelectorAll(`#${formId} .form-error`).forEach(e => e.remove());
    document.querySelectorAll(`#${formId} .form-control.error`).forEach(e => e.classList.remove('error'));

    Object.entries(rules).forEach(([fieldId, checks]) => {
      const field = document.getElementById(fieldId);
      if (!field) return;
      const val = field.value.trim();

      for (const check of checks) {
        let msg = '';
        if (check === 'required'     && !val) msg = 'Wajib diisi';
        if (check === 'email'        && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) msg = 'Email tidak valid';
        if (check === 'phone'        && val && !/^[0-9+\-\s]{9,15}$/.test(val)) msg = 'Nomor HP tidak valid';
        if (check.startsWith('min:') && val.length < parseInt(check.split(':')[1])) msg = `Minimal ${check.split(':')[1]} karakter`;
        if (check.startsWith('max:') && val.length > parseInt(check.split(':')[1])) msg = `Maksimal ${check.split(':')[1]} karakter`;

        if (msg) {
          valid = false;
          field.classList.add('error');
          const errEl = document.createElement('div');
          errEl.className = 'form-error'; errEl.textContent = msg;
          field.insertAdjacentElement('afterend', errEl);
          break;
        }
      }
    });
    return valid;
  }
};

// ============================================
// AJAX HELPER
// ============================================
window.Ajax = {
  async post(url, data) {
    const formData = new FormData();
    Object.entries(data).forEach(([k, v]) => formData.append(k, v));
    const res = await fetch(url, { method: 'POST', body: formData });
    return res.json();
  },
  async get(url, params = {}) {
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(qs ? `${url}?${qs}` : url);
    return res.json();
  }
};

// ============================================
// INIT ON DOM READY
// ============================================
document.addEventListener('DOMContentLoaded', () => {
  // Highlight active bottom nav
  const path = window.location.pathname;
  document.querySelectorAll('.bottom-nav a').forEach(a => {
    if (path.includes(a.getAttribute('href'))) a.classList.add('active');
  });
  // Admin sidebar active
  document.querySelectorAll('.sidebar nav a').forEach(a => {
    if (path.includes(a.getAttribute('href'))) a.classList.add('active');
  });
});

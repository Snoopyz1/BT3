// ============================================================
// assets/js/app.js — Main JavaScript
// ============================================================

'use strict';

/* ── Auto-dismiss alerts ── */
document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
  setTimeout(() => {
    const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
    if (bsAlert) bsAlert.close();
  }, 5000);
});

/* ── Confirm delete ── */
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', function (e) {
    const msg = this.dataset.confirm || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
    if (!confirm(msg)) e.preventDefault();
  });
});

/* ── Tooltips ── */
if (typeof bootstrap !== 'undefined') {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
  });
}

/* ── Date input: disable past dates for booking ── */
const dateInput = document.getElementById('date');
if (dateInput && dateInput.dataset.minToday !== undefined) {
  dateInput.min = new Date().toISOString().split('T')[0];
}

/* ── Time validation: end_time > start_time ── */
const startTime = document.getElementById('start_time');
const endTime   = document.getElementById('end_time');
if (startTime && endTime) {
  startTime.addEventListener('change', function () {
    endTime.min = this.value;
    if (endTime.value && endTime.value <= this.value) endTime.value = '';
  });
}

/* ── Search filter table ── */
const searchInput = document.getElementById('tableSearch');
if (searchInput) {
  searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('table tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

/* ── Device type filter ── */
const typeFilter = document.getElementById('deviceTypeFilter');
if (typeFilter) {
  typeFilter.addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('[data-device-type]').forEach(row => {
      row.style.display = (!val || row.dataset.deviceType === val) ? '' : 'none';
    });
  });
}

/* ── Status filter ── */
const statusFilter = document.getElementById('statusFilter');
if (statusFilter) {
  statusFilter.addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('[data-status]').forEach(row => {
      row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
    });
  });
}

/* ── Dynamic room image preview ── */
const roomImageInput = document.getElementById('image');
const roomImagePreview = document.getElementById('imagePreview');
if (roomImageInput && roomImagePreview) {
  roomImageInput.addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        roomImagePreview.src = e.target.result;
        roomImagePreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
}

/* ── Highlight active sidebar link ── */
(function () {
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
      link.classList.add('active');
    }
  });
})();

/* ── Counter animation for stat cards ── */
function animateCounter(el) {
  const target = parseInt(el.dataset.target || el.textContent, 10);
  if (isNaN(target)) return;
  let current = 0;
  const step  = Math.max(1, Math.ceil(target / 40));
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString('vi-VN');
    if (current >= target) clearInterval(timer);
  }, 30);
}
document.querySelectorAll('.stat-value[data-target]').forEach(animateCounter);

/* ── Fetch notifications (simple polling) ── */
async function fetchNotifications() {
  try {
    const res = await fetch(window.APP_BASE_URL + '/api/getNotifications.php');
    if (!res.ok) return;
    const data = await res.json();
    if (data.count > 0) {
      const badge = document.getElementById('notifBadge');
      if (badge) { badge.textContent = data.count; badge.style.display = 'inline'; }
    }
  } catch (_) { /* silent */ }
}
if (window.APP_BASE_URL) {
  fetchNotifications();
  setInterval(fetchNotifications, 60000);
}

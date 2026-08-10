/* ============================================================
   FASBY STUDIO - Admin JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    /* ========================================================
       MOBİL SİDEBAR
       ======================================================== */
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        sidebarOverlay?.classList.toggle('open');
    });

    sidebarOverlay?.addEventListener('click', () => {
        sidebar?.classList.remove('open');
        sidebarOverlay?.classList.remove('open');
    });

    /* ========================================================
       GÖRSEL ÖNİZLEME
       ======================================================== */
    const imageInput   = document.getElementById('imageInput');
    const previewWrap  = document.getElementById('imagePreviewWrap');
    const previewImg   = document.getElementById('imagePreview');
    const uploadArea   = document.getElementById('imageUploadArea');
    const removeImgBtn = document.getElementById('removeImageBtn');

    imageInput?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showAdminToast('Lütfen geçerli bir görsel dosyası seçin.', 'error');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showAdminToast('Dosya boyutu 5MB\'den büyük olamaz.', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = (ev) => {
            if (previewImg) previewImg.src = ev.target.result;
            if (previewWrap) previewWrap.style.display = '';
            if (uploadArea)  uploadArea.style.display  = 'none';
        };
        reader.readAsDataURL(file);
    });

    removeImgBtn?.addEventListener('click', () => {
        if (imageInput) imageInput.value = '';
        if (previewWrap) previewWrap.style.display = 'none';
        if (uploadArea)  uploadArea.style.display  = '';
        const existingImg = document.getElementById('existingImageInput');
        if (existingImg) existingImg.value = '';
    });

    // Sürükle & bırak
    uploadArea?.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    uploadArea?.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea?.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && imageInput) {
            const dt = new DataTransfer();
            dt.items.add(file);
            imageInput.files = dt.files;
            imageInput.dispatchEvent(new Event('change'));
        }
    });

    /* ========================================================
       SİLME ONAYI
       ======================================================== */
    const confirmOverlay = document.getElementById('confirmOverlay');
    const confirmYes     = document.getElementById('confirmYes');
    const confirmNo      = document.getElementById('confirmNo');
    let   pendingDeleteUrl = null;

    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            pendingDeleteUrl = btn.dataset.href || btn.href || null;
            const msg = btn.dataset.confirm;
            const msgEl = document.getElementById('confirmMsg');
            if (msgEl) msgEl.textContent = msg;
            confirmOverlay?.classList.add('open');
        });
    });

    confirmYes?.addEventListener('click', () => {
        if (pendingDeleteUrl) window.location.href = pendingDeleteUrl;
        confirmOverlay?.classList.remove('open');
    });

    confirmNo?.addEventListener('click', () => {
        confirmOverlay?.classList.remove('open');
        pendingDeleteUrl = null;
    });

    confirmOverlay?.addEventListener('click', (e) => {
        if (e.target === confirmOverlay) {
            confirmOverlay.classList.remove('open');
            pendingDeleteUrl = null;
        }
    });

    /* ========================================================
       TABLO ARAMA
       ======================================================== */
    const tableSearch = document.getElementById('tableSearch');
    const tableRows   = document.querySelectorAll('table tbody tr[data-search]');

    tableSearch?.addEventListener('input', () => {
        const q = tableSearch.value.toLowerCase().trim();
        tableRows.forEach(row => {
            const text = (row.dataset.search || '').toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });

    /* ========================================================
       FORM DOĞRULAMA (client-side)
       ======================================================== */
    const adminForms = document.querySelectorAll('form[data-validate]');

    adminForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let valid = true;

            form.querySelectorAll('[required]').forEach(field => {
                const errorEl = form.querySelector(`[data-error="${field.name}"]`);
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');
                    if (errorEl) errorEl.textContent = 'Bu alan zorunludur.';
                } else {
                    field.classList.remove('error');
                    if (errorEl) errorEl.textContent = '';
                }
            });

            if (!valid) {
                e.preventDefault();
                showAdminToast('Lütfen zorunlu alanları doldurun.', 'error');
                form.querySelector('.error')?.focus();
            }
        });
    });

    /* ========================================================
       TOAST BİLDİRİMİ
       ======================================================== */
    function showAdminToast(msg, type = 'info') {
        let toast = document.getElementById('adminToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'adminToast';
            toast.style.cssText = `
                position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(16px);
                background:#111; color:#fff; padding:12px 24px; border-radius:50px;
                font-size:0.85rem; font-weight:500; z-index:9999; opacity:0;
                transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1); white-space:nowrap;
                box-shadow:0 8px 24px rgba(0,0,0,0.2); font-family:var(--font,'Inter',sans-serif);
            `;
            document.body.appendChild(toast);
        }
        const colors = { success: '#059669', error: '#DC2626', warning: '#D97706', info: '#2563EB' };
        toast.style.background = colors[type] || '#111';
        toast.textContent = msg;
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        });
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(16px)';
        }, 4000);
    }

    window.showAdminToast = showAdminToast;

    // Sayfa yüklenince flash mesajı göster
    const flashMsg  = document.getElementById('flashMsg');
    const flashType = document.getElementById('flashType');
    if (flashMsg && flashMsg.textContent.trim()) {
        showAdminToast(flashMsg.textContent.trim(), flashType?.textContent.trim() || 'info');
    }

    /* ========================================================
       KARAKTER SAYACI (textarea)
       ======================================================== */
    document.querySelectorAll('textarea[data-maxlength]').forEach(ta => {
        const max      = parseInt(ta.dataset.maxlength, 10);
        const counterEl = document.querySelector(`[data-counter="${ta.name}"]`);
        const update   = () => {
            const left = max - ta.value.length;
            if (counterEl) {
                counterEl.textContent = `${ta.value.length}/${max}`;
                counterEl.style.color = left < 20 ? '#DC2626' : '';
            }
        };
        ta.addEventListener('input', update);
        update();
    });

});

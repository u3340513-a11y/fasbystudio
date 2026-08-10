/* ============================================================
   FASBY STUDIO - Ana JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    /* ========================================================
       NAVİGASYON
       ======================================================== */
    const navbar    = document.getElementById('navbar');
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');

    // Scroll'da navbar gölgesi
    const onScroll = () => {
        navbar?.classList.toggle('scrolled', window.scrollY > 20);
        scrollTopBtn?.classList.toggle('visible', window.scrollY > 400);
    };
    window.addEventListener('scroll', onScroll, { passive: true });

    // Hamburger menü
    hamburger?.addEventListener('click', () => {
        const isOpen = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('active', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Mobil menü linklerinde kapat
    mobileMenu?.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        });
    });

    // Dışarıya tıklayınca kapat
    document.addEventListener('click', (e) => {
        if (mobileMenu?.classList.contains('open') &&
            !mobileMenu.contains(e.target) &&
            !hamburger.contains(e.target)) {
            mobileMenu.classList.remove('open');
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    });

    // Aktif navlink (scroll spy)
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-menu a[href^="#"], .mobile-menu a[href^="#"]');

    const spyObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                navLinks.forEach(l => {
                    l.classList.toggle('active', l.getAttribute('href') === '#' + id);
                });
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px' });

    sections.forEach(s => spyObserver.observe(s));

    /* ========================================================
       SCROLL TO TOP
       ======================================================== */
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    scrollTopBtn?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ========================================================
       KATEGORİ FİLTRESİ
       ======================================================== */
    const filterBtns    = document.querySelectorAll('.filter-btn');
    const productCards  = document.querySelectorAll('.product-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const cat = btn.dataset.cat || 'all';

            let visible = 0;
            productCards.forEach(card => {
                const cardCat = card.dataset.category || '';
                const show = cat === 'all' || cardCat === cat;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Boş durum
            const noProducts = document.getElementById('noProducts');
            if (noProducts) noProducts.style.display = visible === 0 ? '' : 'none';
        });
    });

    /* ========================================================
       ÜRÜN MODAL
       ======================================================== */
    const modalOverlay  = document.getElementById('productModal');
    const modalClose    = document.getElementById('modalClose');

    // Modal aç
    function openModal(data) {
        if (!modalOverlay) return;

        document.getElementById('modal-image').src          = data.image || '/assets/images/placeholder.svg';
        document.getElementById('modal-image').alt          = data.title;
        document.getElementById('modal-cat').textContent    = data.category || '';
        document.getElementById('modal-title').textContent  = data.title || '';
        document.getElementById('modal-price').innerHTML    = data.price
            ? `<span class="price-val">${data.price}</span><small class="currency">${data.currency || ''}</small>`
            : '';
        document.getElementById('modal-desc').textContent   = data.description || '';

        // Etiketler
        const tagsEl = document.getElementById('modal-tags');
        tagsEl.innerHTML = '';
        if (data.tags) {
            data.tags.split(',').map(t => t.trim()).filter(Boolean).forEach(tag => {
                const span = document.createElement('span');
                span.className = 'modal-tag';
                span.textContent = '#' + tag;
                tagsEl.appendChild(span);
            });
        }

        // Etsy linki
        const etsyBtn = document.getElementById('modal-etsy-btn');
        if (etsyBtn) {
            etsyBtn.href = data.etsy_link || '#';
            etsyBtn.style.display = data.etsy_link ? '' : 'none';
        }

        // Etsy link içindeki görsel
        const imgPlaceholder = document.getElementById('modal-img-placeholder');
        const imgEl          = document.getElementById('modal-image');
        if (data.image) {
            imgEl.style.display    = '';
            if (imgPlaceholder) imgPlaceholder.style.display = 'none';
        } else {
            imgEl.style.display = 'none';
            if (imgPlaceholder) imgPlaceholder.style.display = '';
        }

        modalOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Focus yönetimi (erişilebilirlik)
        setTimeout(() => modalClose?.focus(), 100);
    }

    // Modal kapat
    function closeModal() {
        modalOverlay?.classList.remove('open');
        document.body.style.overflow = '';
    }

    modalClose?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });

    // ESC tuşu
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // Ürün kart tıklaması
    productCards.forEach(card => {
        card.addEventListener('click', () => {
            openModal({
                image:       card.dataset.image,
                title:       card.dataset.title,
                category:    card.dataset.categoryName,
                price:       card.dataset.price,
                currency:    card.dataset.currency,
                description: card.dataset.description,
                tags:        card.dataset.tags,
                etsy_link:   card.dataset.etsyLink,
            });
        });

        // Klavye erişimi
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });

    /* ========================================================
       İLETİŞİM FORMU (AJAX)
       ======================================================== */
    const contactForm = document.getElementById('contactForm');

    contactForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = contactForm.querySelector('[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>Gönderiliyor…</span>';
        btn.disabled = true;

        // Temizle
        contactForm.querySelectorAll('.form-error').forEach(el => el.textContent = '');

        try {
            const response = await fetch('/ajax/contact.php', {
                method: 'POST',
                body: new FormData(contactForm),
            });
            const json = await response.json();

            if (json.success) {
                showToast('Mesajınız başarıyla gönderildi! En kısa sürede yanıtlayacağım.', 'success');
                contactForm.reset();
            } else {
                if (json.errors) {
                    Object.entries(json.errors).forEach(([field, msg]) => {
                        const errEl = contactForm.querySelector(`[data-error="${field}"]`);
                        if (errEl) errEl.textContent = msg;
                    });
                }
                showToast(json.message || 'Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
            }
        } catch {
            showToast('Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });

    /* ========================================================
       TOAST BİLDİRİMİ
       ======================================================== */
    function showToast(msg, type = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.className = `toast ${type}`;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 4500);
    }

    window.showToast = showToast;

    /* ========================================================
       SCROLL ANİMASYONLARI
       ======================================================== */
    const animEls = document.querySelectorAll('.anim');

    if (animEls.length) {
        const animObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    animObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        animEls.forEach(el => animObserver.observe(el));
    }

    /* ========================================================
       SAYAÇ ANİMASYONU (Hero stats)
       ======================================================== */
    const counters = document.querySelectorAll('[data-count]');

    if (counters.length) {
        const countObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el  = entry.target;
                const end = parseInt(el.dataset.count, 10);
                const dur = 1800;
                const step = end / (dur / 16);
                let cur = 0;
                const timer = setInterval(() => {
                    cur = Math.min(cur + step, end);
                    el.textContent = Math.floor(cur) + (el.dataset.suffix || '');
                    if (cur >= end) clearInterval(timer);
                }, 16);
                countObserver.unobserve(el);
            });
        }, { threshold: 0.5 });

        counters.forEach(c => countObserver.observe(c));
    }

});

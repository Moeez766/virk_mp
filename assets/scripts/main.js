// ============================================================
// Omnes MarketPlace — Main JavaScript
// Vanilla JS only, no frameworks
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile navigation toggle ──────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const siteHeader = document.querySelector('.site-header');

    if (hamburger && siteHeader) {
        hamburger.addEventListener('click', function () {
            siteHeader.classList.toggle('mobile-nav-open');
            const expanded = siteHeader.classList.contains('mobile-nav-open');
            hamburger.setAttribute('aria-expanded', expanded);
        });

        // Close nav when clicking a link
        document.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                siteHeader.classList.remove('mobile-nav-open');
            });
        });
    }

    // ── Flash message auto-dismiss ────────────────────────────
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 400);
        }, 4000);
    });

    // ── Product category filter (products.php) ────────────────
    const filterBtns = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card-wrap');

    if (filterBtns.length > 0 && productCards.length > 0) {
        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Toggle active state — support multiple active filters
                const isCatFilter  = btn.dataset.cat;
                const isSaleFilter = btn.dataset.sale;

                if (btn.dataset.all !== undefined) {
                    // "All" clears all filters
                    filterBtns.forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    productCards.forEach(function (card) { card.classList.remove('hidden'); });
                    return;
                }

                // Remove "all" button active state
                const allBtn = document.querySelector('.filter-btn[data-all]');
                if (allBtn) allBtn.classList.remove('active');

                btn.classList.toggle('active');

                // Collect active filters
                const activeCats  = Array.from(document.querySelectorAll('.filter-btn[data-cat].active')).map(function (b) { return b.dataset.cat; });
                const activeSales = Array.from(document.querySelectorAll('.filter-btn[data-sale].active')).map(function (b) { return b.dataset.sale; });

                productCards.forEach(function (card) {
                    const cardCat  = card.dataset.category;
                    const cardSale = card.dataset.saleType;

                    const catOk  = activeCats.length === 0  || activeCats.includes(cardCat);
                    const saleOk = activeSales.length === 0 || activeSales.includes(cardSale);

                    if (catOk && saleOk) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });

                // If nothing is active, reset
                if (activeCats.length === 0 && activeSales.length === 0) {
                    if (allBtn) allBtn.classList.add('active');
                    productCards.forEach(function (card) { card.classList.remove('hidden'); });
                }
            });
        });
    }

    // ── Auction countdown timers ──────────────────────────────
    const timers = document.querySelectorAll('.auction-timer[data-end]');

    function updateTimer(timerEl) {
        const endTime = new Date(timerEl.dataset.end).getTime();
        const now     = Date.now();
        const diff    = endTime - now;

        if (diff <= 0) {
            timerEl.innerHTML = '<span class="text-muted" style="font-size:0.9rem;">Auction ended</span>';
            return;
        }

        const days    = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        const daysEl    = timerEl.querySelector('.t-days');
        const hoursEl   = timerEl.querySelector('.t-hours');
        const minsEl    = timerEl.querySelector('.t-mins');
        const secsEl    = timerEl.querySelector('.t-secs');

        if (daysEl)  daysEl.textContent  = String(days).padStart(2, '0');
        if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
        if (minsEl)  minsEl.textContent  = String(minutes).padStart(2, '0');
        if (secsEl)  secsEl.textContent  = String(seconds).padStart(2, '0');
    }

    timers.forEach(function (timer) {
        updateTimer(timer);
        setInterval(function () { updateTimer(timer); }, 1000);
    });

    // ── Image preview on product upload form ──────────────────
    const imageInput = document.getElementById('product_image');
    const imagePreview = document.getElementById('image_preview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ── Confirm delete buttons ─────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ── Cart quantity / remove (AJAX-lite via form) ───────────
    // Cart remove buttons use standard form POST — no JS needed.

    // ── Toggle password visibility ─────────────────────────────
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(this.dataset.target);
            if (!target) return;
            const type = target.getAttribute('type') === 'password' ? 'text' : 'password';
            target.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁' : '🙈';
        });
    });

    // ── Sticky header shadow on scroll ────────────────────────
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 10) {
                header.style.boxShadow = '0 4px 24px rgba(26,5,51,0.35)';
            } else {
                header.style.boxShadow = '';
            }
        }, { passive: true });
    }

    // ── Smooth scroll for anchor links ────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ── Negotiate form max offer validation ───────────────────
    const negotiateForm = document.getElementById('negotiate-form');
    if (negotiateForm) {
        negotiateForm.addEventListener('submit', function (e) {
            const offerInput = document.getElementById('offer_amount');
            const listedPrice = parseFloat(negotiateForm.dataset.listedPrice || 0);
            const offerVal    = parseFloat(offerInput.value);

            if (isNaN(offerVal) || offerVal <= 0) {
                e.preventDefault();
                showInlineError(offerInput, 'Please enter a valid offer amount.');
                return;
            }

            if (offerVal >= listedPrice) {
                if (!confirm('Your offer equals or exceeds the listed price. Do you want to use Buy It Now instead?')) {
                    e.preventDefault();
                }
            }
        });
    }

    // ── Inline form error helper ──────────────────────────────
    function showInlineError(input, message) {
        let errEl = input.parentNode.querySelector('.form-error');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.className = 'form-error text-small';
            errEl.style.color = 'var(--danger)';
            errEl.style.marginTop = '4px';
            input.parentNode.appendChild(errEl);
        }
        errEl.textContent = message;
        input.focus();
    }

    // ── Bid form validation ───────────────────────────────────
    const bidForm = document.getElementById('bid-form');
    if (bidForm) {
        bidForm.addEventListener('submit', function (e) {
            const maxBid     = parseFloat(document.getElementById('max_bid').value);
            const currentTop = parseFloat(bidForm.dataset.currentTop || 0);

            if (isNaN(maxBid) || maxBid <= 0) {
                e.preventDefault();
                alert('Please enter a valid maximum bid amount.');
                return;
            }

            if (maxBid <= currentTop) {
                e.preventDefault();
                alert('Your maximum bid must exceed the current highest bid of €' + currentTop.toFixed(2));
            }
        });
    }

});

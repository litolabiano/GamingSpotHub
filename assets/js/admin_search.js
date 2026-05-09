/**
 * GamingSpotHub Admin — Shared Search / Filter / Pagination Utility
 * =================================================================
 * Classes:
 *   AdminPaginator(tableIdOrEl, opts)  — paginate any table tbody
 *   AdminCardPaginator(gridEl, cardSel, opts) — paginate card grids
 *
 * Helpers:
 *   mkSearchBar(cfg) — inject a pre-built search bar HTML string
 *
 * Filter contract:
 *   Filter functions should call row.classList.toggle('asb-hidden', !matches)
 *   Then call paginator.reset() or paginator.apply().
 *   Paginator only pages rows WITHOUT the 'asb-hidden' class.
 */

/* ── Inject shared CSS once ─────────────────────────────────────────────── */
(function injectCSS() {
    if (document.getElementById('adminSearchCSS')) return;
    const s = document.createElement('style');
    s.id = 'adminSearchCSS';
    s.textContent = `
    /* ── Search bar ── */
    .asb-wrap {
        display:flex; gap:8px; align-items:center; flex-wrap:wrap;
    }
    .asb-search {
        position:relative; flex:1; min-width:140px; max-width:300px;
    }
    .asb-search > i {
        position:absolute; left:10px; top:50%; transform:translateY(-50%);
        color:rgba(255,255,255,.3); font-size:12px; pointer-events:none;
    }
    .asb-input {
        width:100%; padding:7px 30px 7px 30px;
        background:rgba(10,33,81,.45);
        border:1px solid rgba(95,133,218,.22);
        border-radius:9px; color:#f0f0f0; font-size:12px;
        font-family:inherit; outline:none; box-sizing:border-box;
        transition:border-color .18s, box-shadow .18s;
    }
    .asb-input:focus {
        border-color:rgba(32,200,161,.5);
        box-shadow:0 0 0 3px rgba(32,200,161,.08);
    }
    .asb-input::placeholder { color:rgba(255,255,255,.25); }
    .asb-clear {
        position:absolute; right:8px; top:50%; transform:translateY(-50%);
        background:none; border:none; cursor:pointer;
        color:rgba(255,255,255,.3); font-size:11px; padding:2px 3px;
        display:none; transition:color .15s;
    }
    .asb-clear:hover { color:#fb566b; }
    .asb-select {
        padding:7px 11px;
        background:rgba(10,33,81,.45);
        border:1px solid rgba(95,133,218,.22);
        border-radius:9px; color:#f0f0f0; font-size:12px;
        font-family:inherit; outline:none; cursor:pointer;
        transition:border-color .18s;
    }
    .asb-select:focus { border-color:rgba(32,200,161,.5); }
    .asb-count {
        font-size:11px; color:rgba(255,255,255,.3);
        white-space:nowrap; flex-shrink:0;
    }
    .asb-no-results {
        text-align:center; padding:30px 20px;
        color:rgba(255,255,255,.3); font-size:13px; display:none;
    }

    /* ── Pagination bar ── */
    .asb-pagination {
        display:flex; align-items:center; gap:6px; flex-wrap:wrap;
        padding:12px 16px; border-top:1px solid rgba(95,133,218,.1);
        font-size:12px;
    }
    .asb-pagination-info {
        color:rgba(255,255,255,.35); margin-right:auto; white-space:nowrap;
    }
    .asb-pg-btn {
        min-width:30px; height:30px;
        background:rgba(10,33,81,.5);
        border:1px solid rgba(95,133,218,.2);
        border-radius:7px; color:rgba(255,255,255,.6);
        font-size:12px; font-family:inherit;
        cursor:pointer; padding:0 8px;
        transition:background .15s, border-color .15s, color .15s;
        display:inline-flex; align-items:center; justify-content:center;
    }
    .asb-pg-btn:hover:not(:disabled) {
        background:rgba(32,200,161,.12);
        border-color:rgba(32,200,161,.4);
        color:#20c8a1;
    }
    .asb-pg-btn.active {
        background:rgba(32,200,161,.18);
        border-color:rgba(32,200,161,.5);
        color:#20c8a1; font-weight:700;
    }
    .asb-pg-btn:disabled {
        opacity:.3; cursor:default;
    }
    .asb-pg-dots {
        color:rgba(255,255,255,.25); padding:0 2px; font-size:13px;
        line-height:30px;
    }
    .asb-per-page {
        padding:5px 8px;
        background:rgba(10,33,81,.45);
        border:1px solid rgba(95,133,218,.2);
        border-radius:7px; color:rgba(255,255,255,.5);
        font-size:11px; font-family:inherit; outline:none; cursor:pointer;
        margin-left:6px;
    }

    /* ── Arrow Field Wrap (for form inputs & selects) ── */
    .asb-field-wrap {
        display: flex;
        align-items: center;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 8px;
        padding: 8px 14px;
        cursor: pointer;
        transition: all .2s;
        position: relative;
    }
    .asb-field-wrap:focus-within, .asb-field-wrap:hover {
        border-color: #20c8a1;
        background: rgba(255,255,255,.08);
    }
    .asb-field-icon {
        color: rgba(255,255,255,.4);
        font-size: 14px;
        margin-right: 12px;
        transition: transform .2s;
    }
    .asb-field-wrap:hover .asb-field-icon { transform: scale(1.08); }
    .asb-field-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .asb-field-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #888;
        margin-bottom: 2px;
    }
    .asb-native-input {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 13px;
        font-family: inherit;
        padding: 0;
        outline: none;
        width: 100%;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    .asb-native-input option {
        background: #0d1117;
        color: #fff;
    }
    .asb-native-input::-webkit-calendar-picker-indicator {
        opacity: 0;
        cursor: pointer;
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }
    .asb-field-arrow {
        color: rgba(255,255,255,.3);
        font-size: 12px;
        margin-left: 12px;
        transition: all .2s;
        pointer-events: none;
    }
    .asb-field-wrap:hover .asb-field-arrow, .asb-field-wrap:focus-within .asb-field-arrow {
        color: rgba(32,200,161,.7);
        transform: translateX(2px);
    }
    `;
    document.head.appendChild(s);
})();


/* ══════════════════════════════════════════════════════════════════════════
   AdminPaginator — for <table> elements
   ══════════════════════════════════════════════════════════════════════════ */
class AdminPaginator {
    /**
     * @param {string|HTMLElement} tableRef  — ID string or table element
     * @param {object} opts
     *   opts.pageSize      {number}   default 10
     *   opts.pageSizes     {number[]} choices for per-page select, default [10,25,50]
     *   opts.paginationSel {string}   CSS selector for the pagination container div
     *   opts.noResultsSel  {string}   CSS selector for no-results message element
     *   opts.countSel      {string}   CSS selector for result count span
     */
    constructor(tableRef, opts = {}) {
        this.table    = typeof tableRef === 'string'
                        ? document.getElementById(tableRef)
                        : tableRef;
        this.tbody    = this.table?.querySelector('tbody');
        this.pageSize = opts.pageSize  || 10;
        this.pageSizes= opts.pageSizes || [10, 25, 50];
        this.current  = 1;

        this._paginationEl = opts.paginationSel
            ? document.querySelector(opts.paginationSel) : null;
        this._noResultsEl  = opts.noResultsSel
            ? document.querySelector(opts.noResultsSel) : null;
        this._countEl      = opts.countSel
            ? document.querySelector(opts.countSel) : null;
    }

    /* Returns rows not hidden by the search filter */
    _activeRows() {
        if (!this.tbody) return [];
        return Array.from(this.tbody.querySelectorAll('tr'))
                    .filter(r => !r.classList.contains('asb-hidden'));
    }

    /* Apply display state for the current page */
    apply() {
        const rows  = this._activeRows();
        const total = rows.length;
        const pages = Math.max(1, Math.ceil(total / this.pageSize));

        if (this.current > pages) this.current = pages;
        if (this.current < 1)    this.current = 1;

        const start = (this.current - 1) * this.pageSize;
        const end   = start + this.pageSize;

        /* Show only current-page rows; hide the rest */
        rows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });
        /* Also hide all asb-hidden rows explicitly */
        if (this.tbody) {
            this.tbody.querySelectorAll('tr.asb-hidden').forEach(r => {
                r.style.display = 'none';
            });
        }

        /* Update count text */
        if (this._countEl) {
            this._countEl.textContent = total > 0
                ? `${start + 1}–${Math.min(end, total)} of ${total}`
                : '0 results';
        }

        /* No-results state */
        if (this._noResultsEl) {
            this._noResultsEl.style.display = total === 0 ? 'block' : 'none';
        }

        this._renderControls(total, pages, start, end);
    }

    /* Reset to page 1 and re-apply */
    reset() {
        this.current = 1;
        this.apply();
    }

    /* Navigate to a page */
    goTo(page) {
        this.current = page;
        this.apply();
    }

    /* Build and insert pagination controls */
    _renderControls(total, pages, start, end) {
        if (!this._paginationEl) return;

        if (total === 0) {
            this._paginationEl.innerHTML = '';
            return;
        }

        const cur = this.current;

        /* Page number buttons with smart ellipsis */
        const pgBtns = this._pageNumbers(cur, pages).map(p => {
            if (p === '…') return `<span class="asb-pg-dots">…</span>`;
            return `<button class="asb-pg-btn${p === cur ? ' active' : ''}"
                            onclick="_asbGoTo(this,${p})">${p}</button>`;
        }).join('');

        /* Per-page selector */
        const perPageOpts = this.pageSizes.map(n =>
            `<option value="${n}"${n === this.pageSize ? ' selected' : ''}>${n}</option>`
        ).join('');

        this._paginationEl.innerHTML = `
        <div class="asb-pagination">
            <span class="asb-pagination-info">
                Showing ${start + 1}–${Math.min(end, total)} of <strong>${total}</strong>
            </span>
            <button class="asb-pg-btn" onclick="_asbGoTo(this,'prev')"
                    ${cur <= 1 ? 'disabled' : ''}
                    title="Previous page">
                <i class="fas fa-chevron-left" style="font-size:10px;"></i>
            </button>
            ${pgBtns}
            <button class="asb-pg-btn" onclick="_asbGoTo(this,'next')"
                    ${cur >= pages ? 'disabled' : ''}
                    title="Next page">
                <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            </button>
            <select class="asb-per-page" onchange="_asbPerPage(this)"
                    title="Rows per page">
                ${perPageOpts}
            </select>
        </div>`;

        /* Store reference for click handlers */
        this._paginationEl._paginator = this;
    }

    /* Smart page number array (with ellipsis) */
    _pageNumbers(cur, total) {
        if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
        const pages = new Set([1, total, cur]);
        for (let d = -2; d <= 2; d++) {
            const p = cur + d;
            if (p > 0 && p <= total) pages.add(p);
        }
        const sorted = Array.from(pages).sort((a, b) => a - b);
        const result = [];
        let prev = 0;
        sorted.forEach(p => {
            if (p - prev > 1) result.push('…');
            result.push(p);
            prev = p;
        });
        return result;
    }
}


/* ══════════════════════════════════════════════════════════════════════════
   AdminCardPaginator — for CSS card grids
   ══════════════════════════════════════════════════════════════════════════ */
class AdminCardPaginator {
    constructor(gridEl, cardSel, opts = {}) {
        this.grid     = typeof gridEl === 'string'
                        ? document.querySelector(gridEl) : gridEl;
        this.cardSel  = cardSel || '.console-card';
        this.pageSize = opts.pageSize  || 12;
        this.pageSizes= opts.pageSizes || [12, 24, 48];
        this.current  = 1;

        this._paginationEl = opts.paginationSel
            ? document.querySelector(opts.paginationSel) : null;
        this._noResultsEl  = opts.noResultsSel
            ? document.querySelector(opts.noResultsSel) : null;
        this._countEl      = opts.countSel
            ? document.querySelector(opts.countSel) : null;
    }

    _activeCards() {
        if (!this.grid) return [];
        return Array.from(this.grid.querySelectorAll(this.cardSel))
                    .filter(c => !c.classList.contains('asb-hidden'));
    }

    apply() {
        const cards = this._activeCards();
        const total = cards.length;
        const pages = Math.max(1, Math.ceil(total / this.pageSize));

        if (this.current > pages) this.current = pages;
        if (this.current < 1)    this.current = 1;

        const start = (this.current - 1) * this.pageSize;
        const end   = start + this.pageSize;

        cards.forEach((card, i) => {
            card.style.display = (i >= start && i < end) ? '' : 'none';
        });
        this.grid?.querySelectorAll(this.cardSel + '.asb-hidden').forEach(c => {
            c.style.display = 'none';
        });

        if (this._countEl) {
            this._countEl.textContent = total > 0
                ? `${start + 1}–${Math.min(end, total)} of ${total}`
                : '0 consoles';
        }
        if (this._noResultsEl) {
            this._noResultsEl.style.display = total === 0 ? 'block' : 'none';
        }

        this._renderControls(total, pages, start, end);
    }

    reset() { this.current = 1; this.apply(); }

    goTo(page) { this.current = page; this.apply(); }

    _pageNumbers(cur, total) {
        if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
        const pages = new Set([1, total, cur]);
        for (let d = -2; d <= 2; d++) {
            const p = cur + d;
            if (p > 0 && p <= total) pages.add(p);
        }
        const sorted = Array.from(pages).sort((a, b) => a - b);
        const result = [];
        let prev = 0;
        sorted.forEach(p => {
            if (p - prev > 1) result.push('…');
            result.push(p);
            prev = p;
        });
        return result;
    }

    _renderControls(total, pages, start, end) {
        if (!this._paginationEl) return;
        if (total === 0) { this._paginationEl.innerHTML = ''; return; }

        const cur = this.current;
        const pgBtns = this._pageNumbers(cur, pages).map(p => {
            if (p === '…') return `<span class="asb-pg-dots">…</span>`;
            return `<button class="asb-pg-btn${p === cur ? ' active' : ''}"
                            onclick="_asbGoTo(this,${p})">${p}</button>`;
        }).join('');

        const perPageOpts = this.pageSizes.map(n =>
            `<option value="${n}"${n === this.pageSize ? ' selected' : ''}>${n}</option>`
        ).join('');

        this._paginationEl.innerHTML = `
        <div class="asb-pagination">
            <span class="asb-pagination-info">
                Showing ${start + 1}–${Math.min(end, total)} of <strong>${total}</strong>
            </span>
            <button class="asb-pg-btn" onclick="_asbGoTo(this,'prev')"
                    ${cur <= 1 ? 'disabled' : ''} title="Previous">
                <i class="fas fa-chevron-left" style="font-size:10px;"></i>
            </button>
            ${pgBtns}
            <button class="asb-pg-btn" onclick="_asbGoTo(this,'next')"
                    ${cur >= pages ? 'disabled' : ''} title="Next">
                <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            </button>
            <select class="asb-per-page" onchange="_asbPerPage(this)" title="Per page">
                ${perPageOpts}
            </select>
        </div>`;

        this._paginationEl._paginator = this;
    }
}


/* ── Global click-handler helpers (called from inline onclick) ────────────── */
function _asbGoTo(btn, page) {
    const container = btn.closest('.asb-pagination')?.parentElement;
    const pag = container?._paginator;
    if (!pag) return;
    const total = pag instanceof AdminPaginator
        ? pag._activeRows().length
        : pag._activeCards().length;
    const pages = Math.max(1, Math.ceil(total / pag.pageSize));

    if (page === 'prev') page = Math.max(1, pag.current - 1);
    if (page === 'next') page = Math.min(pages, pag.current + 1);
    pag.goTo(parseInt(page));
}

function _asbPerPage(select) {
    const container = select.closest('.asb-pagination')?.parentElement;
    const pag = container?._paginator;
    if (!pag) return;
    pag.pageSize = parseInt(select.value);
    pag.reset();
}

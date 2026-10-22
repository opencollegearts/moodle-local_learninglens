/**
 * Learning Lens binder (no ES6 imports, no core/templates or core/ajax).
 *
 * Filename must stay *-lazy so Moodle excludes it from the production first.js
 * combo. Do not write the AMD factory call in comments: Moodle names the first
 * occurrence of that call in the file, and a comment match leaves the real
 * factory anonymous, which aborts core/first and ghosts Snap/Boost navigation.
 *
 * @module     local_learninglens/widget-lazy
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';
    var STORAGE_KEY = 'local_learninglens';
    var CONTRAST_CYCLE = ['', 'dark', 'light', 'invert', 'extra'];
    var TEXT_CYCLE = [100, 125, 150, 200];
    var SPEECH_CYCLE = [75, 100, 125, 150];
    var BOOL_FEATURES = [
        'dyslexia', 'spacing', 'links', 'pauseanim',
        'hideimages', 'readingguide', 'readingmask', 'readaloud'
    ];
    var READABLE_SELECTOR = 'p, li, h1, h2, h3, h4, h5, h6, dd, blockquote, td, th, figcaption, pre';
    var INTERACTIVE_SELECTOR = 'a, button, input, select, textarea, label, summary, ' +
        '[role="button"], [role="link"], [role="menuitem"]';
    var CHUNK_MAX = 180;

    var cfg = {};
    var prefs = {};
    var root = null;
    var panel = null;
    var launcher = null;
    var lastFocus = null;
    var saveTimer = null;
    var pointerY = 0;
    var speakTimer = null;
    var highlightEl = null;
    var speech = {
        queue: [],
        index: 0,
        status: 'idle',
        supported: typeof window !== 'undefined' && 'speechSynthesis' in window
    };

    var defaultPrefs = function() {
        return {
            contrast: '',
            dyslexia: false,
            spacing: false,
            links: false,
            textsize: 100,
            pauseanim: false,
            hideimages: false,
            readingguide: false,
            readingmask: false,
            readaloud: false,
            speechrate: 100
        };
    };

    var mergePrefs = function(raw) {
        var merged = defaultPrefs();
        if (!raw || typeof raw !== 'object') {
            return merged;
        }
        if (CONTRAST_CYCLE.indexOf(raw.contrast) !== -1) {
            merged.contrast = raw.contrast;
        }
        BOOL_FEATURES.forEach(function(key) {
            if (raw[key] === true || raw[key] === 1 || raw[key] === '1') {
                merged[key] = true;
            }
        });
        var size = parseInt(raw.textsize, 10);
        if (TEXT_CYCLE.indexOf(size) !== -1) {
            merged.textsize = size;
        }
        var rate = parseInt(raw.speechrate, 10);
        if (SPEECH_CYCLE.indexOf(rate) !== -1) {
            merged.speechrate = rate;
        }
        return merged;
    };

    var compactPrefs = function(current) {
        var out = {};
        if (current.contrast) {
            out.contrast = current.contrast;
        }
        BOOL_FEATURES.forEach(function(key) {
            if (current[key]) {
                out[key] = true;
            }
        });
        if (current.textsize && current.textsize !== 100) {
            out.textsize = current.textsize;
        }
        if (current.speechrate && current.speechrate !== 100) {
            out.speechrate = current.speechrate;
        }
        return out;
    };

    var featureEnabled = function(id) {
        if (id === 'speechrate') {
            return !!(cfg.features && cfg.features.readaloud);
        }
        return !!(cfg.features && cfg.features[id]);
    };

    var applyHtmlClasses = function() {
        var html = document.documentElement;
        var drop = /^(local-learninglens-|local-a11ywidget-|a11y-contrast-|a11y-dyslexia|a11y-spacing|a11y-links|a11y-text-|a11y-pause-anim|a11y-hide-images|a11y-reading-guide|a11y-reading-mask|a11y-readaloud)/;
        html.className = (html.className || '').split(/\s+/).filter(function(name) {
            return name && !drop.test(name);
        }).join(' ');
        if (featureEnabled('contrast') && prefs.contrast) {
            html.classList.add('local-learninglens-contrast-' + prefs.contrast);
        }
        if (featureEnabled('dyslexia') && prefs.dyslexia) {
            html.classList.add('local-learninglens-dyslexia');
        }
        if (featureEnabled('spacing') && prefs.spacing) {
            html.classList.add('local-learninglens-spacing');
        }
        if (featureEnabled('links') && prefs.links) {
            html.classList.add('local-learninglens-links');
        }
        if (featureEnabled('textsize') && prefs.textsize && prefs.textsize !== 100) {
            html.classList.add('local-learninglens-text-' + prefs.textsize);
        }
        if (featureEnabled('pauseanim') && prefs.pauseanim) {
            html.classList.add('local-learninglens-pause-anim');
        }
        if (featureEnabled('hideimages') && prefs.hideimages) {
            html.classList.add('local-learninglens-hide-images');
        }
        if (featureEnabled('readingguide') && prefs.readingguide) {
            html.classList.add('local-learninglens-reading-guide');
        }
        if (featureEnabled('readingmask') && prefs.readingmask) {
            html.classList.add('local-learninglens-reading-mask');
        }
        if (featureEnabled('readaloud') && prefs.readaloud) {
            html.classList.add('local-learninglens-readaloud');
        }
    };

    var ensureOverlay = function(id, extraClass) {
        var el = document.getElementById(id);
        if (el) {
            return el;
        }
        el = document.createElement('div');
        el.id = id;
        el.className = extraClass || '';
        el.setAttribute('aria-hidden', 'true');
        document.body.appendChild(el);
        return el;
    };

    var positionOverlays = function(y) {
        if (typeof y !== 'number') {
            y = pointerY;
        }
        var guide = document.getElementById('local-learninglens-guide');
        if (guide) {
            var gh = guide.offsetHeight || 38;
            guide.style.top = Math.max(0, y - gh / 2) + 'px';
        }
        var mask = document.getElementById('local-learninglens-mask');
        if (mask) {
            var mh = mask.offsetHeight || 120;
            mask.style.top = Math.max(0, y - mh / 2) + 'px';
        }
    };

    var syncOverlays = function() {
        if (featureEnabled('readingguide') && prefs.readingguide) {
            ensureOverlay('local-learninglens-guide');
            positionOverlays();
        } else if (document.getElementById('local-learninglens-guide')) {
            document.getElementById('local-learninglens-guide').remove();
        }
        if (featureEnabled('readingmask') && prefs.readingmask) {
            ensureOverlay('local-learninglens-mask');
            positionOverlays();
        } else if (document.getElementById('local-learninglens-mask')) {
            document.getElementById('local-learninglens-mask').remove();
        }
    };

    var clearAltText = function() {
        document.querySelectorAll('.local-learninglens-alt').forEach(function(node) {
            node.remove();
        });
        document.querySelectorAll('[data-learninglens-alt]').forEach(function(node) {
            delete node.dataset.learninglensAlt;
        });
    };

    var insertAltText = function() {
        if (!featureEnabled('hideimages') || !prefs.hideimages) {
            clearAltText();
            return;
        }
        document.querySelectorAll('img[alt], video').forEach(function(el) {
            if (el.closest('#local-learninglens')) {
                return;
            }
            if (el.dataset.learninglensAlt) {
                return;
            }
            var text = (el.getAttribute('alt') || el.getAttribute('title') || '').trim();
            if (!text) {
                return;
            }
            var span = document.createElement('span');
            span.className = 'local-learninglens-alt';
            span.textContent = text;
            el.insertAdjacentElement('afterend', span);
            el.dataset.learninglensAlt = '1';
        });
    };

    var pauseMedia = function() {
        if (!featureEnabled('pauseanim') || !prefs.pauseanim) {
            return;
        }
        document.querySelectorAll('video, audio').forEach(function(media) {
            try {
                media.pause();
            } catch (e) {
                // Ignore media that cannot pause.
            }
        });
    };

    var applyAll = function() {
        applyHtmlClasses();
        syncOverlays();
        insertAltText();
        pauseMedia();
        syncCards();
    };

    var persist = function() {
        var compact = compactPrefs(prefs);
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(compact));
        } catch (e) {
            // Private mode / quota.
        }
        if (!cfg.loggedin || typeof window.require !== 'function') {
            return;
        }
        if (saveTimer) {
            clearTimeout(saveTimer);
        }
        saveTimer = setTimeout(function() {
            try {
                window.require(['core/ajax'], function(Ajax) {
                    Ajax.call([{
                        methodname: 'core_user_update_user_preferences',
                        args: {
                            preferences: [{
                                type: 'local_learninglens_prefs',
                                value: JSON.stringify(compact)
                            }]
                        }
                    }])[0].catch(function() {
                        // Preference save is best-effort.
                    });
                });
            } catch (e) {
                // Ignore.
            }
        }, 300);
    };

    var contrastLabel = function(value) {
        var strings = cfg.strings || {};
        if (value === 'dark') {
            return strings.contrast_dark || 'Dark';
        }
        if (value === 'light') {
            return strings.contrast_light || 'Light';
        }
        if (value === 'invert') {
            return strings.contrast_invert || 'Invert';
        }
        if (value === 'extra') {
            return strings.contrast_extra || 'Extra';
        }
        return '';
    };

    var readAloudLabel = function() {
        var strings = cfg.strings || {};
        if (!speech.supported) {
            return strings.readaloud_unavailable || 'Not available';
        }
        if (speech.status === 'playing') {
            return strings.readaloud_playing || 'Playing';
        }
        if (speech.status === 'paused') {
            return strings.readaloud_paused || 'Paused';
        }
        if (prefs.readaloud) {
            return strings.readaloud_on || 'Click text';
        }
        return '';
    };

    var syncCards = function() {
        if (!root) {
            return;
        }
        root.querySelectorAll('[data-feature]').forEach(function(card) {
            var id = card.getAttribute('data-feature');
            var active = false;
            var state = '';
            if (id === 'contrast') {
                active = !!prefs.contrast;
                state = contrastLabel(prefs.contrast);
            } else if (id === 'textsize') {
                active = prefs.textsize && prefs.textsize !== 100;
                state = active ? (prefs.textsize + '%') : '';
            } else if (id === 'speechrate') {
                active = prefs.speechrate && prefs.speechrate !== 100;
                state = (prefs.speechrate || 100) + '%';
            } else if (id === 'readaloud') {
                active = !!prefs.readaloud || speech.status !== 'idle';
                state = readAloudLabel();
            } else {
                active = !!prefs[id];
            }
            card.classList.toggle('is-active', active);
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
            var stateEl = card.querySelector('[data-state]');
            if (stateEl) {
                stateEl.textContent = state;
            }
        });
    };

    var cycleValue = function(list, current) {
        var index = list.indexOf(current);
        if (index === -1) {
            index = 0;
        }
        return list[(index + 1) % list.length];
    };

    var clearHighlight = function() {
        if (highlightEl) {
            highlightEl.classList.remove('local-learninglens-readaloud-current');
            highlightEl = null;
        }
    };

    var setHighlight = function(el) {
        clearHighlight();
        if (el && el.classList) {
            el.classList.add('local-learninglens-readaloud-current');
            highlightEl = el;
            if (typeof el.scrollIntoView === 'function') {
                try {
                    el.scrollIntoView({block: 'center', inline: 'nearest'});
                } catch (e) {
                    el.scrollIntoView(true);
                }
            }
        }
    };

    var cancelEngine = function() {
        if (speakTimer) {
            clearTimeout(speakTimer);
            speakTimer = null;
        }
        if (speech.supported) {
            try {
                window.speechSynthesis.cancel();
            } catch (e) {
                // Ignore.
            }
        }
    };

    var stopSpeech = function() {
        cancelEngine();
        speech.queue = [];
        speech.index = 0;
        speech.status = 'idle';
        clearHighlight();
        syncCards();
    };

    var pauseSpeech = function() {
        if (!speech.supported || speech.status !== 'playing') {
            return;
        }
        try {
            window.speechSynthesis.pause();
        } catch (e) {
            // Ignore.
        }
        speech.status = 'paused';
        syncCards();
    };

    var resumeSpeech = function() {
        if (!speech.supported || speech.status !== 'paused') {
            return;
        }
        try {
            window.speechSynthesis.resume();
        } catch (e) {
            // Ignore.
        }
        // Some engines ignore resume(); restart the current chunk if still paused.
        speakTimer = setTimeout(function() {
            if (speech.status === 'paused') {
                speakFromIndex(speech.index);
            }
        }, 80);
        speech.status = 'playing';
        syncCards();
    };

    var normaliseText = function(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    };

    var splitSentences = function(text) {
        var sentences = [];
        var buf = '';
        var i;
        var ch;
        var next;
        for (i = 0; i < text.length; i++) {
            ch = text.charAt(i);
            buf += ch;
            next = text.charAt(i + 1);
            if ((ch === '.' || ch === '!' || ch === '?') && (next === '' || next === ' ')) {
                if (buf.trim()) {
                    sentences.push(buf.trim());
                }
                buf = '';
            }
        }
        if (buf.trim()) {
            sentences.push(buf.trim());
        }
        return sentences.length ? sentences : [text];
    };

    var wrapChunks = function(text, el) {
        var out = [];
        var sentences = splitSentences(text);
        var s;
        var piece;
        var rest;
        var cut;
        for (s = 0; s < sentences.length; s++) {
            piece = sentences[s];
            while (piece.length > CHUNK_MAX) {
                cut = piece.lastIndexOf(' ', CHUNK_MAX);
                if (cut < 40) {
                    cut = CHUNK_MAX;
                }
                out.push({text: piece.slice(0, cut).trim(), el: el});
                piece = piece.slice(cut).trim();
            }
            if (piece) {
                out.push({text: piece, el: el});
            }
        }
        return out;
    };

    var mainRoot = function() {
        return document.querySelector('#region-main, [role="main"], #page-content, #region-main-box, .course-content, #maincontent')
            || document.body;
    };

    var isSkipped = function(el) {
        if (!el || el.nodeType !== 1) {
            return true;
        }
        if (el.closest('#local-learninglens')) {
            return true;
        }
        if (el.closest('script, style, noscript, [aria-hidden="true"]')) {
            return true;
        }
        if (el.getAttribute && el.getAttribute('hidden') !== null) {
            return true;
        }
        return false;
    };

    var collectFrom = function(startEl) {
        var rootEl = mainRoot();
        var nodes = rootEl.querySelectorAll(READABLE_SELECTOR);
        var list = [];
        var started = !startEl;
        var i;
        var el;
        var text;
        for (i = 0; i < nodes.length; i++) {
            el = nodes[i];
            if (isSkipped(el)) {
                continue;
            }
            if (!started) {
                if (el === startEl || el.contains(startEl) || (startEl.contains && startEl.contains(el))) {
                    started = true;
                } else {
                    continue;
                }
            }
            text = normaliseText(el.innerText || el.textContent || '');
            if (text.length < 2) {
                continue;
            }
            list = list.concat(wrapChunks(text, el));
        }
        return list;
    };

    var selectionChunks = function() {
        var sel;
        var text;
        var node;
        var el;
        try {
            sel = window.getSelection ? window.getSelection() : null;
        } catch (e) {
            sel = null;
        }
        if (!sel) {
            return [];
        }
        text = normaliseText(String(sel));
        if (text.length < 2) {
            return [];
        }
        node = sel.anchorNode;
        el = node && node.nodeType === 1 ? node : (node ? node.parentElement : null);
        return wrapChunks(text, el);
    };

    var speakFromIndex = function(index) {
        if (!speech.supported) {
            return;
        }
        if (index >= speech.queue.length) {
            speech.status = 'idle';
            clearHighlight();
            syncCards();
            return;
        }
        speech.index = index;
        var item = speech.queue[index];
        setHighlight(item.el);
        var utterance = new SpeechSynthesisUtterance(item.text);
        utterance.rate = (prefs.speechrate || 100) / 100;
        utterance.lang = document.documentElement.lang || 'en';
        utterance.onend = function() {
            if (speech.status !== 'playing') {
                return;
            }
            speakFromIndex(speech.index + 1);
        };
        utterance.onerror = function() {
            if (speech.status !== 'playing') {
                return;
            }
            speakFromIndex(speech.index + 1);
        };
        speech.status = 'playing';
        try {
            window.speechSynthesis.speak(utterance);
        } catch (e) {
            speech.status = 'idle';
        }
        syncCards();
    };

    var startChunks = function(chunks) {
        cancelEngine();
        speech.queue = chunks || [];
        speech.index = 0;
        if (!speech.queue.length) {
            speech.status = 'idle';
            clearHighlight();
            syncCards();
            return;
        }
        speech.status = 'playing';
        // cancel() is async in Chromium; wait a tick before speaking.
        speakTimer = setTimeout(function() {
            speakFromIndex(0);
        }, 60);
        syncCards();
    };

    var startReadAloud = function(fromEl) {
        if (!speech.supported) {
            syncCards();
            return;
        }
        var chunks = selectionChunks();
        if (!chunks.length) {
            chunks = collectFrom(fromEl || null);
        }
        startChunks(chunks);
    };

    var closestReadable = function(target) {
        if (!target || !target.closest) {
            return null;
        }
        return target.closest(READABLE_SELECTOR);
    };

    var handleReadAloudCard = function() {
        if (!speech.supported) {
            syncCards();
            return;
        }
        if (speech.status === 'playing') {
            pauseSpeech();
            return;
        }
        if (speech.status === 'paused') {
            resumeSpeech();
            return;
        }
        prefs.readaloud = !prefs.readaloud;
        if (prefs.readaloud) {
            startReadAloud(null);
        } else {
            stopSpeech();
        }
        applyAll();
        persist();
    };

    var handleFeature = function(id) {
        if (id === 'readaloud') {
            if (!featureEnabled('readaloud')) {
                return;
            }
            handleReadAloudCard();
            return;
        }
        if (!featureEnabled(id)) {
            return;
        }
        if (id === 'contrast') {
            prefs.contrast = cycleValue(CONTRAST_CYCLE, prefs.contrast || '');
        } else if (id === 'textsize') {
            prefs.textsize = cycleValue(TEXT_CYCLE, prefs.textsize || 100);
        } else if (id === 'speechrate') {
            prefs.speechrate = cycleValue(SPEECH_CYCLE, prefs.speechrate || 100);
            if (speech.status === 'playing' || speech.status === 'paused') {
                startChunks(speech.queue.slice(speech.index));
            }
        } else if (BOOL_FEATURES.indexOf(id) !== -1) {
            prefs[id] = !prefs[id];
        }
        applyAll();
        persist();
    };

    var resetAll = function() {
        stopSpeech();
        prefs = defaultPrefs();
        applyAll();
        persist();
    };

    var focusable = function() {
        if (!panel) {
            return [];
        }
        return Array.prototype.slice.call(
            panel.querySelectorAll('button, a[href]')
        ).filter(function(el) {
            return !el.disabled && el.offsetParent !== null;
        });
    };

    var isOpen = function() {
        return panel && !panel.hasAttribute('hidden');
    };

    var openPanel = function() {
        if (!panel || !launcher) {
            return;
        }
        lastFocus = document.activeElement;
        panel.removeAttribute('hidden');
        launcher.setAttribute('aria-expanded', 'true');
        var closeBtn = panel.querySelector('[data-action="close"]');
        if (closeBtn) {
            closeBtn.focus();
        }
    };

    var closePanel = function() {
        if (!panel || !launcher) {
            return;
        }
        panel.setAttribute('hidden', 'hidden');
        launcher.setAttribute('aria-expanded', 'false');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        } else {
            launcher.focus();
        }
    };

    var togglePanel = function() {
        if (isOpen()) {
            closePanel();
        } else {
            openPanel();
        }
    };

    var onPointerMove = function(event) {
        pointerY = event.clientY;
        if (prefs.readingguide || prefs.readingmask) {
            positionOverlays(pointerY);
        }
    };

    var onFocusIn = function(event) {
        if (!prefs.readingguide && !prefs.readingmask) {
            return;
        }
        if (!event.target || event.target === document.body) {
            return;
        }
        if (event.target.closest && event.target.closest('#local-learninglens')) {
            return;
        }
        var rect = event.target.getBoundingClientRect();
        if (rect && rect.height) {
            positionOverlays(rect.top + rect.height / 2);
        }
    };

    var onDocumentKey = function(event) {
        if (event.altKey && event.shiftKey && event.code === 'KeyA') {
            event.preventDefault();
            togglePanel();
            return;
        }
        if (event.key === 'Escape' && speech.status !== 'idle') {
            stopSpeech();
            if (!isOpen()) {
                return;
            }
        }
        if (!isOpen()) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closePanel();
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        var items = focusable();
        if (!items.length) {
            return;
        }
        var first = items[0];
        var last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    var onPageClick = function(event) {
        if (!featureEnabled('readaloud') || !prefs.readaloud || !speech.supported) {
            return;
        }
        if (event.defaultPrevented) {
            return;
        }
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }
        if (target.closest('#local-learninglens')) {
            return;
        }
        if (target.closest(INTERACTIVE_SELECTOR)) {
            return;
        }
        var block = closestReadable(target);
        if (!block || isSkipped(block)) {
            return;
        }
        startReadAloud(block);
    };

    var bind = function() {
        launcher = root.querySelector('.local-learninglens-launcher');
        panel = root.querySelector('#local-learninglens-panel');
        if (launcher) {
            launcher.addEventListener('click', function(event) {
                event.preventDefault();
                togglePanel();
            });
        }
        root.addEventListener('click', function(event) {
            var action = event.target.closest('[data-action]');
            if (action && root.contains(action)) {
                var name = action.getAttribute('data-action');
                if (name === 'close') {
                    closePanel();
                } else if (name === 'reset') {
                    resetAll();
                }
                return;
            }
            var card = event.target.closest('[data-feature]');
            if (card && root.contains(card)) {
                handleFeature(card.getAttribute('data-feature'));
            }
        });
        document.addEventListener('keydown', onDocumentKey);
        document.addEventListener('mousemove', onPointerMove, {passive: true});
        document.addEventListener('focusin', onFocusIn);
        document.addEventListener('click', onPageClick);
        window.addEventListener('pagehide', stopSpeech);
        if (speech.supported) {
            try {
                window.speechSynthesis.getVoices();
            } catch (e) {
                // Ignore.
            }
        }
    };

    var init = function() {
        var attempts = 0;
        var start = function() {
            try {
                root = document.getElementById('local-learninglens');
                if (!root) {
                    attempts += 1;
                    if (attempts < 20) {
                        setTimeout(start, 50);
                    }
                    return;
                }
                if (root.getAttribute('data-learninglens-bound') === '1') {
                    return;
                }
                root.setAttribute('data-learninglens-bound', '1');
                cfg = window.local_learninglens_cfg || {};
                if (cfg.storagekey) {
                    STORAGE_KEY = cfg.storagekey;
                }
                var local = null;
                try {
                    local = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
                } catch (e) {
                    local = null;
                }
                if (!local) {
                    try {
                        local = JSON.parse(localStorage.getItem('local_a11ywidget') || 'null');
                    } catch (e) {
                        local = null;
                    }
                }
                var source = cfg.loggedin ? (cfg.prefs || local) : (local || cfg.prefs);
                prefs = mergePrefs(source);
                bind();
                applyAll();
            } catch (e) {
                // Never throw into theme JS.
            }
        };
        start();
    };

    return {
        init: init
    };
});

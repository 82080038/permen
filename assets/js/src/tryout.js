/**
 * Tryout Manager - SKD CAT-BKN Application
 * Handles question loading, timer, navigation, and answer submission
 */

class TryoutManager {
    constructor(config) {
        this.sessionId = config.sessionId;
        this.csrfToken = config.csrfToken;
        this.strictMode = config.strictMode;
        this.baseUrl = config.baseUrl;
        this.remainingSeconds = config.remainingSeconds;
        this.subtesTimers = config.subtesTimers;
        this.currentSubtes = config.currentSubtes;

        this.soal = [];
        this.passages = {};
        this.currentIdx = 0;
        this.answers = {};
        this.marked = {};
        this.bookmarked = {};
        this.totalSeconds = this.remainingSeconds;
        this.timerInterval = null;
        this.isPaused = false;
        this.LS_KEY = 'cat_answers_' + this.sessionId;

        this.subtesOrder = Object.keys(this.subtesTimers);
        this.subtesRemaining = {};
        this.activeSubtesIdx = this.subtesOrder.indexOf(this.currentSubtes);

        this.currentFilter = 'all';
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.SWIPE_THRESHOLD = 50;
        this.blurCount = 0;
        this.blurAlertShown = false;

        this.init();
    }

    init() {
        console.log('[TryoutManager] init called', {
            sessionId: this.sessionId,
            baseUrl: this.baseUrl,
            subtesTimers: this.subtesTimers,
            currentSubtes: this.currentSubtes
        });
        // Initialize per-subtes remaining time
        this.subtesOrder.forEach(sub => {
            this.subtesRemaining[sub] = this.subtesTimers[sub]?.remaining || this.subtesTimers[sub]?.durasi * 60 || 1800;
        });

        this.loadSoal();
        this.bindEvents();
    }

    bindEvents() {
        // Anti-cheating: Disable right-click
        document.addEventListener('contextmenu', e => e.preventDefault());

        // Disable copy
        document.addEventListener('copy', e => {
            if (e.target.closest('.passage-bacaan, .question')) e.preventDefault();
        });

        // Disable cut
        document.addEventListener('cut', e => {
            if (e.target.closest('.passage-bacaan, .question')) e.preventDefault();
        });

        // Detect window blur
        window.addEventListener('blur', () => {
            this.blurCount++;
            if (this.blurCount >= 3 && !this.blurAlertShown) {
                this.blurAlertShown = true;
                alert('Peringatan: Anda telah meninggalkan halaman tryout terlalu sering. Integritas tes akan dievaluasi.');
            }
        });

        // Prevent back button navigation
        document.addEventListener('keydown', e => {
            if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                e.preventDefault();
                alert('Refresh tidak diizinkan selama tryout.');
            }
            if (e.ctrlKey && e.key === 'u') e.preventDefault();
            if (e.ctrlKey && e.shiftKey && e.key === 'I') e.preventDefault();
            if (e.key === 'F12') e.preventDefault();
        });

        history.pushState(null, '', location.href);
        window.addEventListener('popstate', () => {
            history.pushState(null, '', location.href);
            alert('Navigasi back tidak diizinkan selama tryout.');
        });

        // Swipe navigation
        document.addEventListener('touchstart', e => {
            this.touchStartX = e.changedTouches[0].screenX;
            this.touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        document.addEventListener('touchend', e => {
            if (!this.soal.length) return;
            const touchEndX = e.changedTouches[0].screenX;
            const touchEndY = e.changedTouches[0].screenY;
            const dx = touchEndX - this.touchStartX;
            const dy = touchEndY - this.touchStartY;

            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > this.SWIPE_THRESHOLD) {
                if (dx < 0) this.nextSoal();
                else this.prevSoal();
            }
        }, { passive: true });

        // Keyboard shortcuts
        document.addEventListener('keydown', e => {
            if (!this.soal.length) return;
            const key = e.key.toUpperCase();

            if (['A', 'B', 'C', 'D', 'E'].includes(key)) {
                const radios = document.querySelectorAll('input[name="jawaban"]');
                radios.forEach(r => {
                    if (r.value === key) {
                        r.checked = true;
                        r.dispatchEvent(new Event('change'));
                    }
                });
            }

            if (key === 'ARROWLEFT' || key === 'ARROWUP') this.prevSoal();
            if (key === 'ARROWRIGHT' || key === 'ARROWDOWN') this.nextSoal();
            if (key === 'M') this.toggleMark();
        });
    }

    async loadSoal() {
        console.log('[TryoutManager] loadSoal called with sessionId:', this.sessionId, 'baseUrl:', this.baseUrl);
        try {
            const res = await fetch(`${this.baseUrl}/api/get_soal.php?session_id=${this.sessionId}`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (res.status === 401 || res.status === 403) {
                alert('Sesi Anda telah berakhir. Silakan login kembali.');
                window.location.href = `${this.baseUrl}/pages/login.php`;
                return;
            }

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }

            const text = await res.text();

            if (text.trim().startsWith('<') || text.trim().startsWith('<!DOCTYPE')) {
                throw new Error('Server returned error page instead of JSON');
            }

            const data = JSON.parse(text);
            if (data.error) {
                if (data.error.includes('Session sudah selesai') || data.error.includes('tidak aktif')) {
                    alert('Sesi tryout Anda telah berakhir atau tidak aktif. Anda akan diarahkan ke halaman hasil.');
                    window.location.href = `${this.baseUrl}/pages/hasil.php?session_id=${this.sessionId}`;
                    return;
                }
                alert(data.error);
                return;
            }

            const responseData = data.data || data;
            this.soal = responseData.soal;
            this.passages = responseData.passages || {};
            console.log('Loaded', this.soal.length, 'questions');
            console.log('First question:', this.soal[0]);

            if (this.soal.length > 0) {
                this.currentSubtes = this.soal[0].subtes;
                this.activeSubtesIdx = this.subtesOrder.indexOf(this.currentSubtes);
                console.log('Set currentSubtes to:', this.currentSubtes);
            }

            this.restoreLocalAnswers();

            const loadingIndicator = document.getElementById('loadingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }

            this.renderNumberGrid();
            this.renderSoal(0);
            this.startTimer();

            if (this.strictMode) {
                document.getElementById('strictModeIndicator').style.display = 'block';
                document.getElementById('btnPrev').disabled = true;
                document.getElementById('btnPrev').style.opacity = '0.5';
                document.getElementById('btnPrev').style.cursor = 'not-allowed';
            }
        } catch (e) {
            alert('Gagal memuat soal: ' + e.message + '. Silakan refresh halaman atau periksa koneksi internet Anda.');
        }
    }

    startTimer() {
        let warningShown = false;
        this.timerInterval = setInterval(() => {
            if (this.isPaused) return;

            if (this.currentSubtes && this.subtesRemaining[this.currentSubtes] > 0) {
                this.subtesRemaining[this.currentSubtes]--;
            }
            this.totalSeconds--;

            if (!warningShown && this.totalSeconds === 300) {
                warningShown = true;
                alert('PERINGATAN: Sesi Anda akan berakhir dalam 5 menit. Jawaban Anda akan otomatis disimpan.');
                this.saveLocalAnswers();
            }

            if (this.totalSeconds <= 0) {
                clearInterval(this.timerInterval);
                this.finishTryout();
                return;
            }

            if (this.currentSubtes && this.subtesRemaining[this.currentSubtes] <= 0) {
                const nextIdx = this.activeSubtesIdx + 1;
                if (nextIdx < this.subtesOrder.length) {
                    alert('Waktu subtes ' + this.currentSubtes + ' habis! Anda akan dipindahkan ke subtes berikutnya.');
                    this.advanceToNextSubtes();
                } else {
                    clearInterval(this.timerInterval);
                    this.finishTryout();
                }
                return;
            }

            const m = Math.floor(this.totalSeconds / 60).toString().padStart(2, '0');
            const s = (this.totalSeconds % 60).toString().padStart(2, '0');
            document.getElementById('timer').textContent = m + ':' + s;
        }, 1000);
    }

    renderNumberGrid() {
        const grid = document.getElementById('numberGrid');
        grid.innerHTML = '';
        let answeredCount = 0, markedCount = 0;

        this.soal.forEach((s, i) => {
            if (this.currentFilter === 'ragu' && !this.marked[s.answer_id]) return;
            if (this.currentFilter === 'unanswered' && this.answers[s.answer_id]) return;

            const btn = document.createElement('button');
            btn.textContent = i + 1;
            btn.onclick = () => this.renderSoal(i);
            if (i === this.currentIdx) btn.classList.add('active');
            if (this.answers[s.answer_id]) {
                btn.classList.add('answered');
                answeredCount++;
            }
            if (this.marked[s.answer_id]) {
                btn.classList.add('marked');
                markedCount++;
            }
            grid.appendChild(btn);
        });

        const status = document.getElementById('navStatus');
        if (status) {
            const total = this.soal.length;
            const text = '<strong style="color:#27ae60">' + answeredCount + '</strong> dijawab, '
                + '<strong style="color:#999">' + (total - answeredCount) + '</strong> belum'
                + (markedCount > 0 ? ' (<strong style="color:#f39c12">' + markedCount + '</strong> ragu)' : '')
                + (this.currentFilter !== 'all' ? ' <span style="color:#e74c3c">[Filter: ' + this.currentFilter + ']</span>' : '');
            status.innerHTML = text;
        }

        const activeBtn = grid.querySelector('button.active');
        if (activeBtn) activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }

    advanceToNextSubtes() {
        const nextIdx = this.activeSubtesIdx + 1;
        if (nextIdx >= this.subtesOrder.length) {
            this.finishTryout();
            return;
        }
        const nextSub = this.subtesOrder[nextIdx];
        const currentSub = this.subtesOrder[this.activeSubtesIdx];

        fetch(`${this.baseUrl}/api/next_subtes.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            },
            body: JSON.stringify({ session_id: this.sessionId, current_subtes: currentSub, next_subtes: nextSub })
        });

        this.currentSubtes = nextSub;
        this.activeSubtesIdx = nextIdx;

        const firstIdx = this.soal.findIndex(q => q.subtes === nextSub);
        if (firstIdx >= 0) {
            this.renderSoal(firstIdx);
        }
    }

    renderSoal(idx) {
        console.log('renderSoal called with idx:', idx);
        this.currentIdx = idx;
        const s = this.soal[idx];
        console.log('Rendering question', idx, s);

        if (s.subtes !== this.currentSubtes) {
            console.log('Subtes change detected:', this.currentSubtes, '->', s.subtes);
            const prevSubIdx = this.subtesOrder.indexOf(this.currentSubtes);
            const newSubIdx = this.subtesOrder.indexOf(s.subtes);

            if (newSubIdx > prevSubIdx) {
                const currentSubSoal = this.soal.filter(q => q.subtes === this.currentSubtes);
                const unanswered = currentSubSoal.filter(q => !this.answers[q.answer_id]).length;
                const msg = 'Anda akan pindah ke subtes ' + s.subtes + '.\n'
                    + 'Soal ' + this.currentSubtes + ' yang belum dijawab: ' + unanswered + '\n'
                    + 'Waktu ' + this.currentSubtes + ' yang tersisa tidak bisa digunakan untuk subtes lain.\n\n'
                    + 'Yakin ingin lanjut?';
                if (!confirm(msg)) {
                    const lastIdx = this.soal.map(q => q.subtes).lastIndexOf(this.currentSubtes);
                    if (lastIdx >= 0) this.currentIdx = lastIdx;
                    console.log('User cancelled subtes change, returning');
                    return;
                }

                fetch(`${this.baseUrl}/api/next_subtes.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': this.csrfToken
                    },
                    body: JSON.stringify({ session_id: this.sessionId, current_subtes: this.currentSubtes, next_subtes: s.subtes })
                });

                this.currentSubtes = s.subtes;
                this.activeSubtesIdx = newSubIdx;
            }
        }

        console.log('Setting subtes-info');
        document.getElementById('subtes-info').textContent = s.subtes + ' — Soal ' + (idx + 1) + ' dari ' + this.soal.length;

        const passageBox = document.getElementById('passageBox');
        const passageJudul = document.getElementById('passageJudul');
        const passageBacaan = document.getElementById('passageBacaan');

        if (s.passage_id && this.passages[s.passage_id]) {
            const p = this.passages[s.passage_id];
            const totalInPassage = this.soal.filter(q => q.passage_id == s.passage_id).length;
            const orderInPassage = this.soal.filter((q, i) => q.passage_id == s.passage_id && i <= idx).length;

            passageBox.style.display = 'block';
            passageJudul.textContent = p.judul ? this.escapeHtml(p.judul) : 'Bacaan';
            passageBacaan.innerHTML = '<div class="passage-info">Soal ' + orderInPassage + ' dari ' + totalInPassage + ' dalam bacaan ini</div>' + this.escapeHtml(p.bacaan);
        } else {
            passageBox.style.display = 'none';
            passageJudul.textContent = '';
            passageBacaan.innerHTML = '';
        }

        const qText = this.escapeHtml(s.pertanyaan);
        const scrollClass = (s.pertanyaan.length > 300) ? 'question-scrollable' : '';
        let html = '<div class="question ' + scrollClass + '"><strong>' + (idx + 1) + '.</strong> ' + qText + '</div>';

        if (s.image_url) {
            html += '<img src="' + this.escapeHtml(s.image_url) + '" class="question-image" alt="Gambar soal" loading="lazy" onerror="this.style.display=\'none\'" onclick="openZoom(this.src)" style="cursor:zoom-in">';
        }

        html += '<div class="options">';
        ['A', 'B', 'C', 'D', 'E'].forEach(opt => {
            const selected = this.answers[s.answer_id] === opt ? 'selected' : '';
            html += '<label class="' + selected + '"><input type="radio" name="jawaban" value="' + opt + '" ' + (selected ? 'checked' : '') + ' onchange="window.tryoutManager.pilihJawaban(' + s.answer_id + ',\'' + opt + '\',this)"> ' + opt + '. ' + this.escapeHtml(s['pilihan_' + opt.toLowerCase()]) + '</label>';
        });
        html += '</div>';
        html += '<div class="pembahasan" id="pembahasanBox" style="display:none">' + this.escapeHtml(s.pembahasan) + '</div>';

        console.log('Setting innerHTML for soalContainer');
        document.getElementById('soalContainer').innerHTML = html;
        console.log('innerHTML set successfully');
        this.renderNumberGrid();

        const soalEl = document.getElementById('soalContainer');
        const rect = soalEl.getBoundingClientRect();
        if (rect.top < 0 || rect.top > window.innerHeight) {
            soalEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    pilihJawaban(answerId, opt, el) {
        this.answers[answerId] = opt;
        document.querySelectorAll('.options label').forEach(l => l.classList.remove('selected'));
        el.closest('label').classList.add('selected');
        this.renderNumberGrid();
        this.saveLocalAnswers();

        fetch(`${this.baseUrl}/api/submit_jawaban.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            },
            body: JSON.stringify({ answer_id: answerId, jawaban: opt, is_ragu: this.marked[answerId] ? 1 : 0 })
        }).then(r => {
            if (r.status === 401 || r.status === 403) {
                alert('Sesi Anda telah berakhir. Silakan login kembali.');
                window.location.href = `${this.baseUrl}/pages/login.php`;
            }
        }).catch(e => {
            console.error('Error submitting answer:', e);
        });

        setTimeout(() => {
            const s = this.soal[this.currentIdx];
            if (this.marked[s.answer_id]) return;

            if (this.currentIdx >= this.soal.length - 1) {
                const answeredCount = Object.keys(this.answers).length;
                const totalCount = this.soal.length;
                const raguCount = Object.values(this.marked).filter(Boolean).length;
                let msg = 'Selamat! Anda telah menjawab soal terakhir.\n\n';
                msg += 'Soal dijawab: ' + answeredCount + ' / ' + totalCount + '\n';
                if (raguCount > 0) msg += 'Ragu-ragu: ' + raguCount + '\n\n';
                msg += 'Klik OK untuk melihat hasil tryout.';
                alert(msg);
                this.finishTryout();
                return;
            }

            const nextIdx = this.currentIdx + 1;
            const currentSub = this.soal[this.currentIdx].subtes;
            const nextSub = this.soal[nextIdx].subtes;

            if (currentSub !== nextSub) {
                const currentSubSoal = this.soal.filter(q => q.subtes === currentSub);
                const unanswered = currentSubSoal.filter(q => !this.answers[q.answer_id]).length;
                const msg = 'Anda akan pindah ke subtes ' + nextSub + '.\n'
                    + 'Soal ' + currentSub + ' yang belum dijawab: ' + unanswered + '\n'
                    + 'Waktu ' + currentSub + ' yang tersisa tidak bisa digunakan untuk subtes lain.\n\n'
                    + 'Yakin ingin lanjut?';
                if (!confirm(msg)) return;

                fetch(`${this.baseUrl}/api/next_subtes.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': this.csrfToken
                    },
                    body: JSON.stringify({ session_id: this.sessionId, current_subtes: currentSub, next_subtes: nextSub })
                });

                this.currentSubtes = nextSub;
                this.activeSubtesIdx = this.subtesOrder.indexOf(nextSub);
            }

            this.renderSoal(nextIdx);
        }, 400);
    }

    prevSoal() {
        if (this.strictMode) {
            alert('Strict Mode aktif: Anda tidak bisa kembali ke soal sebelumnya.');
            return;
        }
        if (this.currentIdx > 0) this.renderSoal(this.currentIdx - 1);
    }

    nextSoal() {
        if (this.currentIdx >= this.soal.length - 1) return;
        const nextIdx = this.currentIdx + 1;
        const currentSub = this.soal[this.currentIdx].subtes;
        const nextSub = this.soal[nextIdx].subtes;

        if (currentSub !== nextSub) {
            const currentSubSoal = this.soal.filter(q => q.subtes === currentSub);
            const unanswered = currentSubSoal.filter(q => !this.answers[q.answer_id]).length;
            const msg = 'Anda akan pindah ke subtes ' + nextSub + '.\n'
                + 'Soal ' + currentSub + ' yang belum dijawab: ' + unanswered + '\n'
                + 'Waktu ' + currentSub + ' yang tersisa tidak bisa digunakan untuk subtes lain.\n\n'
                + 'Yakin ingin lanjut?';
            if (!confirm(msg)) return;

            fetch(`${this.baseUrl}/api/next_subtes.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ session_id: this.sessionId, current_subtes: currentSub, next_subtes: nextSub })
            });

            this.currentSubtes = nextSub;
            this.activeSubtesIdx = this.subtesOrder.indexOf(nextSub);
        }

        this.renderSoal(nextIdx);
    }

    saveLocalAnswers() {
        try {
            const data = JSON.stringify({ answers: this.answers, marked: this.marked, savedAt: Date.now() });
            localStorage.setItem(this.LS_KEY, data);
        } catch (e) {
            if (e.name === 'QuotaExceededError') {
                console.error('LocalStorage quota exceeded. Attempting to clear old data...');
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && key.startsWith('cat_answers_') && key !== this.LS_KEY) {
                        localStorage.removeItem(key);
                        console.log('Cleared old session:', key);
                        try {
                            const data = JSON.stringify({ answers: this.answers, marked: this.marked, savedAt: Date.now() });
                            localStorage.setItem(this.LS_KEY, data);
                            console.log('Successfully saved after clearing old data');
                            return;
                        } catch (retryError) {
                            console.error('Still cannot save after clearing old data');
                        }
                    }
                }
                alert('Peringatan: Penyimpanan browser penuh. Jawaban Anda tetap disimpan ke server, tapi tidak dapat disimpan secara lokal untuk recovery.');
            } else {
                console.error('Error saving to localStorage:', e);
            }
        }
    }

    restoreLocalAnswers() {
        const saved = localStorage.getItem(this.LS_KEY);
        if (saved) {
            try {
                const data = JSON.parse(saved);
                if (data.answers) Object.assign(this.answers, data.answers);
                if (data.marked) Object.assign(this.marked, data.marked);
            } catch (e) {
                console.error('Error restoring from localStorage:', e);
            }
        }
    }

    clearLocalAnswers() {
        try {
            localStorage.removeItem(this.LS_KEY);
        } catch (e) {
            console.error('Error clearing localStorage:', e);
        }
    }

    toggleMark() {
        const s = this.soal[this.currentIdx];
        this.marked[s.answer_id] = !this.marked[s.answer_id];
        this.saveLocalAnswers();
        this.renderNumberGrid();

        const btn = document.getElementById('btnMark');
        if (this.marked[s.answer_id]) {
            btn.textContent = 'Ragu ✓';
            btn.title = 'Auto-advance dinonaktifkan untuk soal ini';
        } else {
            btn.textContent = 'Ragu (M)';
            btn.title = 'Tandai ragu-ragu';
        }

        fetch(`${this.baseUrl}/api/mark_revision.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            },
            body: JSON.stringify({ question_id: s.question_id, needs_revision: this.marked[s.answer_id] ? 1 : 0 })
        });
    }

    filterRagu() {
        this.currentFilter = 'ragu';
        this.renderNumberGrid();
    }

    filterUnanswered() {
        this.currentFilter = 'unanswered';
        this.renderNumberGrid();
    }

    showAll() {
        this.currentFilter = 'all';
        this.renderNumberGrid();
    }

    async togglePause() {
        const btn = document.getElementById('btnPause');
        const indicator = document.getElementById('pauseIndicator');

        if (!this.isPaused) {
            try {
                const res = await fetch(`${this.baseUrl}/api/pause_tryout.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': this.csrfToken
                    },
                    body: JSON.stringify({ session_id: this.sessionId })
                });

                if (res.status === 401 || res.status === 403) {
                    alert('Sesi Anda telah berakhir. Silakan login kembali.');
                    window.location.href = `${this.baseUrl}/pages/login.php`;
                    return;
                }

                const data = await res.json();
                if (data.success) {
                    this.isPaused = true;
                    btn.textContent = '▶ Resume';
                    btn.style.background = '#27ae60';
                    indicator.style.display = 'block';
                    alert('Tryout dipause. Timer dihentikan sementara. Klik Resume untuk melanjutkan.');
                } else {
                    alert('Gagal pause: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Error pausing tryout:', e);
                alert('Gagal pause tryout. Silakan coba lagi.');
            }
        } else {
            try {
                const res = await fetch(`${this.baseUrl}/api/resume_tryout.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': this.csrfToken
                    },
                    body: JSON.stringify({ session_id: this.sessionId })
                });

                if (res.status === 401 || res.status === 403) {
                    alert('Sesi Anda telah berakhir. Silakan login kembali.');
                    window.location.href = `${this.baseUrl}/pages/login.php`;
                    return;
                }

                const data = await res.json();
                if (data.success) {
                    this.isPaused = false;
                    btn.textContent = '⏸ Pause';
                    btn.style.background = '#e67e22';
                    indicator.style.display = 'none';
                    this.totalSeconds += data.pause_duration;
                    alert('Tryout dilanjutkan. Timer disesuaikan dengan durasi pause.');
                } else {
                    alert('Gagal resume: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Error resuming tryout:', e);
                alert('Gagal resume tryout. Silakan coba lagi.');
            }
        }
    }

    async toggleBookmark() {
        const s = this.soal[this.currentIdx];
        if (!s || !s.question_id) {
            this.showToast('Soal tidak valid', 'error');
            return;
        }
        const isBookmarked = this.bookmarked[s.question_id];
        const action = isBookmarked ? 'remove' : 'add';

        const formData = new FormData();
        formData.append('question_id', s.question_id);
        formData.append('action', action);

        try {
            const res = await fetch(`${this.baseUrl}/api/bookmark_question.php`, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                },
                body: formData
            });

            if (res.status === 401 || res.status === 403) {
                this.showToast('Sesi telah berakhir. Silakan login kembali.', 'error');
                setTimeout(() => window.location.href = `${this.baseUrl}/pages/login.php`, 2000);
                return;
            }

            const data = await res.json();

            if (data.success) {
                this.bookmarked[s.question_id] = !isBookmarked;
                const btn = document.getElementById('btnBookmark');
                if (btn) {
                    btn.style.background = this.bookmarked[s.question_id] ? '#f39c12' : '#9b59b6';
                    btn.textContent = this.bookmarked[s.question_id] ? '⭐ Tersimpan' : '⭐ Favorit';
                }
                this.showToast(this.bookmarked[s.question_id] ? 'Soal disimpan ke favorit' : 'Soal dihapus dari favorit', 'success');
            } else {
                this.showToast(data.error || 'Gagal menyimpan favorit', 'error');
            }
        } catch (e) {
            this.showToast('Gagal menyimpan favorit', 'error');
        }
    }

    showToast(message, type = 'info') {
        // Simple toast implementation
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            background: ${type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db'};
            color: white;
            border-radius: 4px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    finishTryout() {
        const answeredCount = Object.keys(this.answers).length;
        const totalCount = this.soal.length;
        const msg = 'Yakin ingin menyelesaikan try out?\n\n'
            + 'Soal dijawab: ' + answeredCount + ' / ' + totalCount + '\n'
            + 'Ragu-ragu: ' + Object.values(this.marked).filter(Boolean).length;
        if (!confirm(msg)) return;

        clearInterval(this.timerInterval);
        this.clearLocalAnswers();

        fetch(`${this.baseUrl}/api/finish_tryout.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            },
            body: JSON.stringify({ session_id: this.sessionId })
        }).then(r => r.json()).then(data => {
            if (data.success) window.location.href = `${this.baseUrl}/pages/hasil.php?session_id=${this.sessionId}`;
            else alert(data.error);
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export TryoutManager to global scope
window.TryoutManager = TryoutManager;

// Theme and accessibility functions
const savedTheme = localStorage.getItem('cat_theme') || 'light';
if (savedTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');

window.toggleTheme = function () {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('cat_theme', next);
};

// Font size functions
const fontSizes = ['small', 'medium', 'large'];
let fontIdx = 1; // medium default
const savedFont = localStorage.getItem('cat_font');
if (savedFont) {
    const idx = fontSizes.indexOf(savedFont);
    if (idx >= 0) {
        fontIdx = idx;
        document.documentElement.setAttribute('data-font-size', savedFont);
    }
}

window.cycleFontSize = function () {
    fontIdx = (fontIdx + 1) % fontSizes.length;
    const size = fontSizes[fontIdx];
    document.documentElement.setAttribute('data-font-size', size);
    localStorage.setItem('cat_font', size);
};

// Global functions for inline event handlers
window.filterRagu = function () {
    if (window.tryoutManager) window.tryoutManager.filterRagu();
};

window.filterUnanswered = function () {
    if (window.tryoutManager) window.tryoutManager.filterUnanswered();
};

window.showAll = function () {
    if (window.tryoutManager) window.tryoutManager.showAll();
};

window.togglePause = function () {
    if (window.tryoutManager) window.tryoutManager.togglePause();
};

window.toggleMark = function () {
    if (window.tryoutManager) window.tryoutManager.toggleMark();
};

window.toggleBookmark = function () {
    if (window.tryoutManager) window.tryoutManager.toggleBookmark();
};

window.prevSoal = function () {
    if (window.tryoutManager) window.tryoutManager.prevSoal();
};

window.nextSoal = function () {
    if (window.tryoutManager) window.tryoutManager.nextSoal();
};

window.finishTryout = function () {
    if (window.tryoutManager) window.tryoutManager.finishTryout();
};

window.openZoom = function (src) {
    const modal = document.getElementById('imgZoomModal');
    const img = document.getElementById('zoomImg');
    if (modal && img) {
        img.src = src;
        modal.style.display = 'flex';
    }
};

window.closeZoom = function () {
    const modal = document.getElementById('imgZoomModal');
    if (modal) modal.style.display = 'none';
};

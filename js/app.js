document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // SHARED UTILITIES
    // ==========================================
    const WASTE_TYPE_LABELS = {
        'general': 'ขยะทั่วไป',
        'recyclable': 'ขยะรีไซเคิล',
        'organic': 'ขยะอินทรีย์',
        'hazardous': 'ขยะอันตราย'
    };

    const STATUS_LABELS = {
        'new': 'ใหม่',
        'in_progress': 'ดำเนินการ',
        'done': 'เสร็จสิ้น'
    };

    function formatNumber(num) {
        if (num === null || num === undefined) return '-';

        return Number(num).toLocaleString('th-TH', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        });
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '';

        return new Date(dateStr).toLocaleString('th-TH', {
            day: 'numeric',
            month: 'short',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // ==========================================
    // STATS PAGE LOGIC
    // ==========================================
    const statsLoading = document.getElementById('loading-state');

    if (statsLoading) initStatsPage();

    async function initStatsPage() {
        const els = {
            loading: statsLoading,
            error: document.getElementById('error-state'),
            content: document.getElementById('content-wrapper'),
            generated: document.getElementById('bs-generated'),
            proper: document.getElementById('bs-proper'),
            rate: document.getElementById('bs-rate'),
            topBody: document.getElementById('top-list-body'),
            yearSelect: document.getElementById('year-select')
        };

        // ฟังก์ชันโหลดข้อมูล
        async function loadData(selectedYear = null) {
            try {
                // แก้ URL ให้ตรงกับโฟลเดอร์ BinGo
                const url = selectedYear
                    ? `/BinGo/api/stats.php?year=${selectedYear}`
                    : '/BinGo/api/stats.php';

                const res = await fetch(url);

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                const data = await res.json();

                // อัปเดต Dropdown ปี
                if (
                    data.available_years &&
                    els.yearSelect &&
                    els.yearSelect.options.length <= 1
                ) {
                    els.yearSelect.innerHTML = '';

                    data.available_years.forEach(y => {
                        const opt = document.createElement('option');

                        opt.value = y;
                        opt.textContent = `พ.ศ. ${y}`;

                        if (parseInt(y) === data.year) {
                            opt.selected = true;
                        }

                        els.yearSelect.appendChild(opt);
                    });
                }

                // Render ข้อมูล
                renderStats(data);

                // ซ่อน Loading
                els.loading.classList.add('hidden');
                els.content.classList.remove('hidden');

            } catch (err) {
                console.error(err);

                els.loading.classList.add('hidden');
                els.error.textContent =
                    'ไม่สามารถโหลดข้อมูลสถิติได้';

                els.error.classList.remove('hidden');
            }
        }

        // Event Listener เมื่อเปลี่ยนปี
        if (els.yearSelect) {
            els.yearSelect.addEventListener('change', (e) => {
                const val = e.target.value;

                els.content.style.opacity = '0.5';

                loadData(val).finally(() => {
                    els.content.style.opacity = '1';
                });
            });
        }

        // โหลดครั้งแรก ใช้ปีล่าสุด
        loadData();
    }

    function renderStats(data) {
        const els = {
            generated: document.getElementById('bs-generated'),
            proper: document.getElementById('bs-proper'),
            rate: document.getElementById('bs-rate'),
            gaugeFill: document.getElementById('gauge-fill'),
            topBody: document.getElementById('top-list-body')
        };

        // Render KPI
        const GAUGE_CIRCUMFERENCE = 2 * Math.PI * 92; // r=92, matches SVG

        if (data.bangsaen) {
            els.generated.textContent =
                formatNumber(data.bangsaen.generated_tpd);

            els.proper.textContent =
                formatNumber(data.bangsaen.proper_tpd);

            const rate = data.bangsaen.proper_rate;

            els.rate.textContent =
                rate !== null ? rate.toFixed(1) : '-';

            const clampedRate = Math.max(0, Math.min(100, rate || 0));

            els.gaugeFill.style.strokeDasharray = `${GAUGE_CIRCUMFERENCE}`;
            els.gaugeFill.style.strokeDashoffset =
                `${GAUGE_CIRCUMFERENCE - (GAUGE_CIRCUMFERENCE * clampedRate) / 100}`;

        } else {
            els.generated.textContent = '-';
            els.proper.textContent = '-';
            els.rate.textContent = '-';

            els.gaugeFill.style.strokeDasharray = `${GAUGE_CIRCUMFERENCE}`;
            els.gaugeFill.style.strokeDashoffset = `${GAUGE_CIRCUMFERENCE}`;
        }

        // Render Ranking List
        els.topBody.innerHTML = '';

        if (!data.top || data.top.length === 0) {
            els.topBody.innerHTML =
                '<div class="empty-state compact">ไม่มีข้อมูลสถิติในปีนี้</div>';

        } else {
            const maxVal = Math.max(
                ...data.top.map(i => i.generated_tpd)
            );

            data.top.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'ranking-item';

                // Rank Number
                const rankNum = document.createElement('div');
                rankNum.className = 'rank-number';
                rankNum.textContent = index + 1;

                // Info (Name + Bar)
                const info = document.createElement('div');
                info.className = 'rank-info';

                const name = document.createElement('div');
                name.className = 'rank-name';
                name.textContent = item.local_gov;

                const barBg = document.createElement('div');
                barBg.className = 'rank-bar-bg';

                const barFill = document.createElement('div');
                barFill.className = 'rank-bar-fill';

                barFill.style.width = maxVal > 0
                    ? `${(item.generated_tpd / maxVal) * 100}%`
                    : '0%';

                barBg.appendChild(barFill);

                info.appendChild(name);
                info.appendChild(barBg);

                // Value
                const val = document.createElement('div');
                val.className = 'rank-value';
                val.textContent = formatNumber(item.generated_tpd);

                div.appendChild(rankNum);
                div.appendChild(info);
                div.appendChild(val);

                els.topBody.appendChild(div);
            });
        }
    }

    // ==========================================
    // REPORT PAGE LOGIC
    // ==========================================
    const reportForm = document.getElementById('report-form');

    if (reportForm) initReportPage();

    function initReportPage() {
        const els = {
            form: reportForm,
            submitBtn: document.getElementById('submit-btn'),
            successMsg: document.getElementById('form-success'),
            errorMsg: document.getElementById('form-error'),
            list: document.getElementById('reports-list'),
            loading: document.getElementById('reports-loading'),
            empty: document.getElementById('reports-empty'),
            filter: document.getElementById('filter-type')
        };

        loadReports(els);

        els.filter.addEventListener('change', () => {
            els.list.innerHTML = '';

            els.list.classList.add('hidden');
            els.empty.classList.add('hidden');

            els.loading.querySelector('span').textContent =
                'กำลังกรอง...';

            els.loading.classList.remove('hidden');

            loadReports(els);
        });

        els.form.addEventListener('submit', async (e) => {
            e.preventDefault();

            els.successMsg.classList.add('hidden');
            els.errorMsg.classList.add('hidden');

            const payload = {
                location: document.getElementById('location').value,
                waste_type: document.getElementById('waste_type').value,
                amount_kg: document.getElementById('amount_kg').value,
                detail: document.getElementById('detail').value
            };

            try {
                els.submitBtn.disabled = true;
                els.submitBtn.textContent = 'กำลังส่ง...';

                // แก้ URL ให้ตรงกับโฟลเดอร์ BinGo
                const res = await fetch('/BinGo/api/report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.status === 201) {
                    els.form.reset();

                    els.successMsg.textContent =
                        `บันทึกแล้ว #${data.id}`;

                    els.successMsg.classList.remove('hidden');

                    const currentFilter = els.filter.value;

                    if (
                        currentFilter === '' ||
                        currentFilter === data.waste_type
                    ) {
                        prependCard(data, els.list);

                        els.empty.classList.add('hidden');
                        els.list.classList.remove('hidden');
                    }

                } else {
                    els.errorMsg.textContent =
                        data.error || 'เกิดข้อผิดพลาด';

                    els.errorMsg.classList.remove('hidden');
                }

            } catch (err) {
                console.error(err);

                els.errorMsg.textContent =
                    'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';

                els.errorMsg.classList.remove('hidden');

            } finally {
                els.submitBtn.disabled = false;
                els.submitBtn.textContent = 'ส่งข้อมูล';
            }
        });
    }

    // ==========================================
    // LOAD REPORTS
    // ==========================================
    async function loadReports(els) {
        try {
            const type = els.filter?.value || '';

            // แก้ URL ให้ตรงกับโฟลเดอร์ BinGo
            const url = type
                ? `/BinGo/api/report.php?type=${encodeURIComponent(type)}`
                : '/BinGo/api/report.php';

            const res = await fetch(url);

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));

                throw new Error(err.error || 'Failed');
            }

            const reports = await res.json();

            els.loading.classList.add('hidden');

            if (reports.length === 0) {
                els.empty.querySelector('p').textContent =
                    type
                        ? 'ไม่พบรายการประเภทนี้'
                        : 'ยังไม่มีรายการแจ้งขยะ';

                els.empty.classList.remove('hidden');

            } else {
                els.list.classList.remove('hidden');

                reports.forEach(r => {
                    prependCard(r, els.list);
                });
            }

        } catch (err) {
            console.error(err);

            els.loading.querySelector('span').textContent =
                err.message || 'โหลดไม่สำเร็จ';
        }
    }

    // ==========================================
    // CREATE REPORT CARD
    // ==========================================
    function prependCard(report, container) {
        const card = document.createElement('div');
        card.className = 'report-item';

        // Header
        const header = document.createElement('div');
        header.className = 'report-header';

        const loc = document.createElement('strong');
        loc.className = 'report-loc';
        loc.textContent = report.location;

        const badge = document.createElement('span');
        badge.className = `badge badge-${report.status}`;

        badge.textContent =
            STATUS_LABELS[report.status] || report.status;

        header.appendChild(loc);
        header.appendChild(badge);

        // Meta
        const meta = document.createElement('div');
        meta.className = 'report-meta';

        const typeSpan = document.createElement('span');

        typeSpan.textContent =
            WASTE_TYPE_LABELS[report.waste_type] ||
            report.waste_type;

        const amtSpan = document.createElement('span');
        amtSpan.className = 'report-amount';
        amtSpan.textContent = `${report.amount_kg} กก.`;

        meta.appendChild(typeSpan);
        meta.appendChild(amtSpan);

        card.appendChild(header);
        card.appendChild(meta);

        // Detail
        if (report.detail) {
            const detail = document.createElement('p');

            detail.className = 'report-detail';
            detail.textContent = report.detail;

            card.appendChild(detail);
        }

        // Time
        const time = document.createElement('div');

        time.className = 'report-time';
        time.textContent = formatDateTime(report.created_at);

        card.appendChild(time);

        container.prepend(card);
    }
});
/* ChezGigi - Calendrier */

const SOURCE_COLORS = {
    AIRBNB:  '#FF5A5F',
    BOOKING: '#003580',
    MANUAL:  '#10B981',
    MENAGE:  'rgb(195, 198, 203)',
};
const MENAGE_COLOR  = 'rgb(195, 198, 203)';
const BLOCKED_COLOR = '#D1D5DB';

let reservations = [];
let calendar     = null;
let currentMonth = new Date();

document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
    loadReservations();

    document.getElementById('btn-sync').addEventListener('click', syncIcal);
    document.getElementById('btn-cancel').addEventListener('click', closeModal);
    document.getElementById('btn-save').addEventListener('click', saveReservation);
    document.getElementById('btn-delete').addEventListener('click', deleteReservation);

    document.getElementById('modal').addEventListener('click', (e) => {
        if (e.target.id === 'modal') closeModal();
    });
});

function initCalendar() {
    const el = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(el, {
        plugins: [],
        initialView: 'dayGridMonth',
        locale: 'fr',
        firstDay: 1,
        timeZone: 'local',
        displayEventTime: false,
        nextDayThreshold: '00:00:00',
        eventDisplay: 'block',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        buttonText: { today: "Aujourd'hui" },
        height: 'auto',
        events: [],
        dateClick: (info) => openModal({ startDate: info.dateStr, endDate: info.dateStr }),
        eventClick: (info) => {
            const r = reservations.find((r) => String(r.id) === info.event.id);
            if (r) openModal(r);
        },
        datesSet: (info) => {
            currentMonth = info.view.currentStart;
            renderStats();
        },
    });
    calendar.render();
}

async function loadReservations() {
    const res = await fetch('api/reservations.php');
    if (!res.ok) {
        if (res.status === 401) { window.location.href = 'login.php'; return; }
        alert('Erreur lors du chargement');
        return;
    }
    reservations = await res.json();
    renderEvents();
    renderStats();
}

function renderEvents() {
    const events = reservations.map((r) => {
        const blocked = !r.isMenage && /not available|closed|blocked/i.test(r.guestName);
        let bg, txt = '#fff';
        if (r.isMenage) {
            bg = MENAGE_COLOR;
        } else if (blocked) {
            bg  = BLOCKED_COLOR;
            txt = '#6B7280';
        } else {
            bg = SOURCE_COLORS[r.source] || SOURCE_COLORS.MANUAL;
        }
        const title = r.isMenage ? `🧹 ${r.guestName || 'Ménage'}` : r.guestName;
        return {
            id: String(r.id),
            title,
            start: (r.startDate || '').slice(0, 10),
            end:   (r.endDate   || '').slice(0, 10),
            backgroundColor: bg,
            borderColor: bg,
            textColor: txt,
            allDay: true,
        };
    });
    calendar.removeAllEvents();
    calendar.addEventSource(events);
}

function renderStats() {
    const y = currentMonth.getFullYear();
    const m = currentMonth.getMonth();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const monthStart  = new Date(y, m, 1);
    const monthEnd    = new Date(y, m + 1, 0);

    const isBooking = (r) => {
        const s = (r.source || '').toUpperCase().replace(/\s+/g, '');
        return (s.includes('AIRBNB') || s.includes('BOOKING')) && !r.isMenage;
    };
    const reservs = reservations.filter(isBooking);

    const occupied = new Set();
    let revenue = 0;

    for (const r of reservs) {
        const start = new Date(r.startDate);
        const end   = new Date(r.endDate);
        const oStart = start < monthStart ? monthStart : start;
        const oEnd   = end   > monthEnd   ? monthEnd   : end;

        let d = new Date(oStart);
        while (d <= oEnd) {
            if (d.getMonth() === m && d.getFullYear() === y) occupied.add(d.getDate());
            d.setDate(d.getDate() + 1);
        }
        if (r.price && start <= monthEnd && end >= monthStart) revenue += Number(r.price);
    }

    const occ      = occupied.size;
    const rate     = daysInMonth > 0 ? Math.round((occ / daysInMonth) * 100) : 0;
    const month    = currentMonth.toLocaleString('fr-FR', { month: 'long' });
    const revStr   = revenue.toLocaleString('fr-FR') + ' €';

    document.getElementById('stats').innerHTML = `
        <div class="stat-card"><div class="label">Occupation ${month}</div>
            <div class="value">${rate}%</div></div>
        <div class="stat-card green"><div class="label">Nuits réservées</div>
            <div class="value">${occ}</div></div>
        <div class="stat-card blue"><div class="label">Nuits disponibles</div>
            <div class="value">${daysInMonth - occ}</div></div>
        <div class="stat-card amber"><div class="label">Revenus ${month}</div>
            <div class="value">${revStr}</div></div>
    `;
}

/* ===== Modale ===== */
let editingId = null;
let editingFromIcal = false;

function openModal(data) {
    editingId = data.id || null;
    const src = (data.source || '').toUpperCase().replace(/\s+/g, '');
    editingFromIcal = src.includes('AIRBNB') || src.includes('BOOKING');

    document.getElementById('modal-title').textContent =
        editingId ? 'Détails de la réservation' : 'Nouvelle réservation';

    document.getElementById('f-name').value   = data.guestName || '';
    document.getElementById('f-start').value  = (data.startDate || '').slice(0, 10);
    document.getElementById('f-end').value    = (data.endDate   || '').slice(0, 10);
    document.getElementById('f-source').value = data.source || 'MANUAL';
    document.getElementById('f-price').value  = data.price ?? '';
    document.getElementById('f-notes').value  = data.notes || '';
    document.getElementById('f-menage').checked = !!data.isMenage;

    document.getElementById('f-start').disabled = editingFromIcal;
    document.getElementById('f-end').disabled   = editingFromIcal;
    document.getElementById('source-row').hidden = editingFromIcal;
    document.getElementById('ical-info').hidden  = !editingFromIcal;
    if (editingFromIcal) {
        document.getElementById('ical-info').textContent =
            `Réservation importée depuis ${data.source} — dates non modifiables`;
    }

    // Bouton supprimer affiché pour toute réservation existante
    document.getElementById('btn-delete').hidden = !editingId;
    document.getElementById('modal').hidden = false;
}

function closeModal() {
    document.getElementById('modal').hidden = true;
    editingId = null;
}

async function saveReservation() {
    const data = {
        guestName: document.getElementById('f-name').value.trim(),
        startDate: document.getElementById('f-start').value,
        endDate:   document.getElementById('f-end').value,
        source:    document.getElementById('f-source').value,
        price:     document.getElementById('f-price').value || null,
        notes:     document.getElementById('f-notes').value || null,
        isMenage:  document.getElementById('f-menage').checked,
    };
    if (!data.guestName || !data.startDate || !data.endDate) {
        alert('Nom, arrivée et départ sont obligatoires');
        return;
    }

    if (editingId) {
        await fetch(`api/reservations.php?id=${editingId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
    } else {
        await fetch('api/reservations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
    }
    closeModal();
    loadReservations();
}

async function deleteReservation() {
    if (!editingId || !confirm('Supprimer cette réservation ?')) return;
    await fetch(`api/reservations.php?id=${editingId}`, { method: 'DELETE' });
    closeModal();
    loadReservations();
}

async function syncIcal() {
    const btn = document.getElementById('btn-sync');
    btn.disabled = true;
    btn.textContent = 'Synchronisation…';
    try {
        const res = await fetch('api/sync.php', { method: 'POST' });
        const data = await res.json();
        if (data.error) alert('Erreur : ' + data.error);
        else loadReservations();
    } catch (e) {
        alert('Erreur réseau');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Synchroniser iCal';
    }
}

/* ChezGigi - Page paramètres : gestion sources iCal */

document.addEventListener('DOMContentLoaded', () => {
    loadSources();
    document.getElementById('btn-add').addEventListener('click', addSource);
    document.getElementById('btn-sync').addEventListener('click', syncAll);
});

async function loadSources() {
    const res = await fetch('api/ical-sources.php');
    if (!res.ok) {
        if (res.status === 401) { window.location.href = 'login.php'; return; }
        document.getElementById('sources-list').innerHTML = '<p>Erreur de chargement</p>';
        return;
    }
    const sources = await res.json();
    renderSources(sources);
}

function renderSources(sources) {
    const list = document.getElementById('sources-list');
    if (!sources.length) {
        list.innerHTML = '<p class="hint">Aucune source. Ajoute Airbnb et Booking ci-dessus.</p>';
        return;
    }
    list.innerHTML = sources.map((s) => {
        const last = s.lastSyncAt
            ? `Dernière sync : ${new Date(s.lastSyncAt).toLocaleString('fr-FR')}`
            : 'Jamais synchronisée';
        return `
            <div class="source-item">
                <div class="info">
                    <div class="name">${escapeHtml(s.name)}</div>
                    <div class="url" title="${escapeHtml(s.url)}">${escapeHtml(s.url)}</div>
                    <div class="last-sync">${last}</div>
                </div>
                <button class="delete-btn" data-id="${s.id}">Supprimer</button>
            </div>
        `;
    }).join('');

    list.querySelectorAll('.delete-btn').forEach((btn) => {
        btn.addEventListener('click', () => deleteSource(btn.dataset.id));
    });
}

async function addSource() {
    const name = document.getElementById('f-name').value.trim();
    const url  = document.getElementById('f-url').value.trim();
    if (!name || !url) { alert('Nom et URL requis'); return; }

    const res = await fetch('api/ical-sources.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, url }),
    });
    if (!res.ok) {
        const data = await res.json();
        alert(data.error || 'Erreur');
        return;
    }
    document.getElementById('f-name').value = '';
    document.getElementById('f-url').value  = '';
    loadSources();
}

async function deleteSource(id) {
    if (!confirm('Supprimer cette source ?')) return;
    await fetch(`api/ical-sources.php?id=${id}`, { method: 'DELETE' });
    loadSources();
}

async function syncAll() {
    const btn = document.getElementById('btn-sync');
    btn.disabled = true;
    btn.textContent = 'Synchronisation…';
    try {
        const res  = await fetch('api/sync.php', { method: 'POST' });
        const data = await res.json();
        if (data.error) {
            alert('Erreur : ' + data.error);
        } else {
            const lines = (data.results || []).map(r =>
                r.error ? `❌ ${r.source}: ${r.error}` : `✅ ${r.source}: ${r.synced} réservations`
            ).join('\n');
            alert('Synchronisation terminée :\n\n' + lines);
            loadSources();
        }
    } catch (e) {
        alert('Erreur réseau');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Synchroniser tout';
    }
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) =>
        ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c])
    );
}

// Agenda : filtres sans rechargement de page, carte des lieux (MapLibre + tuiles OpenFreeMap).
// Tout fonctionne aussi sans JS : les filtres sont des liens, le mois un formulaire.
// Déplacer ou zoomer la carte ajoute le filtre « Recherche par lieu » (zone visible) ; le retirer recentre la carte.

const STYLE = 'https://tiles.openfreemap.org/styles/positron';
// Couleurs de la charte appliquées au fond de carte (crème, mer bleutée, parcs verts).
const PAINT = {
    background: ['background-color', '#F3EBD8'],
    water: ['fill-color', '#BCD3DC'],
    park: ['fill-color', '#DCE3CC'],
    landcover_wood: ['fill-color', '#DCE3CC'],
    landuse_residential: ['fill-color', '#EDE3CC'],
    building: ['fill-color', '#E6DCC4'],
};

export async function initAgenda() {
    const root = document.querySelector('[data-agenda]');
    if (!root) return;

    const results = root.querySelector('[data-agenda-results]');
    const filters = root.querySelector('[data-agenda-filters]');
    const mapEl = document.getElementById('agenda-map');
    let map = null;
    let markers = [];
    let pins = JSON.parse(mapEl?.dataset.pins || '[]');
    let moving = false; // déplacement de la carte provoqué par le code (pas par le visiteur)
    let timer = null;
    let drawPins = () => {};
    let fitAll = () => {};

    const load = async (url, { push = true } = {}) => {
        results.classList.add('is-loading');
        try {
            const res = await fetch(url, { headers: { 'X-Agenda': '1', Accept: 'application/json' } });
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();
            results.innerHTML = data.results;
            filters.innerHTML = data.filters;
            pins = data.pins;
            drawPins();
            if (push) {
                history.pushState({}, '', url);
                // Statistiques (Matomo) : un changement de filtre compte comme une page vue.
                window._paq?.push(['setCustomUrl', location.href], ['setDocumentTitle', document.title], ['trackPageView']);
            }
            syncPeriodToggle(url);
        } catch {
            window.location.href = url; // repli : chargement classique
        } finally {
            results.classList.remove('is-loading');
        }
    };

    // « À venir / Passés » est hors de la zone rechargée : ses liens sont recalculés à partir des filtres actuels
    // (sinon ils garderaient ceux du chargement de la page, et un filtre retiré reviendrait).
    const syncPeriodToggle = (url) => {
        const current = new URL(url, location.href);
        const past = current.searchParams.has('passes');
        document.querySelectorAll('.period-toggle a[data-period]').forEach((a) => {
            const target = new URL(current);
            target.searchParams.delete('nb');
            target.searchParams.delete('passes');
            if (a.dataset.period === 'past') target.searchParams.set('passes', '1');
            a.href = target.pathname + target.search;
            a.classList.toggle('active', past === (a.dataset.period === 'past'));
        });
    };

    // Liens de filtres (y compris « À venir / Passés » dans le bandeau).
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-agenda-link]');
        if (!link || e.metaKey || e.ctrlKey || e.shiftKey) return;
        e.preventDefault();
        if (link.hasAttribute('data-clear-zone')) fitAll();
        load(link.href);
    });

    // Mois : liste déroulante.
    root.addEventListener('change', (e) => {
        const form = e.target.closest('form[data-agenda-month]');
        if (!form) return;
        const params = new URLSearchParams(new FormData(form));
        [...params.keys()].forEach((k) => !params.get(k) && params.delete(k));
        load(`${form.action}${params.toString() ? '?' + params : ''}`);
    });

    addEventListener('popstate', () => load(location.href, { push: false }));

    // Carte masquée (téléphone) : on ne charge pas MapLibre.
    if (!mapEl || !mapEl.offsetParent) return;
    const { default: maplibregl } = await import('maplibre-gl');
    await import('maplibre-gl/dist/maplibre-gl.css');

    try {
        map = new maplibregl.Map({
            container: mapEl,
            style: STYLE,
            center: [7.15, 43.7],
            zoom: 9,
            attributionControl: { compact: true },
        });
    } catch {
        // Navigateur sans WebGL : on retire la carte, les filtres par lieu restent disponibles.
        mapEl.closest('.agenda-map-wrap')?.remove();
        return;
    }
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
    map.on('load', () => {
        // La mention des sources s'ouvre d'elle-même au chargement : on la garde repliée derrière le bouton « i ».
        mapEl.querySelector('.maplibregl-ctrl-attrib')?.classList.remove('maplibregl-compact-show');
        Object.entries(PAINT).forEach(([layer, [prop, value]]) => map.getLayer(layer) && map.setPaintProperty(layer, prop, value));
    });

    drawPins = () => {
        markers.forEach((m) => m.remove());
        markers = pins.map((p) => {
            const el = document.createElement('div');
            el.className = 'map-pin';
            el.style.background = p.color;
            el.title = `${p.name}, ${p.city}`;
            el.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${p.icon}"/></svg>${p.count > 1 ? `<span class="count">${p.count}</span>` : ''}`;
            return new maplibregl.Marker({ element: el })
                .setLngLat([p.lng, p.lat])
                .setPopup(new maplibregl.Popup({ offset: 22, closeButton: false }).setText(`${p.name}, ${p.city} · ${p.count} date${p.count > 1 ? 's' : ''}`))
                .addTo(map);
        });
    };

    fitAll = () => {
        if (!pins.length) return;
        const bounds = new maplibregl.LngLatBounds();
        pins.forEach((p) => bounds.extend([p.lng, p.lat]));
        moving = true;
        map.fitBounds(bounds, { padding: 60, maxZoom: 13, duration: 600 });
    };

    // Vue de départ : la zone demandée dans l'adresse, sinon tous les lieux.
    const zone = mapEl.dataset.zone?.split(',').map(Number);
    if (zone?.length === 4) {
        moving = true;
        map.fitBounds([[zone[0], zone[1]], [zone[2], zone[3]]], { duration: 0 });
    } else {
        fitAll();
    }
    drawPins();

    map.on('moveend', (e) => {
        if (moving || !e.originalEvent) {
            moving = false;
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(() => {
            const b = map.getBounds();
            const url = new URL(location.href);
            url.searchParams.set('zone', [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()].map((n) => n.toFixed(4)).join(','));
            url.searchParams.delete('nb');
            load(url.toString());
        }, 350);
    });
}

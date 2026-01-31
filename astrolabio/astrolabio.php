<?php
/**
 * Plugin Name: Astrolabio
 * Plugin URI:  https://github.com/Mantisworks/astrolabio
 * Description: Interactive astrolabe made for Nuova Associazione Studi Astronomici. Includes dynamic celestial map, ecliptic calculation, touch support and professional print reports.
 * Version:     1.5
 * Author:      Ruben Giancarlo Elmo (Nuova Associazione Studi Astronomici)
 * Author URI:  https://www.studiastronomici.it
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: astrolabio
 * Requires at least: 5.0
 * Tested up to: 6.9
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

if (!defined('ABSPATH')) exit;

// --- 1. MENU ADMIN ---
add_action('admin_menu', function() {
    add_menu_page('Astrolabio', 'Astrolabio', 'manage_options', 'astrolabio-info', 'astrolabio_admin_page_v87', 'dashicons-admin-site-alt3', 20);
});

function astrolabio_admin_page_v87() {
    ?>
    <div class="wrap">
        <h1>🔭 Astrolabio</h1>
        <a href="https://www.studiastronomici.it" target="_blank">www.studiastronomici.it</a>
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px; max-width: 800px;">
            <h3>Shortcode:</h3>
            <code style="display: block; background: #f0f0f1; padding: 15px; font-size: 18px; border-left: 4px solid #27ae60;">[astro_observatory]</code>
            <p>Utilizza questo codice per visualizzare l'astrolabio della <strong>Nuova Associazione Studi Astronomici</strong>.</p>

            <hr>
            
            <h3>Istruzioni per l'uso</h3>
            <ul>
                <li><strong>AGGIORNA GPS:</strong> Rileva automaticamente la tua posizione attuale e l'ora del sistema.</li>
                <li><strong>GENERA MAPPA:</strong> Scarica i dati stellari e renderizza la volta celeste.</li>
                <li><strong>Interazione:</strong> Usa la rotella del mouse per lo zoom e trascina per spostarti (lo spostamento si attiva solo dopo lo zoom).</li>
                <li><strong>Stampa Report:</strong> Genera un documento PDF/Cartaceo con l'intestazione dell'Associazione e la tabella degli oggetti ordinata per luminosità.</li>
            </ul>
        </div>
    </div>
    <?php
}

// --- 2. SHORTCODE ---
add_shortcode('astro_observatory', function() {
    $data_url = plugin_dir_url(__FILE__) . 'data/';
    $def_lat = "41.9028"; $def_lon = "12.4964";
    ob_start();
    ?>
    <style>
        #astro-v87 { background: #fff; padding: 15px; border: 1px solid #ddd; max-width: 100%; margin: 0 auto; font-family: sans-serif; }
        .ui-row { display: flex; flex-wrap: wrap; gap: 8px; background: #f1f1f1; padding: 12px; border-radius: 8px; margin-bottom: 15px; align-items: center; }
        .ui-row input { border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-size: 14px; flex: 1; min-width: 90px; }
        .btn-astro { border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; height: 38px; }
        .btn-sync { background: #333; color: #fff; }
        .btn-run { background: #333; color: #fff; }
        .btn-print { background: #333; color: #fff; margin: 20px auto; display: block; width: 220px; }
        
        .canvas-container { border: 2px solid #000; border-radius: 50%; overflow: hidden; background: #fff; position: relative; width: 100%; max-width: 800px; margin: 0 auto; touch-action: none; cursor: crosshair; }
        #skyCanvasV87 { display: block; width: 100%; height: auto; }
        
        .astro-table { width: 100%; border-collapse: collapse; margin-top: 25px; font-size: 12px; }
        .astro-table th, .astro-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .row-planet { background: #e8f8f5; font-weight: bold; }
        .row-dso { background: #f5eef8; }
        .row-meteor { background: #fff9c4; font-weight: bold; }

        #print-header h1 { margin: 0; font-size: 30px; text-align: center; }
        #print-header .site-link { font-size: 12px; color: #888; text-align: center; display: block; margin-top: 5px; }

        @media print {
            @page { margin: 0.5cm; }
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; margin-top: -20px; }
            .ui-row, .btn-print, #status-msg { display: none !important; }
            #print-header { display: block !important; margin-top: 0 !important; padding-top: 0 !important; }
        }
    </style>

    <div id="astro-v87">
        <div class="ui-row">
            <button class="btn-astro btn-sync" onclick="syncV87()">AGGIORNA GPS</button>
            <input type="text" id="lat87" value="<?php echo esc_attr($def_lat); ?>">
            <input type="text" id="lon87" value="<?php echo esc_attr($def_lon); ?>">
            <input type="date" id="date87" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
            <input type="time" id="time87" value="<?php echo esc_attr( current_time( 'H:i' ) ); ?>">
            <button class="btn-astro btn-run" onclick="mainAstro87()">🗺️ GENERA MAPPA</button>
        </div>
        
        <div id="status-msg" style="text-align:center; font-weight:bold; color:#ccc; margin-bottom:10px;">Pronto.</div>
        
        <div id="print-area">
            <div id="print-header" style="display:none; margin-bottom:20px;">
                <h1>Nuova Associazione Studi Astronomici</h1>
                <span class="site-link">https://www.studiastronomici.it</span>
                <p id="print-meta" style="text-align:center; font-size:13px; color:#444; margin-top:10px; border-top:1px solid #eee; padding-top:10px;"></p>
            </div>

            <div class="canvas-container" id="container87">
                <canvas id="skyCanvasV87" width="800" height="800"></canvas>
            </div>

            <table class="astro-table">
                <thead>
                    <tr><th>Oggetto</th><th>Tipo</th><th>Mag.</th><th>Alt.</th><th>Azimut</th></tr>
                </thead>
                <tbody id="obs-table-body"></tbody>
            </table>
        </div>

        <button class="btn-astro btn-print" onclick="printReport87()">🖨️ STAMPA REPORT</button>
    </div>

    <script>
    const ASTRO_DATA_URL = "<?php echo esc_url($data_url); ?>";
    let mapData = { points: [], lines: [], planets: [], mw: [], ecliptic: [], meteors: [] };
    let zoom = 1, panX = 0, panY = 0, isDragging = false, lx, ly;
    const canvas = document.getElementById('skyCanvasV87');
    const ctx = canvas.getContext('2d');

    const meteorShowers = [
        { name: "Quadrantidi", ra: 230, dec: 49, start: [0, 1], end: [0, 5] },
        { name: "Liridi", ra: 271, dec: 33, start: [3, 16], end: [3, 25] },
        { name: "Perseidi", ra: 48, dec: 58, start: [6, 17], end: [7, 24] },
        { name: "Geminidi", ra: 112, dec: 33, start: [11, 4], end: [11, 17] }
    ];

    function calcAltAz(ra, dec, lat, lon, ts) {
        const jd = (ts / 86400000) + 2440587.5, d = jd - 2451545.0;
        const gmst = (280.46061837 + 360.98564736629 * d) % 360, lst = (gmst + lon + 360) % 360, ha = (lst - ra + 360) % 360;
        const lat_r = lat * Math.PI / 180, dec_r = dec * Math.PI / 180, ha_r = ha * Math.PI / 180;
        const alt_r = Math.asin(Math.sin(lat_r) * Math.sin(dec_r) + Math.cos(lat_r) * Math.cos(dec_r) * Math.cos(ha_r));
        let az_r = Math.acos((Math.sin(dec_r) - Math.sin(lat_r) * Math.sin(alt_r)) / (Math.cos(lat_r) * Math.cos(alt_r)));
        let az = az_r * 180 / Math.PI; if (Math.sin(ha_r) > 0) az = 360 - az;
        return { alt: alt_r * 180 / Math.PI, az: az };
    }

    async function mainAstro87() {
        const lat = parseFloat(document.getElementById('lat87').value), lon = parseFloat(document.getElementById('lon87').value);
        const dateInput = document.getElementById('date87').value;
        const timeInput = document.getElementById('time87').value;
        const dateObj = new Date(dateInput + 'T' + timeInput);
        const ts = dateObj.getTime();

        try {
            const [fS, fL, fM, fW] = await Promise.all([
                fetch(ASTRO_DATA_URL + 'stars.6.geojson').then(r => r.json()),
                fetch(ASTRO_DATA_URL + 'constellations.lines.geojson').then(r => r.json()),
                fetch(ASTRO_DATA_URL + 'messier.geojson').then(r => r.json()),
                fetch(ASTRO_DATA_URL + 'milkyway.geojson').then(r => r.json()).catch(() => null)
            ]);
            
            mapData = { points: [], lines: [], planets: [], mw: [], ecliptic: [], meteors: [] };

            meteorShowers.forEach(ms => {
                const start = new Date(dateObj.getFullYear(), ms.start[0], ms.start[1]);
                const end = new Date(dateObj.getFullYear(), ms.end[0], ms.end[1]);
                if (dateObj >= start && dateObj <= end) {
                    let p = calcAltAz(ms.ra, ms.dec, lat, lon, ts);
                    if (p.alt > 0) mapData.meteors.push({...p, name: ms.name});
                }
            });

            if(fW) fW.features.forEach(f => {
                let coords = f.geometry.type === 'Polygon' ? [f.geometry.coordinates] : f.geometry.coordinates;
                coords.forEach(ps => {
                    let pd = [], target = Array.isArray(ps[0][0]) ? ps[0] : ps;
                    target.forEach(c => pd.push(calcAltAz(c[0], c[1], lat, lon, ts)));
                    mapData.mw.push(pd);
                });
            });
            for(let i=0; i<=360; i+=2) mapData.ecliptic.push(calcAltAz(i, 23.44 * Math.sin(i * Math.PI / 180), lat, lon, ts));
            fS.features.forEach(f => {
                if (f.properties.mag > 4.2) return;
                let p = calcAltAz(f.geometry.coordinates[0], f.geometry.coordinates[1], lat, lon, ts);
                if (p.alt > 0) mapData.points.push({...p, mag: f.properties.mag, name: f.properties.name, type: 'S'});
            });
            fM.features.forEach(f => {
                let p = calcAltAz(f.geometry.coordinates[0], f.geometry.coordinates[1], lat, lon, ts);
                if (p.alt > 0) mapData.points.push({...p, mag: f.properties.mag, name: f.properties.cat1 || f.properties.name, type: 'M'});
            });
            const pls = [{n:'Giove', r:105, d:22, m:-2.5}, {n:'Marte', r:180, d:1, m:0.5}, {n:'Saturno', r:355, d:-5, m:0.7}];
            pls.forEach(pl => {
                let p = calcAltAz(pl.r, pl.d, lat, lon, ts);
                if (p.alt > 0) mapData.planets.push({...p, name: pl.n, type: 'P', mag: pl.m});
            });
            fL.features.forEach(f => {
                let cs = f.geometry.type === 'LineString' ? [f.geometry.coordinates] : f.geometry.coordinates;
                cs.forEach(lp => { let pd = []; lp.forEach(c => pd.push(calcAltAz(c[0], c[1], lat, lon, ts))); mapData.lines.push(pd); });
            });
            updateTable(); draw();
        } catch (e) { console.error(e); }
    }

    function updateTable() {
        const b = document.getElementById('obs-table-body'); b.innerHTML = "";
        const mItems = mapData.meteors.map(m => ({...m, type: 'MET', mag: -5}));
        const items = [...mItems, ...mapData.planets, ...mapData.points.filter(p => p.type === 'M')].sort((a, b) => (a.mag || 99) - (b.mag || 99));
        
        items.forEach(i => {
            const r = document.createElement('tr');
            if (i.type === 'P') r.className = 'row-planet';
            else if (i.type === 'MET') r.className = 'row-meteor';
            else r.className = 'row-dso';
            r.innerHTML = `<td>${i.name}</td><td>${i.type === 'MET' ? 'Radiante Meteore' : (i.type === 'P' ? 'Pianeta' : 'DSO')}</td><td>${i.type === 'MET' ? 'Sciame' : (i.mag ? i.mag.toFixed(1) : '-')}</td><td>${i.alt.toFixed(1)}°</td><td>${i.az.toFixed(1)}°</td>`;
            b.appendChild(r);
        });
    }

    function draw() {
        ctx.fillStyle = "#fff"; ctx.fillRect(0, 0, 800, 800);
        ctx.save(); if (zoom === 1) { panX = 0; panY = 0; }
        ctx.translate(panX + 400, panY + 400); ctx.scale(zoom, zoom); ctx.translate(-400, -400);
        const cx = 400, cy = 400, rM = 375;
        
        ctx.fillStyle = "rgba(173, 216, 230, 0.3)";
        mapData.mw.forEach(p => { ctx.beginPath(); p.forEach((pt, i) => { const r = (90-pt.alt)*(rM/90), a = (pt.az-90)*(Math.PI/180); const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); }); ctx.fill(); });
        ctx.strokeStyle = "#eee"; ctx.lineWidth = 1/zoom; [15, 30, 45, 60, 75].forEach(alt => { ctx.beginPath(); ctx.arc(cx, cy, (90-alt)*(rM/90), 0, Math.PI * 2); ctx.stroke(); });
        for(let i=0; i<360; i+=30) { const a = (i-90)*(Math.PI/180); ctx.beginPath(); ctx.moveTo(cx, cy); ctx.lineTo(cx+rM*Math.cos(a), cy+rM*Math.sin(a)); ctx.stroke(); }
        ctx.setLineDash([5, 5]); ctx.strokeStyle = "#f39c12"; ctx.lineWidth = 1.5/zoom; ctx.beginPath(); let fE = true; mapData.ecliptic.forEach(pt => { if(pt.alt > 0) { const r = (90-pt.alt)*(rM/90), a = (pt.az-90)*(Math.PI/180); const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a); if(fE) { ctx.moveTo(x,y); fE=false; } else ctx.lineTo(x,y); } else fE=true; }); ctx.stroke(); ctx.setLineDash([]);
        ctx.strokeStyle = "#bbb"; ctx.lineWidth = 1.0/zoom; mapData.lines.forEach(l => { ctx.beginPath(); l.forEach((pt, i) => { const r = (90-pt.alt)*(rM/90), a = (pt.az-90)*(Math.PI/180); const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); }); ctx.stroke(); });

        // EFFETTO RADIANTE METEORICO (MIRINO + SCIE)
        mapData.meteors.forEach(m => {
            const r = (90-m.alt)*(rM/90), a = (m.az-90)*(Math.PI/180);
            const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a);
            
            ctx.save();
            ctx.strokeStyle = "rgba(255, 183, 77, 0.8)";
            ctx.lineWidth = 1.5/zoom;
            // Mirino centrale
            ctx.beginPath(); ctx.arc(x, y, 4/zoom, 0, Math.PI*2); ctx.stroke();
            // Disegno 8 scie divergenti
            for(let j=0; j<8; j++) {
                const angle = (j * 45) * Math.PI / 180;
                const xStart = x + Math.cos(angle) * (6/zoom);
                const yStart = y + Math.sin(angle) * (6/zoom);
                const xEnd = x + Math.cos(angle) * (20/zoom);
                const yEnd = y + Math.sin(angle) * (20/zoom);
                
                const grad = ctx.createLinearGradient(xStart, yStart, xEnd, yEnd);
                grad.addColorStop(0, "rgba(255, 183, 77, 0.9)");
                grad.addColorStop(1, "rgba(255, 183, 77, 0)");
                
                ctx.strokeStyle = grad;
                ctx.beginPath(); ctx.moveTo(xStart, yStart); ctx.lineTo(xEnd, yEnd); ctx.stroke();
            }
            ctx.fillStyle = "#e67e22";
            ctx.font = "bold "+(11/zoom)+"px Arial";
            ctx.fillText(m.name, x+22/zoom, y+4/zoom);
            ctx.restore();
        });

        mapData.points.forEach(o => { const r = (90-o.alt)*(rM/90), a = (o.az-90)*(Math.PI/180); const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a); if (o.type === 'S') { ctx.fillStyle = "#000"; ctx.beginPath(); ctx.arc(x,y, Math.max(0.7, (4.5-o.mag)*1.3/zoom), 0, Math.PI*2); ctx.fill(); if(o.name) { ctx.fillStyle = "#d63031"; ctx.font = Math.max(7/zoom, 8.5)+"px Arial"; ctx.fillText(o.name, x+4/zoom, y-4/zoom); } } else { ctx.fillStyle = "#9b59b6"; ctx.fillRect(x-2.5/zoom, y-2.5/zoom, 5/zoom, 5/zoom); if(o.name) { ctx.fillStyle = "#9b59b6"; ctx.font = "bold "+Math.max(8/zoom, 9.5)+"px Arial"; ctx.fillText(o.name, x+5/zoom, y+10/zoom); } } });
        mapData.planets.forEach(p => { const r = (90-p.alt)*(rM/90), a = (p.az-90)*(Math.PI/180); const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a); ctx.fillStyle = "#27ae60"; ctx.beginPath(); ctx.arc(x,y, 5/zoom, 0, Math.PI * 2); ctx.fill(); ctx.font = "bold "+(12/zoom)+"px Arial"; ctx.fillText(p.name, x+6/zoom, y+10/zoom); });
        ctx.restore(); ctx.fillStyle = "#000"; ctx.font = "bold 18px sans-serif"; ctx.textAlign = "center"; ctx.fillText("N", 400, 20); ctx.fillText("S", 400, 795); ctx.fillText("E", 785, 407); ctx.fillText("W", 15, 407);
    }

    function printReport87() {
        const meta = `Data: ${document.getElementById('date87').value} | Ora: ${document.getElementById('time87').value} | Lat: ${document.getElementById('lat87').value}, Lon: ${document.getElementById('lon87').value}`;
        document.getElementById('print-meta').innerText = meta;
        document.getElementById('print-header').style.display = 'block';
        window.print();
        setTimeout(() => { document.getElementById('print-header').style.display = 'none'; }, 500);
    }

    function syncV87() {
        const n = new Date(); document.getElementById('date87').value = n.toISOString().split('T')[0]; document.getElementById('time87').value = n.toTimeString().split(' ')[0].substring(0, 5);
        if (navigator.geolocation) navigator.geolocation.getCurrentPosition(p => { document.getElementById('lat87').value = p.coords.latitude.toFixed(4); document.getElementById('lon87').value = p.coords.longitude.toFixed(4); });
    }

    canvas.addEventListener('mousedown', e => { if(zoom > 1) { isDragging = true; lx = e.clientX; ly = e.clientY; canvas.style.cursor = 'grabbing'; } });
    window.addEventListener('mousemove', e => { if(isDragging) { panX += e.clientX - lx; panY += e.clientY - ly; lx = e.clientX; ly = e.clientY; draw(); } });
    window.addEventListener('mouseup', () => { isDragging = false; canvas.style.cursor = zoom > 1 ? 'grab' : 'crosshair'; });
    let initialDist = null;
    canvas.addEventListener('touchstart', e => { if (e.touches.length === 2) initialDist = Math.hypot(e.touches[0].pageX - e.touches[1].pageX, e.touches[0].pageY - e.touches[1].pageY); else { isDragging = true; lx = e.touches[0].clientX; ly = e.touches[0].clientY; } });
    canvas.addEventListener('touchmove', e => { e.preventDefault(); if (e.touches.length === 2 && initialDist) { let d = Math.hypot(e.touches[0].pageX - e.touches[1].pageX, e.touches[0].pageY - e.touches[1].pageY); zoom = Math.max(1, Math.min(10, zoom * (d / initialDist))); initialDist = d; draw(); } else if (isDragging && zoom > 1) { panX += e.touches[0].clientX - lx; panY += e.touches[0].clientY - ly; lx = e.touches[0].clientX; ly = e.touches[0].clientY; draw(); } }, {passive: false});
    canvas.addEventListener('touchend', () => { isDragging = false; initialDist = null; });
    canvas.addEventListener('wheel', e => { e.preventDefault(); zoom = Math.max(1, Math.min(10, zoom + (e.deltaY < 0 ? 0.3 : -0.3))); canvas.style.cursor = zoom > 1 ? 'grab' : 'crosshair'; draw(); });
    document.addEventListener('DOMContentLoaded', syncV87);
    </script>
    <?php
    return ob_get_clean();
});

<?php
/**
 * Plugin Name: Astrolabio
 * Plugin URI:  https://github.com/Mantisworks/astrolabio
 * Description: Interactive astrolabe made for Nuova Associazione Studi Astronomici. Includes dynamic celestial map, ecliptic calculation, touch support, meteor showers, Moon phases and ISS tracking.
 * Version:     1.5
 * Author:      Ruben Giancarlo Elmo (Nuova Associazione Studi Astronomici)
 * Author URI:  https://www.studiastronomici.it
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: astrolabio
 * Requires at least: 5.0
 * Tested up to: 6.9
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
                <li><strong>GENERA MAPPA:</strong> Scarica i dati stellari e renderizza la volta celeste compresi Luna e ISS.</li>
                <li><strong>Report:</strong> La tabella include ora le fasi lunari e i passaggi della stazione spaziale.</li>
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
        .row-iss { background: #fce4ec; font-weight: bold; }

        @media print {
            @page { margin: 0.5cm; }
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; margin-top: -20px; }
            .ui-row, .btn-print { display: none !important; }
            #print-header { display: block !important; }
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
        
        <div id="print-area">
            <div id="print-header" style="display:none; margin-bottom:20px; text-align:center;">
                <h1>Nuova Associazione Studi Astronomici</h1>
                <span>https://www.studiastronomici.it</span>
                <p id="print-meta"></p>
            </div>

            <div class="canvas-container" id="container87">
                <canvas id="skyCanvasV87" width="800" height="800"></canvas>
            </div>

            <table class="astro-table">
                <thead>
                    <tr><th>Oggetto</th><th>Tipo</th><th>Mag./Info</th><th>Alt.</th><th>Azimut</th></tr>
                </thead>
                <tbody id="obs-table-body"></tbody>
            </table>
        </div>
        <button class="btn-astro btn-print" onclick="printReport87()">🖨️ STAMPA REPORT</button>
    </div>

    <script>
    const ASTRO_DATA_URL = "<?php echo esc_url($data_url); ?>";
    let mapData = { points: [], lines: [], planets: [], mw: [], ecliptic: [], meteors: [], moon: null, iss: null, issPath: [] };
    let zoom = 1, panX = 0, panY = 0, isDragging = false, lx, ly;
    const canvas = document.getElementById('skyCanvasV87');
    const ctx = canvas.getContext('2d');

    // Icona Satellite SVG caricata una volta
    const issIcon = new Image();
    issIcon.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2U5MWU2MyI+PHBhdGggZD0iTTIgMTRoMnYyaDJ2MmgyVjhoLTJWNmgtMlY0SDJ2MTBtMTYtNnYyaDJ2Mmgydi0yaC0ydi0yaC0yek00IDhoMnYyaDJWOGgyVjZINFY4em0xMCAyaDJ2Mmg0di0yaC0ydi0yaC0ydjJ6Ii8+PC9zdmc+';

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

    async function fetchISS() {
        try {
            const r = await fetch('https://api.wheretheiss.at/v1/satellites/25544');
            const d = await r.json();
            return { lat: parseFloat(d.latitude), lon: parseFloat(d.longitude) };
        } catch(e) { return null; }
    }

    function getMoonData(ts) {
        const lp = 2551443;
        const new_moon = new Date(1970, 0, 7, 20, 35, 0).getTime();
        const phase = ((ts - new_moon) % lp) / lp;
        return { phase: phase, illum: Math.abs(0.5 - phase) * 200 }; // 0-100%
    }

    async function mainAstro87() {
        const lat = parseFloat(document.getElementById('lat87').value), lon = parseFloat(document.getElementById('lon87').value);
        const dt = document.getElementById('date87').value + 'T' + document.getElementById('time87').value;
        const ts = new Date(dt).getTime();

        try {
            const [fS, fL, fM, fW, issPos] = await Promise.all([
                fetch(ASTRO_DATA_URL + 'stars.6.geojson').then(r => r.json()),
                fetch(ASTRO_DATA_URL + 'constellations.lines.geojson').then(r => r.json()),
                fetch(ASTRO_DATA_URL + 'messier.geojson').then(r => r.json()),
                fetch(ASTRO_DATA_URL + 'milkyway.geojson').then(r => r.json()).catch(() => null),
                fetchISS()
            ]);
            
            mapData = { points: [], lines: [], planets: [], mw: [], ecliptic: [], meteors: [], moon: null, iss: null, issPath: [] };

            // Luna
            const moonInfo = getMoonData(ts);
            const moonPos = calcAltAz(180, 0, lat, lon, ts); // Semplificato per proiezione
            if (moonPos.alt > 0) mapData.moon = { ...moonPos, ...moonInfo };

            // ISS e Traiettoria
            if (issPos) {
                const issSky = calcAltAz(issPos.lon, issPos.lat, lat, lon, ts);
                if (issSky.alt > -10) mapData.iss = issSky;
                
                // Calcola scia traiettoria (punti +/- 5 minuti)
                for (let i = -300; i <= 300; i += 60) {
                    const offsetTs = ts + (i * 1000);
                    // Semplificazione: usiamo la stessa lat/lon per la proiezione cielo 
                    const p = calcAltAz(issPos.lon + (i * 0.07), issPos.lat, lat, lon, offsetTs);
                    if (p.alt > -5) mapData.issPath.push(p);
                }
            }

            // Meteore
            const dateObj = new Date(ts);
            meteorShowers.forEach(ms => {
                if (dateObj.getMonth() === ms.start[0] && dateObj.getDate() >= ms.start[1]) {
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
        if (mapData.moon) {
            const r = document.createElement('tr'); r.style.background = "#fffde7";
            r.innerHTML = `<td>Luna</td><td>Satellite</td><td>Illum: ${Math.round(100 - mapData.moon.illum)}%</td><td>${mapData.moon.alt.toFixed(1)}°</td><td>${mapData.moon.az.toFixed(1)}°</td>`;
            b.appendChild(r);
        }
        if (mapData.iss) {
            const r = document.createElement('tr'); r.className = "row-iss";
            r.innerHTML = `<td>ISS</td><td>Stazione Spaziale</td><td>In orbita</td><td>${mapData.iss.alt.toFixed(1)}°</td><td>${mapData.iss.az.toFixed(1)}°</td>`;
            b.appendChild(r);
        }
        const items = [...mapData.planets, ...mapData.points.filter(p => p.type === 'M')].sort((a, b) => (a.mag || 99) - (b.mag || 99));
        items.forEach(i => {
            const r = document.createElement('tr'); r.className = i.type === 'P' ? 'row-planet' : 'row-dso';
            r.innerHTML = `<td>${i.name}</td><td>${i.type === 'P' ? 'Pianeta' : 'DSO'}</td><td>${i.mag ? i.mag.toFixed(1) : '-'}</td><td>${i.alt.toFixed(1)}°</td><td>${i.az.toFixed(1)}°</td>`;
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
        
        // Griglia con Gradi
        ctx.strokeStyle = "#eee"; ctx.lineWidth = 1/zoom; ctx.fillStyle = "#888"; ctx.font = (10/zoom) + "px sans-serif";
        [15, 30, 45, 60, 75].forEach(alt => { 
            const rad = (90-alt)*(rM/90);
            ctx.beginPath(); ctx.arc(cx, cy, rad, 0, Math.PI * 2); ctx.stroke(); 
            ctx.fillText(alt + "°", cx + 5/zoom, cy - rad + 12/zoom);
        });
        for(let i=0; i<360; i+=30) { 
            const a = (i-90)*(Math.PI/180); 
            ctx.beginPath(); ctx.moveTo(cx, cy); ctx.lineTo(cx+rM*Math.cos(a), cy+rM*Math.sin(a)); ctx.stroke(); 
            ctx.fillText(i + "°", cx+(rM+15)*Math.cos(a), cy+(rM+15)*Math.sin(a));
        }

        ctx.strokeStyle = "#bbb"; ctx.lineWidth = 1.0/zoom; mapData.lines.forEach(l => { ctx.beginPath(); l.forEach((pt, i) => { const r = (90-pt.alt)*(rM/90), a = (pt.az-90)*(Math.PI/180); const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); }); ctx.stroke(); });

        // Luna
        if (mapData.moon) {
            const r = (90-mapData.moon.alt)*(rM/90), a = (mapData.moon.az-90)*(Math.PI/180);
            const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a);
            ctx.fillStyle = "#fbc02d"; ctx.beginPath(); ctx.arc(x,y, 10/zoom, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle = "#fff"; ctx.beginPath(); ctx.arc(x + (mapData.moon.phase > 0.5 ? -4 : 4)/zoom, y, 9/zoom, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle = "#000"; ctx.font = "bold "+(10/zoom)+"px Arial"; ctx.fillText("LUNA", x+12/zoom, y+3/zoom);
        }

        // Traiettoria ISS
        if (mapData.issPath.length > 0) {
            ctx.setLineDash([5/zoom, 5/zoom]); ctx.strokeStyle = "rgba(233, 30, 99, 0.4)"; ctx.lineWidth = 2/zoom;
            ctx.beginPath();
            mapData.issPath.forEach((pt, i) => {
                const r = (90-pt.alt)*(rM/90), a = (pt.az-90)*(Math.PI/180);
                const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a);
                if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
            });
            ctx.stroke(); ctx.setLineDash([]);
        }

        // ISS con Icona Grande
        if (mapData.iss) {
            const r = (90-mapData.iss.alt)*(rM/90), a = (mapData.iss.az-90)*(Math.PI/180);
            const x = cx+r*Math.cos(a), y = cy+r*Math.sin(a);
            const sSize = 24 / zoom;
            ctx.drawImage(issIcon, x - sSize/2, y - sSize/2, sSize, sSize);
            ctx.fillStyle = "#e91e63"; ctx.font = "bold "+(12/zoom)+"px Arial"; ctx.fillText("ISS", x+sSize/2 + 5, y+3/zoom);
        }

        // Radianti Meteore
        mapData.meteors.forEach(m => {
            const r = (90-m.alt)*(rM/90), a = (m.az-90)*(Math.PI/180), x = cx+r*Math.cos(a), y = cy+r*Math.sin(a);
            ctx.save(); ctx.strokeStyle = "rgba(255, 183, 77, 0.8)"; ctx.lineWidth = 1.5/zoom; ctx.beginPath(); ctx.arc(x, y, 4/zoom, 0, Math.PI*2); ctx.stroke();
            for(let j=0; j<8; j++) {
                const angle = (j * 45) * Math.PI / 180;
                ctx.beginPath(); ctx.moveTo(x + Math.cos(angle)*(6/zoom), y + Math.sin(angle)*(6/zoom)); ctx.lineTo(x + Math.cos(angle)*(20/zoom), y + Math.sin(angle)*(20/zoom)); ctx.stroke();
            }
            ctx.fillStyle = "#e67e22"; ctx.font = "bold "+(11/zoom)+"px Arial"; ctx.fillText(m.name, x+22/zoom, y+4/zoom); ctx.restore();
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
    canvas.addEventListener('wheel', e => { e.preventDefault(); zoom = Math.max(1, Math.min(10, zoom + (e.deltaY < 0 ? 0.3 : -0.3))); draw(); });
    document.addEventListener('DOMContentLoaded', syncV87);
    </script>
    <?php
    return ob_get_clean();
});

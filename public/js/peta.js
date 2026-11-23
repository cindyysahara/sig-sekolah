const Peta = {
    // Pusat Kota Banjarmasin, urutan [latitude, longitude]
    pusat: [-3.3194, 114.5908],

    // Warna marker per jenjang
    warna: { SD: '#16a34a', SMP: '#2563eb', SMA: '#f59e0b', SMK: '#dc2626' },

    // Membuat peta dengan kontrol pergantian peta dasar (L.control.layers).
    // Kontrol disimpan di map.kontrolLapisan agar menu lain dapat
    // menambahkan lapisan lewat map.kontrolLapisan.addOverlay(...)
    buat(idElemen, zoom = 12) {
        const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });
        const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: 'Data: &copy; OpenStreetMap contributors, SRTM | Gaya: &copy; OpenTopoMap (CC-BY-SA)',
        });
        const citra = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri',
        });

        const map = L.map(idElemen, { layers: [osm] })
            .setView(Peta.pusat, zoom);

        map.kontrolLapisan = L.control.layers({
            'OpenStreetMap': osm,
            'Topografi': topo,
            'Citra Satelit': citra,
        }, {}).addTo(map);

        return map;
    },

    // Mencegah teks dari database dibaca sebagai HTML (XSS)
    esc(teks) {
        const d = document.createElement('div');
        d.textContent = teks ?? '';
        return d.innerHTML;
    },

    // Mengambil seluruh fitur (Feature) sekolah dari endpoint GeoJSON.
    // Parameter jenjang dan status boleh dikosongkan.
    async ambilSekolah(jenjang = '', status = '') {
        const url = `${window.PETA_URL.data}`
            + `?jenjang=${encodeURIComponent(jenjang)}`
            + `&status=${encodeURIComponent(status)}`;
        const respon = await fetch(url);
        if (!respon.ok) {
            throw new Error('Gagal memuat data sekolah');
        }
        const data = await respon.json();
        return data.features;
    },

    // GeoJSON [lng, lat] menjadi Leaflet [lat, lng]
    latlng(fitur) {
        const [lng, lat] = fitur.geometry.coordinates;
        return [lat, lng];
    },

    popup(fitur) {
        const p = fitur.properties;
        const [lat, lng] = Peta.latlng(fitur);
        return `
            <strong>${Peta.esc(p.nama)}</strong><br>
            NPSN: ${Peta.esc(p.npsn)}<br>
            ${Peta.esc(p.jenjang)} - ${Peta.esc(p.status)}<br>
            ${Peta.esc(p.alamat)}, ${Peta.esc(p.kecamatan)}<br>
            Telp: ${Peta.esc(p.telepon) || '-'}<br>
            Kepala sekolah: ${Peta.esc(p.kepala_sekolah) || '-'}<br>
            Akreditasi: ${Peta.esc(p.akreditasi) || '-'}<br>
            Siswa: ${p.jumlah_siswa ?? '-'} | Guru: ${p.jumlah_guru ?? '-'}<br>
            <small>Koordinat: ${lat}, ${lng}</small>
        `;
    },

    // Ikon bulat berwarna sesuai jenjang, berisi teks jenjang
    ikonJenjang(jenjang) {
        const warna = Peta.warna[jenjang] ?? '#6b7280';
        const gaya = `background:${warna};width:32px;height:32px;`
            + 'border-radius:50%;border:3px solid #fff;box-sizing:border-box;'
            + 'box-shadow:0 2px 4px rgba(0,0,0,.4);display:flex;'
            + 'align-items:center;justify-content:center;'
            + 'color:#fff;font:bold 10px sans-serif';
        return L.divIcon({
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16],
            html: `<div style="${gaya}">${Peta.esc(jenjang)}</div>`,
        });
    },

    // Marker sekolah lengkap dengan ikon jenjang dan popup
    marker(fitur) {
        return L.marker(Peta.latlng(fitur), {
            icon: Peta.ikonJenjang(fitur.properties.jenjang),
        }).bindPopup(Peta.popup(fitur));
    },

    // Legenda warna jenjang di kiri bawah peta
    legenda(map) {
        const kontrol = L.control({ position: 'bottomleft' });
        kontrol.onAdd = () => {
            const div = L.DomUtil.create('div');
            div.style.cssText = 'background:#fff;padding:8px 10px;'
                + 'border-radius:6px;box-shadow:0 1px 5px rgba(0,0,0,.4);'
                + 'font-size:13px;line-height:1.7';
            div.innerHTML = Object.entries(Peta.warna).map(([j, w]) =>
                `<div><i style="display:inline-block;width:12px;height:12px;`
                + `border-radius:50%;margin-right:6px;background:${w}"></i>${j}</div>`
            ).join('');
            return div;
        };
        kontrol.addTo(map);
        return kontrol;
    },

    // Mengunduh daftar fitur sebagai berkas .geojson
    unduh(fitur, namaBerkas = 'sekolah.geojson') {
        const isi = JSON.stringify(
            { type: 'FeatureCollection', features: fitur }, null, 2);
        const blob = new Blob([isi], { type: 'application/geo+json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = namaBerkas;
        a.click();
        URL.revokeObjectURL(a.href);
    },

    // Jarak garis lurus (km) antara dua titik [lat, lng], rumus Haversine
    jarakKm(a, b) {
        const R = 6371;
        const rad = (x) => (x * Math.PI) / 180;
        const dLat = rad(b[0] - a[0]);
        const dLng = rad(b[1] - a[1]);
        const h = Math.sin(dLat / 2) ** 2 +
            Math.cos(rad(a[0])) * Math.cos(rad(b[0])) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(h));
    },

    // Penanda bulat berisi angka (dipakai pada menu Rute)
    ikonAngka(angka, warna) {
        return L.divIcon({
            className: '',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            html: `<div style="background:${warna};width:30px;height:30px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:13px">${angka}</div>`,
        });
    },
};
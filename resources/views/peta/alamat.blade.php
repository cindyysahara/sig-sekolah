@extends('layouts.public')
@section('title', 'Cek Lokasi')

@section('content')
<div class="toolbar">
    <span>Klik di mana saja pada peta, atau gunakan posisi Anda.</span>
    <button type="button" id="btn-lokasi">Lokasi saya</button>
    <span id="info-lokasi" style="color: #6b7280;"></span>
</div>
<div id="peta" class="peta"></div>
<div id="hasil" class="panel"
     style="top: 120px; right: 12px; width: 330px; max-height: 70%;
            overflow: auto; display: none;"></div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);
    const panel = document.getElementById('hasil');
    const RADIUS_KM = 1;

    let semua = [];
    let terakhir = 0;

    // Promise data sekolah, ditunggu sebelum analisis dijalankan
    const dataSiap = Peta.ambilSekolah().then((fitur) => { semua = fitur; });

    // n sekolah terdekat dari sebuah titik [lat, lng]
    function terdekat(titik, n = 3) {
        return semua
            .map((f) => ({ fitur: f, jarak: Peta.jarakKm(titik, Peta.latlng(f)) }))
            .sort((a, b) => a.jarak - b.jarak)
            .slice(0, n);
    }

    async function ambilAlamat(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse'
            + '?format=jsonv2&addressdetails=1&accept-language=id'
            + `&lat=${lat}&lon=${lng}`;
        const respon = await fetch(url);
        if (!respon.ok) throw new Error('HTTP ' + respon.status);
        return respon.json();
    }

    // Seluruh analisis untuk satu titik (dipakai klik peta dan Lokasi saya)
    async function periksa(lat, lng) {
        // Kebijakan Nominatim: maksimal 1 permintaan per detik
        const sekarang = Date.now();
        if (sekarang - terakhir < 1200) return;
        terakhir = sekarang;

        await dataSiap;

        const titik = [lat, lng];
        lapisan.clearLayers();

        // Lingkaran jangkauan 1 km
        L.circle(titik, {
            radius: RADIUS_KM * 1000,
            color: '#16a34a', weight: 2,
            fillColor: '#16a34a', fillOpacity: 0.12,
        }).addTo(lapisan);

        // Sekolah di dalam lingkaran
        const dalam = semua.filter(
            (f) => Peta.jarakKm(titik, Peta.latlng(f)) <= RADIUS_KM
        );
        dalam.forEach((f) => {
            L.circleMarker(Peta.latlng(f), {
                radius: 5, color: '#fff', weight: 1,
                fillColor: '#16a34a', fillOpacity: 1,
            }).bindPopup(Peta.popup(f)).addTo(lapisan);
        });

        // Titik yang dipilih
        L.circleMarker(titik, {
            radius: 9, color: '#fff', weight: 3,
            fillColor: '#ef4444', fillOpacity: 1,
        }).addTo(lapisan);

        // Garis ke tiga sekolah terdekat
        const dekat = terdekat(titik);
        dekat.forEach((d) => {
            L.polyline([titik, Peta.latlng(d.fitur)], {
                color: '#2563eb', weight: 2, dashArray: '6 6',
            }).addTo(lapisan);
        });

        panel.style.display = 'block';
        panel.textContent = 'Mencari alamat...';

        let bagianAlamat = '<em>Alamat tidak dapat dimuat.</em>';
        try {
            const data = await ambilAlamat(lat, lng);
            const a = data.address || {};
            const baris = [
                ['Jalan', a.road],
                ['Kelurahan/Desa', a.suburb || a.neighbourhood || a.village],
                ['Kota/Kabupaten', a.city || a.town || a.municipality || a.county],
                ['Provinsi', a.state],
                ['Kode pos', a.postcode],
            ].map(([k, v]) => `
                <tr>
                    <td style="padding: 2px 8px 2px 0; color: #6b7280;">${k}</td>
                    <td>${Peta.esc(v) || '-'}</td>
                </tr>`).join('');

            bagianAlamat = `
                <p style="margin: 6px 0;">${Peta.esc(data.display_name || '-')}</p>
                <table style="font-size: 13px;">${baris}</table>`;
        } catch (err) {
            console.error(err);
        }

        const perJenjang = ['SD', 'SMP', 'SMA', 'SMK'].map((j) =>
            `${j}: ${dalam.filter((f) => f.properties.jenjang === j).length}`
        ).join(' | ');

        const daftar = dekat.map((d) => `
            <li data-npsn="${Peta.esc(d.fitur.properties.npsn)}"
                style="padding: 6px 0; border-top: 1px solid #e5e7eb; cursor: pointer;">
                <strong>${Peta.esc(d.fitur.properties.nama)}</strong><br>
                <small>${d.jarak.toFixed(2)} km (garis lurus)</small>
            </li>`).join('');

        panel.innerHTML = `
            <strong>Koordinat</strong>
            <div style="font-family: monospace;">${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
            <strong style="display: block; margin-top: 8px;">Alamat</strong>
            ${bagianAlamat}
            <strong style="display: block; margin-top: 8px;">
                Jangkauan ${RADIUS_KM} km
            </strong>
            <div><span style="font-size: 20px; font-weight: bold;">${dalam.length}</span>
                sekolah dalam radius ${RADIUS_KM} km</div>
            <small style="color: #6b7280;">${perJenjang}</small>
            <strong style="display: block; margin-top: 8px;">Sekolah terdekat</strong>
            <ul style="list-style: none; margin: 4px 0 0; padding: 0;">${daftar}</ul>`;
    }

    map.on('click', (e) => periksa(e.latlng.lat, e.latlng.lng));

    // ---------- Lokasi saya (navigator.geolocation) ----------
    document.getElementById('btn-lokasi').addEventListener('click', () => {
        const info = document.getElementById('info-lokasi');

        if (!navigator.geolocation) {
            info.textContent = 'Browser tidak mendukung geolokasi.';
            return;
        }

        info.textContent = 'Mencari lokasi...';

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude, accuracy } = pos.coords;
                info.textContent = `Akurasi sekitar ${Math.round(accuracy)} m`;
                map.flyTo([latitude, longitude], 15);
                periksa(latitude, longitude);
            },
            (err) => {
                const pesan = {
                    1: 'izin lokasi ditolak',
                    2: 'lokasi tidak tersedia',
                    3: 'waktu habis',
                };
                info.textContent = 'Gagal: ' + (pesan[err.code] ?? err.message);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    // Klik pada daftar sekolah terdekat: terbang ke sekolah tersebut
    panel.addEventListener('click', (e) => {
        const li = e.target.closest('[data-npsn]');
        if (!li) return;
        const f = semua.find((x) => x.properties.npsn === li.dataset.npsn);
        if (f) {
            L.popup().setLatLng(Peta.latlng(f)).setContent(Peta.popup(f)).openOn(map);
            map.flyTo(Peta.latlng(f), 17);
        }
    });
</script>
@endpush
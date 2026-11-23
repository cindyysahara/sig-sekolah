@extends('layouts.public')
@section('title', 'Peta Sekolah')

@push('styles')
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
@endpush

@section('content')
<div class="toolbar">
    <label>Jenjang
        <select id="filter-jenjang">
            <option value="">Semua jenjang</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
        </select>
    </label>
    <label>Status
        <select id="filter-status">
            <option value="">Semua status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>
    </label>
    <button type="button" id="unduh">Unduh GeoJSON</button>
    <span id="jumlah"></span>
    <span style="color: #6b7280;">
        Ikon lapisan di kanan atas: hidupkan Marker, Klaster, atau Peta panas.
    </span>
</div>
<div id="peta" class="peta"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
    const map = Peta.buat('peta');
    Peta.legenda(map);

    // Tiga lapisan yang dapat dihidupkan dan dimatikan
    const lapMarker = L.layerGroup().addTo(map);
    const lapKlaster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        disableClusteringAtZoom: 17,
        chunkedLoading: true,
    });
    const lapPanas = L.heatLayer([], {
        radius: 30,
        blur: 25,
        maxZoom: 17,
        max: 1.0,
        gradient: {
            0.2: '#2563eb', 0.4: '#06b6d4', 0.6: '#84cc16',
            0.8: '#facc15', 1.0: '#dc2626',
        },
    });

    map.kontrolLapisan.addOverlay(lapMarker, 'Marker');
    map.kontrolLapisan.addOverlay(lapKlaster, 'Klaster');
    map.kontrolLapisan.addOverlay(lapPanas, 'Peta panas');

    let fiturSaatIni = [];

    async function muat() {
        const jenjang = document.getElementById('filter-jenjang').value;
        const status = document.getElementById('filter-status').value;

        try {
            const fitur = await Peta.ambilSekolah(jenjang, status);
            fiturSaatIni = fitur;

            lapMarker.clearLayers();
            lapKlaster.clearLayers();

            // Marker pada tiap lapisan dibuat terpisah (satu marker tidak
            // boleh berada di dua lapisan yang sama-sama tampil)
            fitur.forEach((f) => Peta.marker(f).addTo(lapMarker));
            lapKlaster.addLayers(fitur.map((f) => Peta.marker(f)));
            lapPanas.setLatLngs(fitur.map((f) => [...Peta.latlng(f), 0.35]));

            document.getElementById('jumlah').textContent =
                fitur.length + ' sekolah';

            if (fitur.length > 0) {
                map.fitBounds(L.latLngBounds(fitur.map(Peta.latlng)).pad(0.1));
            }
        } catch (e) {
            console.error(e);
            document.getElementById('jumlah').textContent = 'Gagal memuat data';
        }
    }

    ['filter-jenjang', 'filter-status'].forEach((id) =>
        document.getElementById(id).addEventListener('change', muat));

    // Unduh fitur yang sedang tampil (mengikuti filter yang aktif)
    document.getElementById('unduh').addEventListener('click', () => {
        if (fiturSaatIni.length === 0) {
            alert('Tidak ada data untuk diunduh.');
            return;
        }
        Peta.unduh(fiturSaatIni, 'sekolah.geojson');
    });

    muat();
</script>
@endpush
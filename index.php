<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenStreetMap with Leaflet</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            text-align: center;
        }
        #map {
            width: 100%;
            height: 500px;
            margin: 20px 0;
        }
        .search-box {
            margin-bottom: 20px;
        }
        #search-input {
            width: 300px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #search-button {
            padding: 10px 20px;
            font-size: 16px;
            margin-left: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #search-button:hover {
            background-color: #45a049;
        }
        #nearest-location {
            margin-top: 20px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cari Lokasi dan Jarak Terdekat</h1>
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Cari lokasi...">
            <button id="search-button">Cari</button>
        </div>
        <div id="map"></div>
        <div id="nearest-location"></div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script>
        // Inisialisasi peta
        var map = L.map('map').setView([1.7779878761991732, 114.46747530185209], 4);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Lokasi-lokasi yang sudah ditentukan
        var predefinedLocations = [
            { lat: 3.5302253125369214, lng: 98.6784747382376, name: "SP MEDAN 2 JAMIL" },
            { lat: 3.622463891321936, lng: 98.67883793996386, name: "SP MEDAN 3 LIANTONO" },
            { lat: -0.9655122950369849, lng: 100.40098013029815, name: "SP PADANG HERMAN" },
            { lat: -2.5383828840572176, lng: 140.45875470755072, name: "SP JAYA PURA GUNAWAN" },
            { lat: -0.015422155090093924, lng: 109.3300561689244, name: "SP PONTIANAK FAISAL" },
            { lat: -2.242084513634375, lng: 113.95838280755073, name: "SP PALANGKARAYA FAJAR AHMAD" },
            { lat: -8.596244838033739, lng: 116.08138566098496, name: "SP MATARAM INDRA" },
            { lat: -5.122651302572539, lng: 119.5165712996113, name: "WS MAKASSAR ARFAN" },
            { lat: 3.6290430263358684, lng: 98.5905499996113, name: "SP MEDAN 3 ADITYA" },
            { lat: -1.6117236444670628, lng: 103.57085003823755, name: "SP JAMBI" },
            { lat: 3.3650323687725483, lng: 99.16428610755071, name: "SP MEDAN 2 DEPO TEBING TINGGI" },
            { lat: -5.385056397443081, lng: 105.22844569961131, name: "SP LAMPUNG 4 BASIT" },
            { lat: 1.5023300187463906, lng: 124.88549603029817, name: "SP MANADO 6 YONGKY" },
            { lat: -3.3327274752181024, lng: 114.59181270755073, name: "SP BANJARMASIN 2 AROSYADA" },
            { lat: -1.2038697066392479, lng: 116.97828509961128, name: "WS BALIKPAPAN INDRA" },
            { lat: -5.171549365880873, lng: 119.45006593823759, name: "SP MAKASSAR 2 SUWARNO" },
            { lat: -7.246212251611203, lng: 112.73482433823759, name: "SP SBY 4 FEBRI" },
            { lat: -10.204997618447818, lng: 123.64563603029814, name: "SP KUPANG CHIKO" },
            { lat: -0.4929552462343579, lng: 117.10394856892442, name: "SP DEPO SAMARINDA PRIYANTO" }
        ];

        // Menambahkan marker untuk lokasi-lokasi yang sudah ditentukan
        predefinedLocations.forEach(function(location) {
            L.marker([location.lat, location.lng])
                .bindPopup(location.name)
                .addTo(map);
        });

        // Variabel untuk menyimpan marker pengguna dan layer garis
        var userMarker;
        var lines = [];

        // Fungsi pencarian lokasi
        document.getElementById('search-button').addEventListener('click', function() {
            var address = document.getElementById('search-input').value;

            if (!address) {
                alert("Masukkan nama lokasi untuk melakukan pencarian.");
                return;
            }

            var geocoder = L.Control.Geocoder.nominatim();
            geocoder.geocode(address, function(results) {
                if (results.length > 0) {
                    var location = results[0].center;
                    map.setView(location, 13);

                    if (userMarker) {
                        map.removeLayer(userMarker); // Hapus marker lama jika ada
                    }

                    userMarker = L.marker(location)
                        .bindPopup(results[0].name)
                        .addTo(map);

                    findAndDrawLocations(location);
                } else {
                    alert("Lokasi tidak ditemukan.");
                }
            });
        });

        // Fungsi untuk mencari dan menggambar lokasi-lokasi terdekat
        function findAndDrawLocations(userLocation) {
            var distances = [];

            // Hapus garis lama jika ada
            lines.forEach(line => map.removeLayer(line));
            lines = [];

            predefinedLocations.forEach(function(location) {
                var distance = calculateDistance(userLocation.lat, userLocation.lng, location.lat, location.lng);
                distances.push({ name: location.name, distance: distance, lat: location.lat, lng: location.lng });
            });

            // Urutkan lokasi berdasarkan jarak
            distances.sort(function(a, b) {
                return a.distance - b.distance;
            });

            // Menggambar garis dari lokasi pengguna ke setiap lokasi terdekat
            distances.forEach(function(dist) {
                var line = L.polyline([
                    [userLocation.lat, userLocation.lng],
                    [dist.lat, dist.lng]
                ], {color: 'blue'}).addTo(map);
                lines.push(line);
            });

            // Tampilkan daftar jarak terurut dalam format tabel
            var table = '<table><thead><tr><th>Nama Lokasi</th><th>Jarak (km)</th></tr></thead><tbody>';
            distances.forEach(function(dist) {
                table += `<tr><td>${dist.name}</td><td>${dist.distance.toFixed(2)}</td></tr>`;
            });
            table += '</tbody></table>';
            document.getElementById('nearest-location').innerHTML = table;
        }

        // Fungsi untuk menghitung jarak antara dua titik
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Radius bumi dalam kilometer
            const dLat = degreesToRadians(lat2 - lat1);
            const dLng = degreesToRadians(lng2 - lng1);
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(degreesToRadians(lat1)) * Math.cos(degreesToRadians(lat2)) *
                      Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c; // Jarak dalam kilometer
        }

        // Konversi derajat ke radian
        function degreesToRadians(degrees) {
            return degrees * (Math.PI / 180);
        }
    </script>
</body>
</html>

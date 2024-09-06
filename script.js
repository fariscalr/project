// Inisialisasi peta
var map = L.map('map').setView([3.5302253125369214, 98.6784747382376], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Lokasi-lokasi yang sudah ditentukan
var predefinedLocations = [
    { lat: 3.5302253125369214, lng: 98.6784747382376, name: "SP MEDAN 2 JAMIL" },
    { lat: 3.622463891321936, lng: 98.67883793996386, name: "SP MEDAN 3 LIANTONO" },
    { lat: -0.9655122950369849, lng: 100.40098013029815, name: "SP PADANG HERMAN" }
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
    var lines = [];

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

    // Tampilkan daftar jarak terurut
    var list = '<ul>';
    distances.forEach(function(dist) {
        list += `<li>Lokasi ${dist.name}: ${dist.distance.toFixed(2)} km</li>`;
    });
    list += '</ul>';
    document.getElementById('nearest-location').innerHTML = list;
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

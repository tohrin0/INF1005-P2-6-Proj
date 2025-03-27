<?php
require_once 'inc/session.php';
include 'templates/header.php'; 
?>

<div style="width:100%; height:600px; margin-top:30px;">
    <div id="globeViz" style="width:100%; height:100%;"></div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
<script src="https://unpkg.com/globe.gl"></script>

<script>
// Store countries data globally to access in click events
let countriesData = [];

const globe = Globe()
    .globeImageUrl('//unpkg.com/three-globe/example/img/earth-blue-marble.jpg')
    .bumpImageUrl('//unpkg.com/three-globe/example/img/earth-topology.png')
    .backgroundColor('#000000')
    .labelsData([])
    .labelText('name')
    .labelSize(1.5) // Increased size for better clickability
    .labelDotRadius(0.4) // Slightly increased dot size
    .labelColor(() => 'rgba(255, 255, 255, 0.8)')
    .labelResolution(2)
    // Handle clicks on the globe itself
    .onGlobeClick((coordinates) => {
        const closestCountry = findClosestCountry(coordinates);
        if (closestCountry) {
            redirectToSearch(closestCountry);
        }
    })
    // Handle clicks on the country labels
    .onLabelClick((label) => {
        redirectToSearch(label.name);
    })(document.getElementById('globeViz'));

globe.controls().autoRotate = true;
globe.controls().autoRotateSpeed = 0.5;

// Find closest country to coordinates using haversine distance
function findClosestCountry({lat, lng}) {
    if (!countriesData.length) return null;
    
    let closestCountry = null;
    let minDistance = Infinity;
    
    countriesData.forEach(country => {
        const distance = haversineDistance(lat, lng, country.lat, country.lng);
        if (distance < minDistance) {
            minDistance = distance;
            closestCountry = country.name;
        }
    });
    
    return closestCountry;
}

// Calculate haversine distance between two coordinate points
function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Redirect to search page with country as destination
function redirectToSearch(countryName) {
    // Using today's date as the default departure date
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
    
    // Redirect to search2.php with the country name as destination
    window.location.href = `search2.php?to=${encodeURIComponent(countryName)}&departDate=${formattedDate}`;
}

// Fetch countries data and populate the globe
fetch('https://raw.githubusercontent.com/vasturiano/globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson')
    .then(res => res.json())
    .then(countries => {
        const countryLabels = countries.features.map(feature => {
            // Use centroid as label position
            const coords = feature.geometry.coordinates;
            // Basic centroid calculation for simple polygons
            let lng = 0, lat = 0;
            if (coords && coords.length > 0 && coords[0].length > 0) {
                const points = coords[0];
                for (let i = 0; i < points.length; i++) {
                    lng += points[i][0];
                    lat += points[i][1];
                }
                lng /= points.length;
                lat /= points.length;
            }
            
            return {
                name: feature.properties.NAME,
                lat: lat,
                lng: lng
            };
        });
        
        // Store countries data globally
        countriesData = countryLabels;
        
        // Update globe with labels
        globe.labelsData(countryLabels);
    })
    .catch(error => {
        console.error('Error loading countries:', error);
    });
</script>

<?php include 'templates/footer.php'; ?>
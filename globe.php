<?php include 'templates/header.php'; ?>

<div style="width:100%; height:600px; margin-top:30px;">
    <div id="globeViz" style="width:100%; height:100%;"></div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
<script src="https://unpkg.com/globe.gl"></script>

<script>
const globe = Globe()
    .globeImageUrl('//unpkg.com/three-globe/example/img/earth-blue-marble.jpg')
    .bumpImageUrl('//unpkg.com/three-globe/example/img/earth-topology.png')
    .backgroundColor('#000000')
    .labelsData([])
    .labelText('name')
    .labelSize(1)
    .labelDotRadius(0.3)
    .labelColor(() => 'rgba(255, 255, 255, 0.8)')
    .labelResolution(2)
    .onGlobeClick(({lat, lng}) => {
        window.location.href = `search.php?lat=${lat}&lng=${lng}`;
    })(document.getElementById('globeViz'));

globe.controls().autoRotate = true;
globe.controls().autoRotateSpeed = 0.5;

fetch('https://raw.githubusercontent.com/vasturiano/globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson').then(res => res.json()).then(countries => {
    const countryLabels = countries.features.map(feature => {
        const [lng, lat] = feature.geometry.coordinates[0][0];
        return {
            name: feature.properties.NAME,
            lat: lat,
            lng: lng
        };
    });
    globe.labelsData(countryLabels);
});
</script>

<?php include 'templates/footer.php'; ?>

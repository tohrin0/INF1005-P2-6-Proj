<?php
require_once 'inc/session.php';
include 'templates/header.php'; 
?>

<div class="container mx-auto px-4 py-8">
  <h1 class="text-3xl font-bold mb-6">Interactive World Explorer</h1>
  
  <div class="globe-container relative overflow-hidden rounded-lg shadow-2xl">
    <div id="globeViz" class="w-full h-full"></div>
    
    <div class="absolute top-5 left-5 bg-blue-900 bg-opacity-75 backdrop-blur-sm p-4 rounded-lg text-white z-10 shadow-lg">
      <h2 class="text-xl font-bold mb-1">Interactive World Explorer</h2>
      <p class="text-sm opacity-90">Click and drag to rotate • Click on a country to find flights</p>
    </div>
    
    <div id="loading-overlay" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-90 z-30 text-white">
      <div class="text-center">
        <div class="text-lg mb-3">Loading World Map...</div>
        <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
      </div>
    </div>
    
    <div id="debug-info" class="absolute bottom-5 right-5 bg-black bg-opacity-70 p-2 rounded text-white text-xs z-30 hidden"></div>
  </div>
</div>

<!-- Add the necessary Three.js libraries from cdnjs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Debug element for troubleshooting
  const debugInfo = document.getElementById('debug-info');
  
  // Elements
  const globeContainer = document.querySelector('.globe-container');
  const globeElement = document.getElementById('globeViz');
  const loadingOverlay = document.getElementById('loading-overlay');
  
  // Log debugging info
  function log(msg) {
    console.log(msg);
    debugInfo.textContent += msg + "\n";
    debugInfo.classList.remove('hidden');
  }
  
  // Countries data with airport codes
  const countries = [
    { name: "United States", lat: 37.0902, lng: -95.7129, airportCode: "JFK" },
    { name: "Canada", lat: 56.1304, lng: -106.3468, airportCode: "YYZ" },
    { name: "Brazil", lat: -14.2350, lng: -51.9253, airportCode: "GRU" },
    { name: "United Kingdom", lat: 55.3781, lng: -3.4360, airportCode: "LHR" },
    { name: "France", lat: 46.2276, lng: 2.2137, airportCode: "CDG" },
    { name: "Germany", lat: 51.1657, lng: 10.4515, airportCode: "FRA" },
    { name: "Italy", lat: 41.8719, lng: 12.5674, airportCode: "FCO" },
    { name: "Spain", lat: 40.4637, lng: -3.7492, airportCode: "MAD" },
    { name: "China", lat: 35.8617, lng: 104.1954, airportCode: "PEK" },
    { name: "Japan", lat: 36.2048, lng: 138.2529, airportCode: "HND" },
    { name: "Australia", lat: -25.2744, lng: 133.7751, airportCode: "SYD" },
    { name: "India", lat: 20.5937, lng: 78.9629, airportCode: "DEL" },
    { name: "South Africa", lat: -30.5595, lng: 22.9375, airportCode: "JNB" },
    { name: "Mexico", lat: 23.6345, lng: -102.5528, airportCode: "MEX" },
    { name: "Russia", lat: 61.5240, lng: 105.3188, airportCode: "SVO" }
  ];
  
  // Create a simpler 2D map version that will definitely work as a fallback
  function createSimple2DMap() {
    log("Creating fallback 2D map");
    
    // Hide loading overlay
    loadingOverlay.style.display = 'none';
    
    // Create a simple world map with clickable countries
    globeElement.innerHTML = `
      <div class="relative w-full h-full flex items-center justify-center">
        <div class="w-full h-full bg-gradient-to-b from-blue-900 to-blue-700 p-4 flex items-center justify-center">
          <div class="world-map-container">
            <div class="world-countries grid grid-cols-3 md:grid-cols-5 gap-3">
              ${countries.map(country => `
                <a href="search2.php?to=${encodeURIComponent(country.airportCode || country.name)}&departDate=${new Date().toISOString().split('T')[0]}" 
                  class="country-btn bg-blue-800 hover:bg-blue-600 text-white p-3 rounded shadow-md text-center transition-colors">
                  ${country.name}
                </a>
              `).join('')}
            </div>
          </div>
        </div>
      </div>
    `;
  }
  
  try {
    log("Starting globe initialization");
    
    // Check if Three.js is available
    if (typeof THREE === 'undefined') {
      log("THREE not defined - loading failed");
      createSimple2DMap();
      return;
    }
    
    log("THREE constructor found");
    
    // Scene setup
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, globeContainer.clientWidth / globeContainer.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    
    renderer.setSize(globeContainer.clientWidth, globeContainer.clientHeight);
    renderer.setClearColor(0x000000, 1);
    globeElement.appendChild(renderer.domElement);
    
    // Add ambient light
    const ambientLight = new THREE.AmbientLight(0x404040, 1);
    scene.add(ambientLight);
    
    // Add directional light
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(5, 3, 5);
    scene.add(directionalLight);
    
    // Camera position
    camera.position.z = 4.5;
    
    // Load Earth textures
    const textureLoader = new THREE.TextureLoader();
    
    // Create variables to store our textures
    let earthTexture, bumpTexture;
    
    // Track texture loading state
    let texturesLoaded = 0;
    const totalTextures = 2;
    
    function checkAllTexturesLoaded() {
      texturesLoaded++;
      if (texturesLoaded === totalTextures) {
        // All textures loaded, create the globe
        createGlobe();
      }
    }
    
    // Load Earth color texture (first image)
    earthTexture = textureLoader.load('assets/images/globe/earth-blue-marble.jpg', checkAllTexturesLoaded, undefined, function(err) {
      log("Error loading earth texture: " + err);
      // Use a basic blue color as fallback
      earthTexture = null;
      checkAllTexturesLoaded();
    });
    
    // Load Earth bump texture (second image)
    bumpTexture = textureLoader.load('assets/images/globe/earth-topology.png', checkAllTexturesLoaded, undefined, function(err) {
      log("Error loading bump texture: " + err);
      // Continue without bump map
      bumpTexture = null;
      checkAllTexturesLoaded();
    });
    
    // Globe variables
    let earthMesh, atmosphere;
    const countryLabels = [];
    
    // Function to create a text sprite for country labels
    function createTextSprite(text) {
      // Create canvas
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      
      // Set canvas size (power of 2 for better performance)
      canvas.width = 256;
      canvas.height = 128;
      
      // Clear canvas
      context.clearRect(0, 0, canvas.width, canvas.height);
      
      // Background with rounded corners and semi-transparency
      context.fillStyle = 'rgba(0, 0, 0, 0.6)';
      context.beginPath();
      context.roundRect(0, 0, canvas.width, canvas.height, 16);
      context.fill();
      
      // Add white border
      context.strokeStyle = 'rgba(255, 255, 255, 0.3)';
      context.lineWidth = 2;
      context.beginPath();
      context.roundRect(1, 1, canvas.width-2, canvas.height-2, 15);
      context.stroke();
      
      // Text styles
      context.font = 'bold 36px Arial';
      context.textAlign = 'center';
      context.textBaseline = 'middle';
      
      // Text with outline for better readability
      context.fillStyle = 'white';
      context.fillText(text, canvas.width/2, canvas.height/2);
      
      // Create texture from canvas
      const texture = new THREE.CanvasTexture(canvas);
      
      // Create sprite material
      const spriteMaterial = new THREE.SpriteMaterial({
        map: texture,
        transparent: true,
        opacity: 0.9
      });
      
      // Create sprite
      const sprite = new THREE.Sprite(spriteMaterial);
      sprite.scale.set(1, 0.5, 1);
      
      return sprite;
    }
    
    function createGlobe() {
      // Create Earth sphere with proper materials
      const earthGeometry = new THREE.SphereGeometry(2, 64, 64);
      const earthMaterial = new THREE.MeshPhongMaterial({
        map: earthTexture,
        bumpMap: bumpTexture,
        bumpScale: 0.05,
        specular: new THREE.Color(0x222222),
        shininess: 5
      });
      
      // If textures failed to load, use a basic color
      if (!earthTexture) {
        earthMaterial.color = new THREE.Color(0x1a4d7e);
      }
      
      earthMesh = new THREE.Mesh(earthGeometry, earthMaterial);
      scene.add(earthMesh);
      
      // Create atmosphere glow
      const atmosphereGeometry = new THREE.SphereGeometry(2.1, 64, 64);
      const atmosphereMaterial = new THREE.MeshPhongMaterial({
        color: 0x3a7bd5,
        transparent: true,
        opacity: 0.15,
        side: THREE.BackSide
      });
      atmosphere = new THREE.Mesh(atmosphereGeometry, atmosphereMaterial);
      scene.add(atmosphere);
      
      // Add stars
      const starsGeometry = new THREE.BufferGeometry();
      const starsMaterial = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 0.1,
        transparent: true
      });
      
      // Create stars
      const starsVertices = [];
      for (let i = 0; i < 1000; i++) {
        const x = (Math.random() - 0.5) * 50;
        const y = (Math.random() - 0.5) * 50;
        const z = (Math.random() - 0.5) * 50;
        
        // Make sure stars aren't too close to Earth
        const distance = Math.sqrt(x*x + y*y + z*z);
        if (distance > 10) {
          starsVertices.push(x, y, z);
        }
      }
      
      starsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starsVertices, 3));
      const stars = new THREE.Points(starsGeometry, starsMaterial);
      scene.add(stars);
      
      // Add country labels
      countries.forEach(country => {
        // Convert lat/lng to 3D coordinates
        const lat = country.lat * (Math.PI / 180);
        const lng = -country.lng * (Math.PI / 180);
        
        // Calculate point on sphere
        const radius = 2.1; // Slightly above Earth surface
        const x = radius * Math.cos(lat) * Math.cos(lng);
        const y = radius * Math.sin(lat);
        const z = radius * Math.cos(lat) * Math.sin(lng);
        
        // Create text sprite for the country name
        const labelSprite = createTextSprite(country.name);
        labelSprite.position.set(x, y, z);
        labelSprite.center.set(0.5, 0.5);
        
        // Make sprite scale smaller and adjust for distance
        labelSprite.scale.set(0.5, 0.25, 1);
        
        // Store country data with the sprite
        labelSprite.userData = country;
        
        // Add to scene and tracking array
        scene.add(labelSprite);
        countryLabels.push(labelSprite);
      });
      
      // Hide loading overlay
      loadingOverlay.style.display = 'none';
      
      // Start animation
      animate();
    }
    
    // Mouse controls
    let isDragging = false;
    let previousMousePosition = { x: 0, y: 0 };
    const rotationSpeed = 0.007;
    
    // Setup raycasting for country selection
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();
    let hoveredLabel = null;
    
    // Handle mouse down
    globeElement.addEventListener('mousedown', (e) => {
      isDragging = true;
      previousMousePosition = {
        x: e.clientX,
        y: e.clientY
      };
    });
    
    // Handle mouse move
    globeElement.addEventListener('mousemove', (e) => {
      // Calculate normalized device coordinates
      const rect = renderer.domElement.getBoundingClientRect();
      mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
      
      if (isDragging) {
        // Calculate how much the mouse has moved
        const deltaMove = {
          x: e.clientX - previousMousePosition.x,
          y: e.clientY - previousMousePosition.y
        };
        
        // Rotate the globe based on mouse movement
        earthMesh.rotation.y += deltaMove.x * rotationSpeed;
        earthMesh.rotation.x += deltaMove.y * rotationSpeed;
        atmosphere.rotation.y += deltaMove.x * rotationSpeed;
        atmosphere.rotation.x += deltaMove.y * rotationSpeed;
        
        // Update labels to match Earth rotation
        countryLabels.forEach(label => {
          label.position.applyAxisAngle(new THREE.Vector3(0, 1, 0), deltaMove.x * rotationSpeed);
          label.position.applyAxisAngle(new THREE.Vector3(1, 0, 0), deltaMove.y * rotationSpeed);
        });
        
        // Update previous position
        previousMousePosition = {
          x: e.clientX,
          y: e.clientY
        };
      } else {
        // Raycast to find if we're hovering over a label
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(countryLabels);
        
        if (intersects.length > 0) {
          const selectedLabel = intersects[0].object;
          
          if (hoveredLabel !== selectedLabel) {
            // Reset previous hover state if any
            if (hoveredLabel) {
              hoveredLabel.scale.set(0.5, 0.25, 1);
              hoveredLabel.material.opacity = 0.9;
            }
            
            hoveredLabel = selectedLabel;
            
            // Highlight the label
            hoveredLabel.scale.set(0.6, 0.3, 1);
            hoveredLabel.material.opacity = 1;
          }
        } else {
          if (hoveredLabel !== null) {
            // Reset hover state
            hoveredLabel.scale.set(0.5, 0.25, 1);
            hoveredLabel.material.opacity = 0.9;
            hoveredLabel = null;
          }
        }
      }
    });
    
    // Handle mouse up
    window.addEventListener('mouseup', () => {
      isDragging = false;
    });
    
    // Handle click on labels
    globeElement.addEventListener('click', (e) => {
      if (!isDragging) {
        // Calculate normalized device coordinates
        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
        
        // Raycast to find if we're clicking a label
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(countryLabels);
        
        if (intersects.length > 0) {
          const selectedLabel = intersects[0].object;
          const country = selectedLabel.userData;
          
          // Add visual feedback
          const originalOpacity = selectedLabel.material.opacity;
          selectedLabel.material.opacity = 1;
          
          setTimeout(() => {
            selectedLabel.material.opacity = originalOpacity;
          }, 300);
          
          redirectToSearch(country);
        }
      }
    });
    
    // Handle window resize
    window.addEventListener('resize', () => {
      camera.aspect = globeContainer.clientWidth / globeContainer.clientHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(globeContainer.clientWidth, globeContainer.clientHeight);
    });
    
    // Animation loop
    function animate() {
      requestAnimationFrame(animate);
      
      if (!isDragging && earthMesh) {
        // Slow auto-rotation when not dragging
        earthMesh.rotation.y += 0.001;
        atmosphere.rotation.y += 0.001;
        
        // Update labels positions with the rotation
        countryLabels.forEach(label => {
          label.position.applyAxisAngle(new THREE.Vector3(0, 1, 0), 0.001);
        });
      }
      
      // Make labels always face the camera
      countryLabels.forEach(label => {
        label.lookAt(camera.position);
      });
      
      renderer.render(scene, camera);
    }
    
  } catch (error) {
    log("Fatal error: " + error.message);
    createSimple2DMap();
  }
  
  // Redirect to search2.php
  function redirectToSearch(countryData) {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    const destination = countryData.airportCode || countryData.name;
    window.location.href = `search2.php?to=${encodeURIComponent(destination)}&departDate=${formattedDate}`;
  }
});
</script>

<style>
  .globe-container {
    width: 100%;
    height: 70vh;
    background: linear-gradient(to bottom, #000000, #001e3c);
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
    margin-top: 30px;
  }
  
  #globeViz {
    width: 100%;
    height: 100%;
  }
  
  /* Remove scrollbars if they appear */
  #globeViz canvas {
    display: block;
  }
</style>

<?php include 'templates/footer.php'; ?>
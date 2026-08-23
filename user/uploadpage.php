<?php
session_start();
// include "../db.php"; 

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') === 'admin') {
    // header("Location: ../user/LogIn.php");
    // exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Issue | AI City Guardian</title>
    
    <link rel="stylesheet" href="/css/style.css"> 
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="nav-logo">
                <h2 class="logo-text">AI City Guardian</h2>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active">
                <a href="upload.php" class="sidebar-link">
                    <i class='bx bx-plus-circle'></i>
                    <span>New Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../user/userpage.php" class="sidebar-link">
                    <i class='bx bx-file'></i>
                    <span>My Submissions</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link">
                    <i class='bx bx-cog'></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <ul class="sidebar-menu">
                <li class="sidebar-item admin-menu-item">
                     <a href="#" class="sidebar-link">
                        <i class='bx bxs-user-circle'></i>
                        <span>User</span>
                    </a>
                    <div class="logout-dropdown">
                        <a href="../user/logout.php" class="logout-btn">
                            <i class='bx bx-log-out'></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="main-header">
            <div class="header-title">
                <h1>Submit New Issue</h1>
                <p>Report a problem in your community</p>
            </div>
        </header>

        <div class="caseReviewBox" style="margin-top: 0; padding: 40px; background: #1a224f;">
            
            <form id="reportForm" action="../report/upload.php" method="POST" enctype="multipart/form-data">
                
                <h2 style="text-align: center; margin-bottom: 20px;">Upload Image</h2>
                <label for="input-file" id="drop-area">
                    <input type="file" name="image" accept="image/*" id="input-file" hidden>
                    <div id="img-view">
                        <i class='bx bx-cloud-upload' style="font-size: 50px; margin-bottom: 10px; color: #fff;"></i>
                        <p>Drag & drop or click to upload</p>
                        <span>Supports JPG, PNG</span>
                    </div>
                </label>
                
                <button type="button" id="analyzeButton" class="action-btn ai-btn">
                    <i class='bx bxs-magic-wand'></i> Analyze Image with AI
                </button>
                <p id="analyzeMessage" class="message-text" style="color: #10b981;"></p>

                <h2 style="text-align: center; margin-top: 40px; margin-bottom: 10px;">Location</h2>
                <input type="text" id="location" name="location" class="input-box" placeholder="Enter issue location" required>
                
                <div class="location-buttons">
                    <button type="button" id="autoLocateBtn" class="action-btn">
                        <i class='bx bx-current-location'></i> Use My Location
                    </button>
                    <button type="button" id="mapButton" class="action-btn">
                        <i class='bx bx-map-alt'></i> Google Maps
                    </button>
                </div>
                
                <p id="mapMessage" class="message-text" style="color: rgba(255,255,255,0.7);"></p>
                <div id="locationMap"></div>
                
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">

                <h2 style="text-align: center; margin-top: 40px; margin-bottom: 10px;">Description</h2>
                <textarea name="description" id="description" placeholder="Click 'Analyze Image with AI', then review or edit the description here..." required></textarea>

                <button type="submit" class="submit-btn">Submit Report</button>
                <p id="message" class="message-text"></p>
            </form>
            
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Your JavaScript is compatible and re-pasted here for completeness
        const dropArea = document.getElementById("drop-area");
        const inputFile = document.getElementById("input-file");
        const imgView = document.getElementById("img-view");
        
        function uploadImage() {
            if (!inputFile.files[0]) return;
            let imgLink = URL.createObjectURL(inputFile.files[0]);
            imgView.style.backgroundImage = `url(${imgLink})`;
            imgView.style.border = 0;
            imgView.innerHTML = ""; // Clears the icon and text
        }
        
        inputFile.addEventListener("change", uploadImage);
        dropArea.addEventListener("dragover", (e) => e.preventDefault());
        dropArea.addEventListener("drop", (e) => {
            e.preventDefault();
            inputFile.files = e.dataTransfer.files;
            uploadImage();
        });
        
        const locationInput = document.getElementById("location");
        const mapButton = document.getElementById("mapButton");
        const mapMessage = document.getElementById("mapMessage");
        const autoLocateBtn = document.getElementById("autoLocateBtn");
        const latitudeInput = document.getElementById("latitude");
        const longitudeInput = document.getElementById("longitude");
        const locationMap = document.getElementById("locationMap");
        
        let map = null;
        let marker = null;
        
        function initMap() {
            locationMap.style.display = 'block'; // Make sure map container is visible
            map = L.map("locationMap").setView([3.1390, 101.6869], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            map.on('click', function(e) {
                const { lat, lng } = e.latlng;
                latitudeInput.value = lat;
                longitudeInput.value = lng;
                if (!marker) {
                    marker = L.marker([lat, lng]).addTo(map);
                } else {
                    marker.setLatLng([lat, lng]);
                }
                mapMessage.style.color = "#10b981";
                mapMessage.textContent = "Location selected on map.";
                reverseGeocode(lat, lng);
            });
            setTimeout(() => map.invalidateSize(), 300);
        }
        
        // ... (rest of your existing JavaScript logic)
        async function reverseGeocode(lat,lng){ mapMessage.style.color="#fff"; mapMessage.textContent="Looking up address..."; try{ const geoRes=await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,{headers:{"Accept-Language":"en"}}); if(!geoRes.ok)throw new Error("Nominatim error"); const geoData=await geoRes.json(); if(geoData.display_name){ locationInput.value=geoData.display_name; if(marker){ marker.bindPopup(geoData.display_name).openPopup(); } mapMessage.style.color="#10b981"; mapMessage.textContent="Location detected successfully."; }else{ mapMessage.textContent="Coordinates captured."; } }catch(err){ mapMessage.textContent="Coordinates captured, but address lookup failed."; } }
        mapButton.addEventListener("click", function() { const query = locationInput.value.trim(); if (query) { window.open("https://www.google.com/maps?q=" + encodeURIComponent(query) + "&z=18", "_blank"); } else { mapMessage.style.color = "#ff4d4d"; mapMessage.textContent = "Please enter a location first."; } });
        autoLocateBtn.addEventListener("click", function() { if (!navigator.geolocation) { mapMessage.style.color = "#ff4d4d"; mapMessage.textContent = "Geolocation is not supported by your browser."; return; } mapMessage.style.color = "#fff"; mapMessage.textContent = "Getting your location..."; navigator.geolocation.getCurrentPosition(async function(position) { const lat = position.coords.latitude; const lng = position.coords.longitude; latitudeInput.value = lat; longitudeInput.value = lng; map.setView([lat, lng], 17); if (!marker) { marker = L.marker([lat, lng]).addTo(map); } else { marker.setLatLng([lat, lng]); } await reverseGeocode(lat, lng); }, function(error) { mapMessage.style.color = "#ff4d4d"; mapMessage.textContent = "Could not get location: " + error.message; }, { enableHighAccuracy: true, timeout: 10000 }); });
        window.addEventListener("load", initMap);
    </script>
</body>
</html>

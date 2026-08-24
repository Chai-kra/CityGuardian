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

    <style>
        /* ---- Two-column layout for the report form ---- */
        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "location image"
                "description description";
            gap: 40px;
            align-items: start;
        }

        .grid-location { grid-area: location; }
        .grid-image    { grid-area: image; }
        .grid-description { grid-area: description; }

        /* Make sure the map never exceeds its column width */
        #locationMap {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Mobile: stack Location -> Image -> Description */
        @media (max-width: 768px) {
            .report-grid {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "location"
                    "image"
                    "description";
                gap: 0;
            }
        }
    </style>
    
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

                <div class="report-grid">

                    <!-- LOCATION (left on desktop, first on mobile) -->
                    <div class="grid-location">
                        <h2 style="text-align: center; margin-bottom: 10px;">Location</h2>
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
                    </div>

                    <!-- IMAGE UPLOAD (right on desktop, second on mobile) -->
                    <div class="grid-image">
                        <h2 style="text-align: center; margin-bottom: 20px;">Upload Image</h2>
                        <label for="input-file" id="drop-area">
                            <input type="file" name="image" accept="image/*" id="input-file" hidden>
                            <div id="img-view" style="position: relative;">
                                <div id="uploadContent" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; width: 100%;">
                                    <i class='bx bx-cloud-upload' style="font-size: 50px; margin-bottom: 10px; color: #fff;"></i>
                                    <p>Drag & drop or click to upload</p>
                                    <span>Supports JPG, PNG</span>
                                </div>
                                <img id="previewImage" alt="Preview" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; border-radius: 18px;">
                            </div>
                        </label>

                        <button type="button" id="analyzeButton" class="action-btn ai-btn">
                            <i class='bx bxs-magic-wand'></i> Analyze Image with AI
                        </button>
                        <p id="analyzeMessage" class="message-text" style="color: #10b981;"></p>
                    </div>

                    <!-- DESCRIPTION (spans full width on desktop, last on mobile) -->
                    <div class="grid-description">
                        <h2 style="text-align: center; margin-top: 0; margin-bottom: 10px;">Description</h2>
                        <textarea name="ai_description" id="description" placeholder="Click 'Analyze Image with AI', then review or edit the description here..." required></textarea>
                    </div>

                </div>

                <button type="submit" class="submit-btn">Submit Report</button>
                <p id="message" class="message-text"></p>
            </form>
            
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // DOM Elements
        const dropArea = document.getElementById("drop-area");
        const inputFile = document.getElementById("input-file");
        const imgView = document.getElementById("img-view");
        const uploadContent = document.getElementById("uploadContent");
        const previewImage = document.getElementById("previewImage");
        
        const analyzeButton = document.getElementById("analyzeButton");
        const analyzeMessage = document.getElementById("analyzeMessage");
        const descriptionBox = document.getElementById("description");
        
        const locationInput = document.getElementById("location");
        const mapButton = document.getElementById("mapButton");
        const mapMessage = document.getElementById("mapMessage");
        const autoLocateBtn = document.getElementById("autoLocateBtn");
        const latitudeInput = document.getElementById("latitude");
        const longitudeInput = document.getElementById("longitude");
        const locationMap = document.getElementById("locationMap");
        
        const reportForm = document.getElementById("reportForm");
        const message = document.getElementById("message");

        // --- 1. Image Upload Preview ---
        function uploadImage() {
            if (!inputFile.files[0]) return;
            let imgLink = URL.createObjectURL(inputFile.files[0]);
            
            previewImage.src = imgLink;
            previewImage.style.display = "block";
            uploadContent.style.display = "none";
            imgView.style.border = "none";
        }
        
        inputFile.addEventListener("change", uploadImage);
        dropArea.addEventListener("dragover", (e) => e.preventDefault());
        dropArea.addEventListener("drop", (e) => {
            e.preventDefault();
            inputFile.files = e.dataTransfer.files;
            uploadImage();
        });

        // --- 2. AI Image Analysis ---
        analyzeButton.addEventListener("click", function() {
            if (!inputFile.files[0]) {
                analyzeMessage.textContent = "Please upload an image first.";
                analyzeMessage.style.color = "#ff4d4d";
                return;
            }
            analyzeMessage.textContent = "Analyzing image...";
            analyzeMessage.style.color = "rgba(255,255,255,0.7)";
            analyzeButton.disabled = true;
            analyzeButton.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Analyzing...";
            descriptionBox.value = "";
            descriptionBox.disabled = true;

            const formData = new FormData();
            formData.append("image", inputFile.files[0]);
            formData.append("location", locationInput.value);

            fetch("../report/analyze.php", { method: "POST", body: formData })
            .then(response => response.json())
            .then(result => {
                descriptionBox.disabled = false;
                analyzeButton.disabled = false;
                analyzeButton.innerHTML = "<i class='bx bxs-magic-wand'></i> Analyze Image with AI";
                
                if (result.success) {
                    descriptionBox.value = result.description || "";
                    analyzeMessage.textContent = "AI description generated. You can edit it before submitting.";
                    analyzeMessage.style.color = "#10b981";
                } else {
                    analyzeMessage.textContent = "Could not generate description. Please describe the issue manually.";
                    analyzeMessage.style.color = "#ff4d4d";
                }
            })
            .catch(error => {
                descriptionBox.disabled = false;
                analyzeButton.disabled = false;
                analyzeButton.innerHTML = "<i class='bx bxs-magic-wand'></i> Analyze Image with AI";
                analyzeMessage.textContent = "Error analyzing image.";
                analyzeMessage.style.color = "#ff4d4d";
                console.error("Fetch error:", error);
            });
        });

        // --- 3. Map Initialization & Logic ---
        let map = null;
        let marker = null;
        
        function initMap() {
            locationMap.style.display = 'block';
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

        async function reverseGeocode(lat, lng) {
            mapMessage.style.color = "rgba(255,255,255,0.7)";
            mapMessage.textContent = "Looking up address...";
            try {
                const geoRes = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {headers: {"Accept-Language": "en"}});
                if (!geoRes.ok) throw new Error("Nominatim error");
                const geoData = await geoRes.json();
                
                if (geoData.display_name) {
                    locationInput.value = geoData.display_name;
                    if (marker) { marker.bindPopup(geoData.display_name).openPopup(); }
                    mapMessage.style.color = "#10b981";
                    mapMessage.textContent = "Location detected successfully.";
                } else {
                    mapMessage.textContent = "Coordinates captured.";
                }
            } catch (err) {
                mapMessage.textContent = "Coordinates captured, but address lookup failed.";
                console.error(err);
            }
        }

        let locationTimer = null;
        locationInput.addEventListener("input", function() {
            clearTimeout(locationTimer);
            const query = locationInput.value.trim();
            if (query.length < 3) return;
            
            locationTimer = setTimeout(async function() {
                mapMessage.style.color = "rgba(255,255,255,0.7)";
                mapMessage.textContent = "Looking up location...";
                try {
                    const geoRes = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1&countrycodes=my`, {headers: {"Accept-Language": "en"}});
                    if (!geoRes.ok) throw new Error("Nominatim error");
                    const results = await geoRes.json();
                    
                    if (!results || results.length === 0) {
                        mapMessage.textContent = "Couldn't find that location.";
                        mapMessage.style.color = "#ff4d4d";
                        return;
                    }
                    
                    const lat = parseFloat(results[0].lat);
                    const lng = parseFloat(results[0].lon);
                    latitudeInput.value = lat;
                    longitudeInput.value = lng;
                    map.setView([lat, lng], 16);
                    
                    if (!marker) {
                        marker = L.marker([lat, lng]).addTo(map);
                    } else {
                        marker.setLatLng([lat, lng]);
                    }
                    marker.bindPopup(results[0].display_name).openPopup();
                    mapMessage.style.color = "#10b981";
                    mapMessage.textContent = "Location matched on map.";
                } catch (err) {
                    mapMessage.textContent = "Location lookup failed.";
                    mapMessage.style.color = "#ff4d4d";
                    console.error(err);
                }
            }, 800);
        });

        mapButton.addEventListener("click", function() { 
            const query = locationInput.value.trim(); 
            if (query) { 
                window.open("https://www.google.com/maps?q=" + encodeURIComponent(query) + "&z=18", "_blank"); 
            } else { 
                mapMessage.style.color = "#ff4d4d"; 
                mapMessage.textContent = "Please enter a location first."; 
            } 
        });

        autoLocateBtn.addEventListener("click", function() { 
            if (!navigator.geolocation) { 
                mapMessage.style.color = "#ff4d4d"; 
                mapMessage.textContent = "Geolocation is not supported by your browser."; 
                return; 
            } 
            mapMessage.style.color = "rgba(255,255,255,0.7)"; 
            mapMessage.textContent = "Getting your location..."; 
            
            navigator.geolocation.getCurrentPosition(async function(position) { 
                const lat = position.coords.latitude; 
                const lng = position.coords.longitude; 
                latitudeInput.value = lat; 
                longitudeInput.value = lng; 
                map.setView([lat, lng], 17); 
                
                if (!marker) { 
                    marker = L.marker([lat, lng]).addTo(map); 
                } else { 
                    marker.setLatLng([lat, lng]); 
                } 
                await reverseGeocode(lat, lng); 
            }, function(error) { 
                mapMessage.style.color = "#ff4d4d"; 
                mapMessage.textContent = "Could not get location: " + error.message; 
            }, { enableHighAccuracy: true, timeout: 10000 }); 
        });

        // --- 4. Form Submission ---
        reportForm.addEventListener("keydown", function(e) {
            if (e.key === "Enter" && (e.target.tagName === "INPUT" || e.target.tagName === "SELECT")) {
                e.preventDefault();
            }
        });

        reportForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(reportForm);

            const submitBtn = reportForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Submitting...";
            
            fetch("../report/upload.php", { method: "POST", body: formData })
            .then(response => response.text())
            .then(data => {
                if (data.includes("successfully")) {
                    message.style.color = "#10b981";
                    message.textContent = data;
                    
                    // Reset Form UI
                    reportForm.reset();
                    previewImage.src = "";
                    previewImage.style.display = "none";
                    uploadContent.style.display = "flex";
                    imgView.style.border = ""; // Restores the dashed border
                    
                    mapMessage.textContent = "";
                    analyzeMessage.textContent = "";
                    latitudeInput.value = "";
                    longitudeInput.value = "";
                    
                    if (marker) {
                        map.removeLayer(marker);
                        marker = null;
                    }
                    map.setView([3.1390, 101.6869], 12);
                    
                    setTimeout(() => {
                        window.location.href = "../user/userpage.php";
                    }, 1500);
                } else {
                    message.style.color = "#ff4d4d";
                    message.textContent = data;
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit Report";
                }
            })
            .catch(error => {
                console.error(error);
                message.style.color = "#ff4d4d";
                message.textContent = "An error occurred while submitting the report.";
                submitBtn.disabled = false;
                submitBtn.textContent = "Submit Report";
            });
        });

        window.addEventListener("load", initMap);
    </script>
</body>
</html>
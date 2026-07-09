<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title> Citizen Report Portal </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    
<style>
.image{
	width: 100%;
    min-height: 100yh;
    display: flex;
    justify-content: center;
}

#drop-area{
	width: 500px;
    height: 300px;
    padding: 30px;
    text-align: center;
    border-radius: 20px;
}

#img-view{
	width: 100%;
    height: 100%;
    border-radius: 20px;
    border: 2px dashed #bbb5ff;
    background: #f7f8ff;
    background-position: center;
    background-size: cover;
}

#img-view img{
	width: 100px;
    margin-top: 25px;
}

#img-view span{
	display: block;
    font-size: 12px;
    color: #777;
    margin-top: 15px;
}

#description{
	width: 500px;
    max-width: 1000px;
    min-height: 50px;
    max-height: 100px;
}

#location{
	width: 500px;
    max-width: 1000px;
    padding: 8px;
    margin-bottom: 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

#locationMap {
    height: 250px;
    width: 500px;
    margin: 10px auto;
    border-radius: 10px;
    display: none;
}
    
	

</style>
    
</head>
<body style="text-align:center;">
	<h1>Smart Urban Issue Detection & Response System</h1>
    <h2>Submit a new issue</h2>
    <form
        id="reportForm"
        action="upload.php"
        method="POST"
        enctype="multipart/form-data"
    >

    <label for="location" style="display: block; margin: 10px 0 5px 0;">Location</label>
    <input type="text" id="location" name="location" placeholder="Enter your location here"><br>
    <button type="button" id="autoLocateBtn" style="margin: 8px 0px 12px 0; padding: 7px 14px; font-size: 13px; background-color: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer;">📍 Use My Current Location</button>
    <button type="button" id="mapButton" style="margin: 8px 0 12px 0; padding: 7px 14px; font-size: 13px; background-color: #2f80ed; color: white; border: none; border-radius: 5px; cursor: pointer;">Open in Google Maps</button>
    <p id="mapMessage" style="margin: 0 0 10px 0; color: #444; font-size: 13px;"></p>

    <div id="locationMap"></div>

    <input type="hidden" id="latitude" name="latitude">
    <input type="hidden" id="longitude" name="longitude">

    <label for="description">Description</label><br>
    <div style="display: inline-block; position: relative;">
        <textarea name="description" id="description" placeholder="Further details to help field crews find and fix the issue faster..."></textarea>
    </div>

    <div class="image">
	    <label for="input-file" id="drop-area">
    	    <input type="file" name="image" accept="image/*" id="input-file" hidden>
            <div id="img-view">
        	    <img src="icon.png">
                <p>Drag and drop or click here<br>to upload image</p>
                <span>Upload any images from device</span>
            </div>
        </label>
    </div>

    <button type="submit" style="display: block; margin: 10px auto 0 auto; padding: 8px 20px; font-size: 14px; background-color: #5555ff; color: white; border: none; border-radius: 5px; cursor: pointer;">Submit Report</button>
    <p id="message" style="margin-top: 10px; color: green; font-weight: bold;"></p>
    </form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
const dropArea = document.getElementById("drop-area");
const inputFile = document.getElementById("input-file");
const imageView = document.getElementById("img-view");
const reportForm = document.getElementById("reportForm");
const message = document.getElementById("message");
const locationInput = document.getElementById("location");
const mapButton = document.getElementById("mapButton");
const mapMessage = document.getElementById("mapMessage");
const autoLocateBtn = document.getElementById("autoLocateBtn");
const latitudeInput = document.getElementById("latitude");
const longitudeInput = document.getElementById("longitude");
const locationMapDiv = document.getElementById("locationMap");

let map = null;
let marker = null;

inputFile.addEventListener("change", uploadImage);
    
function uploadImage(){
    let imgLink = URL.createObjectURL(inputFile.files[0]);
    imageView.style.backgroundImage = `url(${imgLink})`;
    imageView.textContent = "";
    imageView.style.border = 0;
}

dropArea.addEventListener("dragover", function(e){
	e.preventDefault();
});
dropArea.addEventListener("drop", function(e){
	e.preventDefault();
    inputFile.files = e.dataTransfer.files;
    uploadImage();
});

mapButton.addEventListener("click", function(){
    const query = locationInput.value.trim();
    if (query) {
        mapMessage.textContent = "Opening Google Maps...";
        const url = `https://www.google.com/maps?q=${encodeURIComponent(query)}&z=18`;
        window.open(url, "_blank");
    } else {
        mapMessage.textContent = "Please enter a location first.";
    }
});

autoLocateBtn.addEventListener("click", function(){
    if (!navigator.geolocation) {
        mapMessage.textContent = "Geolocation is not supported by your browser.";
        return;
    }

    mapMessage.style.color = "#444";
    mapMessage.textContent = "Getting your location...";

    const submitBtn = reportForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = "Please wait...";

    navigator.geolocation.getCurrentPosition(
        async function(position){
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            latitudeInput.value = lat;
            longitudeInput.value = lng;

            // Show mini map
            locationMapDiv.style.display = "block";
            if (!map) {
                map = L.map('locationMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                marker = L.marker([lat, lng]).addTo(map);
            } else {
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            }
            setTimeout(function() { map.invalidateSize(); }, 100);

            // Reverse geocode to fill the text field
            mapMessage.textContent = "Looking up address...";
            try {
                const geoRes = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                    { headers: { 'Accept-Language': 'en' } }
                );
                if (!geoRes.ok) throw new Error(`Nominatim returned status ${geoRes.status}`);
            
                const geoData = await geoRes.json();

                if (geoData.display_name) {
                    locationInput.value = geoData.display_name;
                    marker.bindPopup(geoData.display_name).openPopup();
                    mapMessage.style.color = "#27ae60";
                    mapMessage.textContent = "Location detected — feel free to edit it above.";
                } else {
                    mapMessage.textContent = "Got coordinates, but couldn't resolve an address. You can type it manually.";
                }
            } catch (err) {
                mapMessage.textContent = "Coordinates captured, but address lookup failed. You can type it manually.";
                console.error(err);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = "Submit Report";
        },
        function(error){
            mapMessage.style.color = "red";
            mapMessage.textContent = "Could not get location: " + error.message;

            submitBtn.disabled = false;
            submitBtn.textContent = "Submit Report";
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
});

async function lookupLocationFromText(query) {
    if (!query || query.trim() === "") return;
 
    mapMessage.style.color = "#444";
    mapMessage.textContent = "Looking up location...";
 
    try {
        const geoRes = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1&countrycodes=my`,
            { headers: { 'Accept-Language': 'en' } }
        );
        if (!geoRes.ok) throw new Error(`Nominatim returned status ${geoRes.status}`);
 
        const results = await geoRes.json();
        if (!results || results.length === 0) {
            mapMessage.style.color = "#444";
            mapMessage.textContent = "Couldn't find that location on the map.";
            return;
        }
 
        const lat = parseFloat(results[0].lat);
        const lng = parseFloat(results[0].lon);
 
        latitudeInput.value = lat;
        longitudeInput.value = lng;
 
        locationMapDiv.style.display = "block";
        if (!map) {
            map = L.map('locationMap').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);
        }
        setTimeout(function() { map.invalidateSize(); }, 100);
 
        mapMessage.style.color = "#27ae60";
        mapMessage.textContent = "Location matched on map.";
 
    } catch (err) {
        mapMessage.textContent = "Location lookup failed.";
        console.error(err);
    }
}

locationInput.addEventListener("blur", function(){
    lookupLocationFromText(locationInput.value);
});

reportForm.addEventListener("keydown", function(e){
    if (e.key === "Enter" && (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA" || e.target.tagName === "SELECT")) {
        e.preventDefault();
    }
});

reportForm.addEventListener("submit", function(e){

    e.preventDefault();

    const formData = new FormData(reportForm);

    if(inputFile.files[0]){
        formData.append("image", inputFile.files[0]);
    }

    fetch("upload.php",{
        method:"POST",
        body:formData
    })
    .then(response => response.text())
    .then(data => {

        message.textContent = data;

        reportForm.reset();

        imageView.style.backgroundImage = "none";
        imageView.style.border = "2px dashed #bbb5ff";
        imageView.innerHTML =
        '<img src="icon.png"><p>Drag and drop or click here<br>to upload image</p><span>Upload any images from device</span>';

        mapMessage.textContent = "";
        locationMapDiv.style.display = "none";

    })
    .catch(error => {

        message.style.color = "red";
        message.textContent = "Error submitting report.";

        console.log(error);

    });

});
    
</script>

</body>
</html>
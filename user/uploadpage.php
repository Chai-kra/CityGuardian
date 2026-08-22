<?php
session_start();
include "../db.php";
if(!isset($_SESSION['id'])||$_SESSION['role']==='admin'){
header("Location: ../user/LogIn.php");
exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>New Issue | AI City Guardian</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f7f8fa;color:#1f2937}
.dashboard{display:flex;min-height:100vh}
.sidebar{width:220px;background:white;border-right:1px solid #e5e7eb;padding:20px 15px;position:fixed;top:0;left:0;bottom:0}
.logo{display:flex;align-items:center;gap:10px;padding:5px 10px 25px}
.logo-icon{width:32px;height:32px;border-radius:8px;background:#1769d2;color:white;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold}
.logo-text h2{margin:0;font-size:16px;color:#1f2937}
.logo-text p{margin:2px 0 0;font-size:11px;color:#8a929e}
.nav-title{font-size:11px;color:#9ca3af;padding:10px;text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 12px;margin:4px 0;border-radius:7px;color:#5f6875;text-decoration:none;font-size:14px}
.nav-item:hover{background:#f1f5f9}
.nav-item.active{background:#1769d2;color:white}
.nav-icon{width:20px;text-align:center}
.main{margin-left:220px;width:calc(100% - 220px)}
.topbar{height:70px;background:white;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;padding:0 28px}
.top-title h1{margin:0;font-size:21px}
.top-title p{margin:4px 0 0;font-size:12px;color:#8a929e}
.top-actions{display:flex;align-items:center;gap:10px}
.search-box{width:210px;height:38px;border:1px solid #e1e5ea;border-radius:6px;display:flex;align-items:center;padding:0 12px;background:white}
.search-box span{color:#9ca3af;margin-right:8px}
.search-box input{border:none;outline:none;width:100%;font-size:13px}
.top-button{height:38px;padding:0 14px;border:1px solid #dfe3e8;border-radius:6px;background:white;color:#4b5563;cursor:pointer;font-size:13px}
.top-button:hover{background:#f5f6f8}
.top-button.primary{background:#1769d2;border-color:#1769d2;color:white}
.notification{width:36px;height:36px;display:flex;align-items:center;justify-content:center;position:relative;font-size:20px}
.notification-badge{position:absolute;top:2px;right:0;width:17px;height:17px;border-radius:50%;background:#e34b4b;color:white;font-size:10px;display:flex;justify-content:center;align-items:center}
.content-area{padding:28px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.page-header h2{margin:0;font-size:22px}
.page-header p{margin:6px 0 0;color:#8a929e;font-size:13px}
.form-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}
.card{background:white;border:1px solid #e5e7eb;border-radius:9px;padding:22px}
.card-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.card-title h3{margin:0;font-size:16px}
.card-title span{font-size:12px;color:#9ca3af}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;font-weight:bold;margin-bottom:7px;color:#374151}
.input,textarea{width:100%;border:1px solid #d9dee5;border-radius:6px;padding:11px 12px;font-size:13px;outline:none;background:white;font-family:inherit}
.input:focus,textarea:focus{border-color:#1769d2}
textarea{min-height:120px;resize:vertical}
.location-buttons{display:flex;gap:8px;margin-top:8px}
.small-button{border:none;border-radius:5px;padding:8px 12px;cursor:pointer;font-size:12px}
.location-button{background:#20a968;color:white}
.maps-button{background:#1769d2;color:white}
#mapMessage{margin:8px 0;font-size:12px;color:#6b7280}
#locationMap{width:100%;height:280px;border-radius:7px;display:block;margin-top:10px;border:1px solid #d9dee5;overflow:hidden}
.upload-area{width:100%;min-height:230px;border:2px dashed #cbd5e1;border-radius:8px;background:#fafbfc;display:flex;justify-content:center;align-items:center;text-align:center;cursor:pointer;transition:.2s}
.upload-area:hover{background:#f5f8fc;border-color:#1769d2}
.upload-content{padding:25px}
.upload-icon{width:58px;height:58px;margin:0 auto 12px;border-radius:50%;background:#e9f1ff;color:#1769d2;display:flex;align-items:center;justify-content:center;font-size:28px}
.upload-content h4{margin:0 0 6px;font-size:14px}
.upload-content p{margin:0;color:#9ca3af;font-size:12px}
#previewImage{max-width:100%;max-height:180px;border-radius:7px;display:none;object-fit:contain}
.ai-button{width:100%;background:#1769d2;color:white;border:none;border-radius:6px;padding:11px;font-size:13px;font-weight:bold;cursor:pointer;margin-top:10px}
.ai-button:hover{background:#1258b2}
.ai-button:disabled{background:#9ca3af;cursor:not-allowed}
.ai-message{font-size:12px;margin:8px 0;color:#6b7280}
.submit-area{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.cancel-button{border:1px solid #d9dee5;background:white;color:#4b5563;border-radius:6px;padding:10px 18px;cursor:pointer}
.submit-button{border:none;background:#1769d2;color:white;border-radius:6px;padding:10px 20px;cursor:pointer;font-weight:bold}
.submit-button:hover{background:#1258b2}
#message{text-align:right;margin-top:10px;font-size:13px;font-weight:bold}
.info-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:7px;padding:14px;margin-bottom:15px}
.info-box h4{margin:0 0 7px;font-size:13px}
.info-box p{margin:0;font-size:12px;line-height:1.5;color:#6b7280}
@media(max-width:900px){
.sidebar{width:70px}
.logo-text,.nav-item span:not(.nav-icon),.nav-title{display:none}
.main{margin-left:70px;width:calc(100% - 70px)}
.form-grid{grid-template-columns:1fr}
.search-box{display:none}
}
@media(max-width:600px){
.topbar{padding:0 15px}
.content-area{padding:15px}
.top-button{display:none}
}
</style>
</head>
<body>
<div class="dashboard">
<aside class="sidebar">
<div class="logo">
<div class="logo-icon">⚡</div>
<div class="logo-text">
<h2>AI City Guardian</h2>
<p>Citizen Dashboard</p>
</div>
</div>
<div class="nav-title">MAIN MENU</div>
<a href="../user/userpage.php" class="nav-item"><span class="nav-icon">⌂</span><span>Overview</span></a>
<a href="#" class="nav-item"><span class="nav-icon">⌖</span><span>Issues Map</span></a>
<a href="#" class="nav-item"><span class="nav-icon">◔</span><span>Analytics</span></a>
<a href="#" class="nav-item"><span class="nav-icon">♙</span><span>Departments</span></a>
<a href="#" class="nav-item"><span class="nav-icon">⚙</span><span>Settings</span></a>
</aside>
<main class="main">
<header class="topbar">
<div class="top-title">
<h1>New Issue</h1>
<p>Report a problem in your community</p>
</div>
<div class="top-actions">
<div class="search-box">
<span>⌕</span>
<input type="text" placeholder="Search issues, locations...">
</div>
<button class="top-button" type="button">⚱ Filters</button>
<button class="top-button" type="button" onclick="location.reload()">⟳ Refresh</button>
<button class="top-button primary" type="button">＋ New Issue</button>
<div class="notification">🔔<span class="notification-badge">3</span></div>
</div>
</header>
<section class="content-area">
<div class="page-header">
<div>
<h2>Submit a New Issue</h2>
<p>Provide details about the urban issue you want to report.</p>
</div>
</div>
<form id="reportForm" action="../report/upload.php" method="POST" enctype="multipart/form-data">
<div class="form-grid">
<div>
<div class="card">
<div class="card-title">
<h3>Issue Information</h3>
<span>Required fields</span>
</div>
<div class="form-group">
<label for="location">Location</label>
<input type="text" id="location" name="location" class="input" placeholder="Enter issue location" required>
<div class="location-buttons">
<button type="button" id="autoLocateBtn" class="small-button location-button">📍 Use My Current Location</button>
<button type="button" id="mapButton" class="small-button maps-button">Open in Google Maps</button>
</div>
<p id="mapMessage">Enter a location or select a point on the map.</p>
<div id="locationMap"></div>
<input type="hidden" id="latitude" name="latitude">
<input type="hidden" id="longitude" name="longitude">
</div>
<div class="form-group">
<label for="description">Description</label>
<textarea name="ai_description" id="description" placeholder="Click 'Analyze Image with AI' after uploading an image, then review or edit the description here..." required></textarea>
</div>
</div>
</div>
<div>
<div class="card">
<div class="card-title">
<h3>Issue Image</h3>
<span>Optional</span>
</div>
<label for="input-file" class="upload-area" id="drop-area">
<input type="file" name="image" accept="image/*" id="input-file" hidden>
<div class="upload-content" id="uploadContent">
<div class="upload-icon">↑</div>
<h4>Drag and drop or click to upload</h4>
<p>Upload an image of the issue from your device</p>
</div>
<img id="previewImage" alt="Image preview">
</label>
<button type="button" id="analyzeButton" class="ai-button">🤖 Analyze Image with AI</button>
<p id="analyzeMessage" class="ai-message"></p>
</div>
<div class="card" style="margin-top:20px;">
<div class="card-title"><h3>AI Assistance</h3></div>
<div class="info-box">
<h4>How it works</h4>
<p>Upload an image and our AI will analyze the issue, generate a description and help classify the report. You can review and edit the generated description before submitting.</p>
</div>
<div class="info-box">
<h4>Location Detection</h4>
<p>Use your current location or manually enter a location. The system will automatically match the location on the map.</p>
</div>
</div>
</div>
</div>
<div class="submit-area">
<button type="button" class="cancel-button" onclick="window.location.href='../user/userpage.php'">Cancel</button>
<button type="submit" class="submit-button">Submit Report</button>
</div>
<p id="message"></p>
</form>
</section>
</main>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
const dropArea=document.getElementById("drop-area");
const inputFile=document.getElementById("input-file");
const uploadContent=document.getElementById("uploadContent");
const previewImage=document.getElementById("previewImage");
const reportForm=document.getElementById("reportForm");
const message=document.getElementById("message");
const locationInput=document.getElementById("location");
const mapButton=document.getElementById("mapButton");
const mapMessage=document.getElementById("mapMessage");
const descriptionBox=document.getElementById("description");
const analyzeButton=document.getElementById("analyzeButton");
const analyzeMessage=document.getElementById("analyzeMessage");
const autoLocateBtn=document.getElementById("autoLocateBtn");
const latitudeInput=document.getElementById("latitude");
const longitudeInput=document.getElementById("longitude");
let map=null;
let marker=null;

function initMap(){
map=L.map("locationMap").setView([3.1390,101.6869],12);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"&copy; OpenStreetMap contributors"}).addTo(map);
map.on("click",function(e){
const lat=e.latlng.lat;
const lng=e.latlng.lng;
latitudeInput.value=lat;
longitudeInput.value=lng;
if(!marker){
marker=L.marker([lat,lng]).addTo(map);
}else{
marker.setLatLng([lat,lng]);
}
mapMessage.style.color="#20a968";
mapMessage.textContent="Location selected on map.";
reverseGeocode(lat,lng);
});
setTimeout(function(){map.invalidateSize()},300);
}

function uploadImage(){
if(!inputFile.files[0])return;
previewImage.src=URL.createObjectURL(inputFile.files[0]);
previewImage.style.display="block";
uploadContent.style.display="none";
}

inputFile.addEventListener("change",uploadImage);

dropArea.addEventListener("dragover",function(e){
e.preventDefault();
dropArea.style.borderColor="#1769d2";
});

dropArea.addEventListener("dragleave",function(){
dropArea.style.borderColor="#cbd5e1";
});

dropArea.addEventListener("drop",function(e){
e.preventDefault();
dropArea.style.borderColor="#cbd5e1";
inputFile.files=e.dataTransfer.files;
uploadImage();
});

analyzeButton.addEventListener("click",function(){
if(!inputFile.files[0]){
analyzeMessage.textContent="Please upload an image first.";
analyzeMessage.style.color="red";
return;
}
analyzeMessage.textContent="";
analyzeImage(inputFile.files[0]);
});

function analyzeImage(file){
analyzeButton.disabled=true;
analyzeMessage.textContent="Analyzing image...";
analyzeMessage.style.color="#6b7280";
descriptionBox.value="";
descriptionBox.disabled=true;
const formData=new FormData();
formData.append("image",file);
formData.append("location",locationInput.value);
fetch("analyze.php",{method:"POST",body:formData})
.then(response=>response.json())
.then(result=>{
descriptionBox.disabled=false;
analyzeButton.disabled=false;
if(result.success){
descriptionBox.value=result.description||"";
analyzeMessage.textContent="AI description generated. You can edit it before submitting.";
analyzeMessage.style.color="#20a968";
}else{
analyzeMessage.textContent="Could not generate description. Please describe the issue manually.";
analyzeMessage.style.color="red";
console.log("Error:",result.error);
}
})
.catch(error=>{
console.log("Fetch error:",error);
descriptionBox.disabled=false;
analyzeButton.disabled=false;
analyzeMessage.textContent="Error analyzing image.";
analyzeMessage.style.color="red";
});
}

mapButton.addEventListener("click",function(){
const query=locationInput.value.trim();
if(query){
mapMessage.textContent="Opening Google Maps...";
window.open("https://www.google.com/maps?q="+encodeURIComponent(query)+"&z=18","_blank");
}else{
mapMessage.textContent="Please enter a location first.";
}
});

autoLocateBtn.addEventListener("click",function(){
if(!navigator.geolocation){
mapMessage.textContent="Geolocation is not supported by your browser.";
return;
}
mapMessage.style.color="#6b7280";
mapMessage.textContent="Getting your location...";
const submitBtn=reportForm.querySelector('button[type="submit"]');
submitBtn.disabled=true;
submitBtn.textContent="Please wait...";
navigator.geolocation.getCurrentPosition(async function(position){
const lat=position.coords.latitude;
const lng=position.coords.longitude;
latitudeInput.value=lat;
longitudeInput.value=lng;
map.setView([lat,lng],17);
if(!marker){
marker=L.marker([lat,lng]).addTo(map);
}else{
marker.setLatLng([lat,lng]);
}
await reverseGeocode(lat,lng);
submitBtn.disabled=false;
submitBtn.textContent="Submit Report";
},function(error){
mapMessage.style.color="red";
mapMessage.textContent="Could not get location: "+error.message;
submitBtn.disabled=false;
submitBtn.textContent="Submit Report";
},{enableHighAccuracy:true,timeout:10000});
});

async function reverseGeocode(lat,lng){
mapMessage.style.color="#6b7280";
mapMessage.textContent="Looking up address...";
try{
const geoRes=await fetch("https://nominatim.openstreetmap.org/reverse?format=json&lat="+lat+"&lon="+lng+"&zoom=18&addressdetails=1",{headers:{"Accept-Language":"en"}});
if(!geoRes.ok)throw new Error("Nominatim error");
const geoData=await geoRes.json();
if(geoData.display_name){
locationInput.value=geoData.display_name;
if(marker){
marker.bindPopup(geoData.display_name).openPopup();
}
mapMessage.style.color="#20a968";
mapMessage.textContent="Location detected.";
}else{
mapMessage.textContent="Coordinates captured.";
}
}catch(err){
mapMessage.textContent="Coordinates captured, but address lookup failed.";
console.error(err);
}
}

async function lookupLocationFromText(query){
if(!query||query.trim()==="")return;
mapMessage.style.color="#6b7280";
mapMessage.textContent="Looking up location...";
try{
const geoRes=await fetch("https://nominatim.openstreetmap.org/search?q="+encodeURIComponent(query)+"&format=json&limit=1&countrycodes=my",{headers:{"Accept-Language":"en"}});
if(!geoRes.ok)throw new Error("Nominatim error");
const results=await geoRes.json();
if(!results||results.length===0){
mapMessage.textContent="Couldn't find that location.";
return;
}
const lat=parseFloat(results[0].lat);
const lng=parseFloat(results[0].lon);
latitudeInput.value=lat;
longitudeInput.value=lng;
map.setView([lat,lng],16);
if(!marker){
marker=L.marker([lat,lng]).addTo(map);
}else{
marker.setLatLng([lat,lng]);
}
marker.bindPopup(results[0].display_name).openPopup();
mapMessage.style.color="#20a968";
mapMessage.textContent="Location matched on map.";
}catch(err){
mapMessage.textContent="Location lookup failed.";
console.error(err);
}
}

let locationTimer=null;
locationInput.addEventListener("input",function(){
clearTimeout(locationTimer);
const query=locationInput.value.trim();
if(query.length<3)return;
locationTimer=setTimeout(function(){
lookupLocationFromText(query);
},800);
});

reportForm.addEventListener("keydown",function(e){
if(e.key==="Enter"&&(e.target.tagName==="INPUT"||e.target.tagName==="TEXTAREA"||e.target.tagName==="SELECT")){
e.preventDefault();
}
});

reportForm.addEventListener("submit",function(e){
e.preventDefault();
const formData=new FormData(reportForm);
if(inputFile.files[0])formData.append("image",inputFile.files[0]);
const submitBtn=reportForm.querySelector('button[type="submit"]');
submitBtn.disabled=true;
submitBtn.textContent="Submitting...";
fetch("../report/upload.php",{method:"POST",body:formData})
.then(response=>response.text())
.then(data=>{
console.log("Upload response:",data);
if(data.includes("successfully")){
message.style.color="#20a968";
message.textContent=data;
reportForm.reset();
previewImage.src="";
previewImage.style.display="none";
uploadContent.style.display="block";
mapMessage.textContent="";
latitudeInput.value="";
longitudeInput.value="";
if(marker){
map.removeLayer(marker);
marker=null;
}
map.setView([3.1390,101.6869],12);
setTimeout(function(){
window.location.href="../user/userpage.php";
},1500);
}else{
message.style.color="red";
message.textContent=data;
submitBtn.disabled=false;
submitBtn.textContent="Submit Report";
}
})
.catch(error=>{
console.error(error);
message.style.color="red";
message.textContent="An error occurred while submitting the report.";
submitBtn.disabled=false;
submitBtn.textContent="Submit Report";
});
});

window.addEventListener("load",function(){
initMap();
});
</script>
</body>
</html>
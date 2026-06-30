<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title> Citizen Report Portal </title>
    
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
    <select name="issueType" style="text-align:center; padding: 8px; width: 200px;">
    	<option value="">Select an option...</option>
    	<option value="potholes">🕳️ Road Damage / Potholes</option>
        <option value="lights">💡 Broken streetlights</option>
        <option value="waste">🗑️ Illegal dumping / Waste</option>
        <option value="flooding ">🚰 Water leak / Flooding </option>
        <option value="Damaged infrastructure">💥 Damaged public facilities</option>
	</select>
    
    

    <label for="location" style="display: block; margin: 10px 0 5px 0;">Location</label>
    <input type="text" id="location" name="location" placeholder="Enter your location here"><br>
    <button type="button" id="mapButton" style="margin: 8px 0 12px 0; padding: 7px 14px; font-size: 13px; background-color: #2f80ed; color: white; border: none; border-radius: 5px; cursor: pointer;">Open in Google Maps</button>
    <p id="mapMessage" style="margin: 0 0 10px 0; color: #444; font-size: 13px;"></p>

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

<script>
const dropArea = document.getElementById("drop-area");
const inputFile = document.getElementById("input-file");
const imageView = document.getElementById("img-view");
const reportForm = document.getElementById("reportForm");
const message = document.getElementById("message");
const locationInput = document.getElementById("location");
const mapButton = document.getElementById("mapButton");
const mapMessage = document.getElementById("mapMessage");

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
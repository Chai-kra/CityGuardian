<?php
include "db.php";

$issue = $_POST['issueType'];
$location = $_POST['location'];
$description = $_POST['description'];

$image = "";

if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

    $image = time() . "_" . $_FILES['image']['name'];

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "uploads/" . $image
    );
}

$sql = "INSERT INTO reports
(issue_type, location, description, image)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param("ssss",
    $issue,
    $location,
    $description,
    $image
);

if($stmt->execute()){
    echo "Report submitted successfully!";
}else{
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
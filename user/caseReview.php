<?php
$totalCases = 50;
$actionNeededCases = 10;
$underwayCases = 10;
$settledCases = 30;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Review</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="#" class="nav-logo">
                <h2 class="logo-text">AI City Guardian</h2>
            </a>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="caseReview.php" class="nav-link">Case Review</a>
                </li>
                <li class="nav-item">
                    <a href="adminStatistics.html" class="nav-link">Statistics</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">Admin</a>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="summary">
            <h2 class="title">Case Review</h2>
            <div class="card-container">
                <div class="card">
                    <div class="card-content">
                        <h3>Total</h3>
                        <h3>Total</h3>
                        <p>
                            <?php echo $totalCases; ?> cases
                        </p>
                        <a href="" class="read-more">Read more</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Action Needed</h3>
                        <p>
                            <?php echo $actionNeededCases; ?> cases
                        </p>
                        <a href="" class="read-more">Read more</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Underway</h3>
                        <p>
                            <?php echo $underwayCases; ?> cases
                        </p>
                        <a href="" class="read-more">Read more</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Settled</h3>
                        <p>
                            <?php echo $settledCases; ?> cases
                        </p>
                        <a href="" class="read-more">Read more</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-container">
            <form action="" method="GET">
                <div class="search">
                    <span class="search-icon material-symbols-outlined">search</span>
                    <input class="search-input" type="search" placeholder="Search case..."
			value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </form>

            <div class="dropdown">
                <div class="select">
                    <span class="selected">Priority</span>
                    <div class="caret"></div>
                </div>
                <ul class="menu">
                    <li class="active">Priority</li>
                    <li>Low</li>
                    <li>Medium</li>
                    <li>High</li>
                    <li>Critical</li>
                </ul>
                
            </div>

            <div class="dropdown">
                <div class="select">
                    <span class="selected">Priority</span>
                    <div class="caret"></div>
                </div>
                <ul class="menu">
                    <li class="active">Priority</li>
                    <li>Low</li>
                    <li>Medium</li>
                    <li>High</li>
                    <li>Critical</li>
                </ul>
                
            </div>
        </div>
        
        <div class="caseReviewBox">
            <div class="sub-category">
                <button class="collapsible">Action needed</button>
                <div class="content">
                    <div class="inner-content">
                        <p>report 1</p>
                    </div> 
                </div>
                <button class="collapsible">Underway</button>
                <div class="content">
                    <div class="inner-content">
                        <p>report 1</p>
                    </div> 
                </div>
                <button class="collapsible">Settled</button>
                <div class="content">
                    <div class="inner-content">
                        <p>report 1</p>
                    </div> 
                </div>
            </div>
        </div>
    </main>

    <script>
        var coll = document.getElementsByClassName("collapsible");
        var i;

        for(i = 0; i < coll.length; i++){
            coll[i].addEventListener("click", function(){
                this.classList.toggle("active");
                var content = this.nextElementSibling;

                if(content.style.maxHeight){
                    content.style.maxHeight = null;
                }else{
                    content.style.maxHeight = content.scrollHeight + "px";
                }
            });
        }

        const dropdowns = document.querySelectorAll(".dropdown");

        dropdowns.forEach(dropdown => {
            const select = dropdown.querySelector(".select");
            const caret = dropdown.querySelector(".caret");
            const menu = dropdown.querySelector(".menu");
            const options = dropdown.querySelectorAll(".menu li");
            const selected = dropdown.querySelector(".selected");

            select.addEventListener("click", () => {
                select.classList.toggle("select-clicked");
                caret.classList.toggle("caret-rotate");
                menu.classList.toggle("menu-open");
            });

            options.forEach(option => {
                option.addEventListener("click", () => {
                    selected.innerText = option.innerText;

                    select.classList.remove("select-clicked");
                    caret.classList.remove("caret-rotate");
                    menu.classList.remove("menu-open");

                    options.forEach(option => {
                        option.classList.remove("active");
                    });

                    option.classList.add("active");
                });
            });
        });
    </script>
</body>



</html>
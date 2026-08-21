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
                <li class="nav-item admin-menu-item">
                    <a href="#" class="nav-link">Admin</a>
                    <div class="logout-dropdown">
                        <a href="login.php" class="logout-btn">
                            <i class='bx bx-log-out'></i> Logout
                        </a>
                    </div>
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

            <div class="date-picker-container">
                <div class="select date-select">
                    <span class="selected" id="date-label">Choose Date</span>
                    <div class="caret"></div>
                    <input type="date" id="case-date-picker" class="native-date-input" onchange="updateDateLabel(this)">
                </div>
                
            </div>
        </div>
        
        <div class="caseReviewBox">
            <div class="sub-category">
                <button class="collapsible">
                    <span>Action needed</span>
                    <span class="case-count"><?php echo $actionNeededCases; ?></span>
                </button>
                <div class="content">
                    <div class="inner-content report-list">
                        <div class="report-card">
                            <div class="report-info">
                                <h4>Case #1024: Traffic Light Malfunction</h4>
                                <p>Location: 5th Avenue & Main Street</p>
                            </div>
                            <div class="report-meta">
                                <span class="badge critical">Critical</span>
                                <span class="date">Aug 21, 2026</span>
                            </div>
                            <button class="view-btn">Review</button>
                        </div>

                        <!-- Report Card 2 -->
                        <div class="report-card">
                            <div class="report-info">
                                <h4>Case #1025: Noise Complaint</h4>
                                <p>Location: Downtown Plaza Center</p>
                            </div>
                            <div class="report-meta">
                                <span class="badge medium">Medium</span>
                                <span class="date">Aug 20, 2026</span>
                            </div>
                            <button class="view-btn">Review</button>
                        </div>
                    </div> 
                </div>
                <button class="collapsible">
                    <span>Underway</span>
                    <span class="case-count"><?php echo $underwayCases; ?></span>
                </button>
                <div class="content">
                    <div class="inner-content report-list">
                        <div class="report-card">
                            <div class="report-info">
                                <h4>Case #1024: Traffic Light Malfunction</h4>
                                <p>Location: 5th Avenue & Main Street</p>
                            </div>
                            <div class="report-meta">
                                <span class="badge critical">Critical</span>
                                <span class="date">Aug 21, 2026</span>
                            </div>
                            <button class="view-btn">Review</button>
                        </div>

                        <!-- Report Card 2 -->
                        <div class="report-card">
                            <div class="report-info">
                                <h4>Case #1025: Noise Complaint</h4>
                                <p>Location: Downtown Plaza Center</p>
                            </div>
                            <div class="report-meta">
                                <span class="badge medium">Medium</span>
                                <span class="date">Aug 20, 2026</span>
                            </div>
                            <button class="view-btn">Review</button>
                        </div>
                    </div> 
                </div>
                <button class="collapsible">
                    <span>Settled</span>
                    <span class="case-count"><?php echo $settledCases; ?></span>
                </button>
                <div class="content">
                    <div class="inner-content report-list">
                        <div class="report-card">
                            <div class="report-info">
                                <h4>Case #1024: Traffic Light Malfunction</h4>
                                <p>Location: 5th Avenue & Main Street</p>
                            </div>
                            <div class="report-meta">
                                <span class="badge critical">Critical</span>
                                <span class="date">Aug 21, 2026</span>
                            </div>
                            <button class="view-btn">Review</button>
                        </div>

                        <!-- Report Card 2 -->
                        <div class="report-card">
                            <div class="report-info">
                                <h4>Case #1025: Noise Complaint</h4>
                                <p>Location: Downtown Plaza Center</p>
                            </div>
                            <div class="report-meta">
                                <span class="badge medium">Medium</span>
                                <span class="date">Aug 20, 2026</span>
                            </div>
                            <button class="view-btn">Review</button>
                        </div>
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
                    // 1. Immediately hide overflow before closing so it doesn't look messy
                    content.style.overflow = "hidden";
                    content.style.maxHeight = null;
                } else {
                    // 2. Open the accordion
                    content.style.maxHeight = content.scrollHeight + "px";
                    
                    // 3. Wait for the 0.2s CSS animation to finish, then allow the cards to break out of the box!
                    setTimeout(() => {
                        if(content.style.maxHeight){
                            content.style.overflow = "visible";
                        }
                    }, 200);
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

            // Toggles the dropdown menu open or closed
            select.addEventListener("click", (e) => {
                // Stop the click from bubbling up to the document listener immediately
                e.stopPropagation();
                toggleMenu();
            });

            // Handles selecting an option from the menu
            options.forEach(option => {
                option.addEventListener("click", () => {
                    selected.innerText = option.innerText;
                    options.forEach(opt => opt.classList.remove("active"));
                    option.classList.add("active");
                    closeMenu();
                });
            });

            // --- Helper Functions ---

            const toggleMenu = () => {
                const isOpen = menu.classList.contains("menu-open");
                if (isOpen) {
                    closeMenu();
                } else {
                    openMenu();
                }
            };

            const openMenu = () => {
                select.classList.add("select-clicked");
                caret.classList.add("caret-rotate");
                menu.classList.add("menu-open");
                // Listen for clicks outside the dropdown
                document.addEventListener("click", handleClickOutside);
            };

            const closeMenu = () => {
                select.classList.remove("select-clicked");
                caret.classList.remove("caret-rotate");
                menu.classList.remove("menu-open");
                // Stop listening for clicks outside
                document.removeEventListener("click", handleClickOutside);
            };

            // The function that closes the menu if a click is detected outside
            const handleClickOutside = (e) => {
                if (!dropdown.contains(e.target)) {
                    closeMenu();
                }
            };
        });

        function updateDateLabel(input) {
            const label = document.getElementById("date-label");
            if (input.value) {
                const [year, month, day] = input.value.split('-');
                const formattedDate = `${day}/${month}/${year}`;
                label.innerText = formattedDate;
            } else {
                label.innerText = "Choose Date";
            }
        }
    </script>
</body>



</html>
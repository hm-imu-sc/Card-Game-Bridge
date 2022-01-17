<?php
    include_once "functions.php";

    $job = $_POST["job"];

    if ($job == "login") {
        echo json_encode(login($_POST));
    }
    else if($job == "logout") {
        ?>
            <div class="heading">
                <div class="heading_section">
                    <h1>Bridge Table</h1>
                </div>

                <hr>
            </div>
        <?php
        login_interface();
    }
?>
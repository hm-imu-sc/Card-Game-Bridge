<?php
    include_once "functions.php";

    $job = "";
    $type = "POST";

    if (isset($_POST["job"])) {
        $job = $_POST["job"];
    }
    else {
        $job = $_GET["job"];
        $type = "GET";
    }

    if ($job == "load_game") {
        load_game_session($_COOKIE, $_POST["session_id"]);
    }
    elseif ($job == "verify_session") {
        echo check_game_session_availabality($_COOKIE, $_POST["session_id"]);
    }
    elseif ($job == "load_sessions") {
        if (varify_token($_POST)) {
            game_sessions_loader();
        }
        else {
            echo "NO CHANCHE";
        }
    }
    elseif ($job == "general_info") {
        echo json_encode(get_general_info($_COOKIE["session_id"]));
    }
    elseif ($job == "sync_game") {
        echo json_encode([
            "turn"=> get_turn_info($_COOKIE["session_id"]),
            "is_new_cycle"=> is_new_cycle($_COOKIE["session_id"])
        ]);
    }
    elseif ($job == "card_play") {
        echo json_encode([
            "valid"=> validate_card_play($_COOKIE["session_id"], $_COOKIE["id"], $_POST["card"])
        ]);
    }
    elseif ($job == "table_update") {
        echo json_encode([
            "table_card_update"=> table_update($_COOKIE["session_id"]),
            "new_round"=> load_new_round($_COOKIE["session_id"], $_COOKIE["id"], $_POST["round_number"]),
        ]);
    }
    elseif ($job == "update_owned_card") {
        echo json_encode(owned_card_updates($_COOKIE["session_id"], $_COOKIE["id"], $_POST["have"]));
    }
    elseif ($job == "update_round") {
        echo json_encode(round_update($_COOKIE["session_id"]));
    }
    elseif ($job == "make_call") {

        // print_r(array_keys($_POST));

         echo json_encode([
            "call_success"=> make_call($_COOKIE["session_id"], $_COOKIE["id"], $_POST["call_val"])
        ]);
    }
?>
<?php
    include_once "classes.php";
        
    function json_read($filename) {
        $file = fopen($filename, "r");
        $data = fread($file, filesize($filename));
        fclose($file);
        return json_decode($data, true);
    }

    function json_write($filename, $data) {
        $file = fopen($filename, "w");
        fwrite($file, json_encode($data));
        fclose($file);
    }

    function retrive_session($session_id) {
        $sessions = JSON::get_array("assets/data/game_sessions.json");
        $call_bridge = new CallBridge(call_bridge_obj: JSON::array_to_object($sessions[$session_id]));
        return $call_bridge;
    }

    function save_session($session_id, $session) {
        $filename = "assets/data/game_sessions.json";
        $all_sessions = JSON::get_array($filename);
        $all_sessions[$session_id] = $session;
        JSON::save($filename, $all_sessions);
    }

    function get_user($id) {
        $filename = "assets/data/users.json";
        $users = json_read($filename);

        foreach ($users as $user) {
            if ($user["id"] == $id) {
                return [
                    "found" => true,
                    "user" => $user
                ];
            }
        }

        return [
            "found" => false
        ];
    }

    function login($post) {
        $id = $post["id"];
        $passcode = $post["passcode"];
        
        $users = json_read("assets/data/users.json");

        foreach ($users as $user) {
            if ($user["id"] == $id && $user["passcode"] == $passcode) {
                $token = generate_token($id);
                
                setcookie("login_status", true, time() + (3600*24));
                setcookie("id", $id, time() + (3600*24));

                return [
                    "login_status"=> 1, 
                    "name"=> $user["name"],
                    "token"=> $token,
                ];
            }
        }

        return [
            "login_status"=> 0,
            "alerts"=> "<span class='alert'><span class='alert_close'><i class='fad fa-times-circle'></i></span> WRONG CREDENTIAL !!!<span>",
        ];
    }

    function generate_token($id) {
        $filename = "assets/data/tokens.json";
        $tokens = json_read($filename);

        $new_token = "new_token";

        $tokens[$id] = $new_token;
        json_write($filename, $tokens);
        return $new_token;
    }

    function varify_token($post) {
        $filename = "assets/data/tokens.json";
        $tokens = json_read($filename);
        if (isset($post["token"])) {
            if ($tokens[$post["id"]] == $_POST["token"]) {
                unset($tokens[$post["id"]]);
                json_write($filename, $tokens);
                return true;
            }
        }
        return false;
    }

    function new_session($session_id, $players) {
        $players_obj = [];

        foreach ($players as $player) {
            array_push($players_obj, new Player(name: $player["name"], id: $player["id"]));
        }

        $new_session = new CallBridge(players: $players_obj);
        $new_session->start();

        $filename = "assets/data/game_sessions.json";

        $sessions = JSON::get_array($filename);
        $sessions[$session_id] = $new_session;

        JSON::save($filename, $sessions);
    }

    function game_sessions_loader() {
        $sessions = array_keys(JSON::get_array("assets/data/game_sessions.json"));
        ?>
        
        <div class="game_sessions">

            <button id="logout_btn">Logout</button>

            <h1>Select a Game Session</h1>
            <?php
                foreach ($sessions as $session_id):
                ?>
                    <button id="<?php echo $session_id ?>">
                        <?php echo $session_id ?>
                    </button>
                <?php
                endforeach;
            ?>
        </div>
        <?php
    }

    function check_game_session_availabality($cookie, $session_id) {
        $player_id = $cookie["id"];

        $sessions = JSON::get_array("assets/data/game_sessions.json");

        $call_bridge = new CallBridge(call_bridge_obj: JSON::array_to_object($sessions[$session_id]));

        if ($call_bridge->authenticate_player($player_id)) {
            echo json_encode([
                "available"=> true,
            ]);
        }
        else {
            echo json_encode([
                "available"=> false,
                "alert"=> "You're no allowed to play in this game session !!!"
            ]);
        }
    }

    function load_game_session($cookie, $session_id) {
        
        setcookie("session_id", $session_id, time() + (24*3600));

        $player_id = $cookie["id"];
        $player = get_user($player_id)["user"];

        $sessions = JSON::get_array("assets/data/game_sessions.json");

        $call_bridge = new CallBridge(call_bridge_obj: JSON::array_to_object($sessions[$session_id]));

        // echo "<pre>";
        // print_r($call_bridge);
        // echo "</pre>";

        $player = $call_bridge->current_round->get_player($player_id);
        
        $cards = [];

        // echo "<pre>";
        // print_r($player->cards_in_hand);
        // echo "</pre>";

        // return;

        foreach ($player->cards_in_hand as $card) {
            if ($card["exists"]) {
                array_push($cards, clone $card["card"]);
            }
        }

        $cards = sort_cards($cards);
        $card_names = [[],[],[],[]];
        for ($i=0; $i<4; $i++) {
            foreach ($cards[$i] as $card) {
                array_push($card_names[$i], $card->view());
            }
        }
        $owned_cards = $call_bridge->current_round->player_log($player_id);

        $this_player = -1;

        for ($i=0; $i<4; $i++) {
            if ($call_bridge->players[$i]->id == $player_id) {
                $this_player = $i;
            }
        }

        $player_names = [];
        $table_player_element = [];

        for ($i=0; $i<4; $i++) {

            $id = $call_bridge->players[($this_player + $i) % 4]->id;
            $name = $call_bridge->players[($this_player + $i) % 4]->name;

            // echo "<pre>";
            // print_r($call_bridge->current_round->current_cycle->no_of_players);
            $card = $call_bridge->current_round->current_cycle->get_card_for($id);
            // echo "</pre>";

            $img = "";

            if ($card != "null") {
                $img = "<img id='{$card}' src='assets/img/cards/{$card}.png'>";
            }

            array_push($player_names, [
                "id"=> $id,
                "name"=> $name
            ]);

            array_push($table_player_element, "
                <div class='table_card'>
                    <div class='card' id='card_{$id}'>{$img}</div>

                    <div class='player_name'>
                        <h1 id='{$id}'>{$name}</h1>
                    </div>
                </div>            
            ");
        }

        $current_round_log = [];

        // echo "<pre>";
        $temp = $call_bridge->current_round->round_log();
        // echo "</pre>";

        for ($i=0; $i<4; $i++) {
            array_push($current_round_log, $temp[($this_player + $i) % 4]);
        }

        $previous_rounds_log = [];
        $temp = $call_bridge->previous_rounds_log();
        foreach ($temp as $temp_log) {
            $round_log = [];
    
            for ($i=0; $i<4; $i++) {
                array_push($round_log, $temp_log[($this_player + $i) % 4]);
            }

            array_push($previous_rounds_log, $round_log);
        }
        // $previous_rounds_log = array_reverse($previous_rounds_log);

        ?>
            <div class="game_section">
                <div class="player_section">
                    Playing as: <span class="name"><?php echo $player->name ?></span>
                    <button id="logout_btn">Logout</button>

                    <div class="card_section">
                        <div class="button_section">
                            <button id="cards_to_play">Cards</button>
                            <button id="cards_owned">My Log</button>
                        </div>
                        <div class="cards_in_hand" id="cards_in_hand">
                            <div class="card_hearts">
                            <?php
                                foreach ($card_names[0] as $card_name):
                                ?>
                                    <img id="<?php echo $card_name ?>" src="<?php echo "assets/img/cards/{$card_name}.png" ?>" alt="">
                                <?php
                                endforeach;
                            ?>
                            </div>
                            <div class="card_clubs">
                            <?php
                                foreach ($card_names[1] as $card_name):
                                ?>
                                    <img id="<?php echo $card_name ?>" src="<?php echo "assets/img/cards/{$card_name}.png" ?>" alt="">
                                <?php
                                endforeach;
                            ?>
                            </div>
                            <div class="card_diamonds">
                            <?php
                                foreach ($card_names[2] as $card_name):
                                ?>
                                    <img id="<?php echo $card_name ?>" src="<?php echo "assets/img/cards/{$card_name}.png" ?>" alt="">
                                <?php
                                endforeach;
                            ?>
                            </div>
                            <div class="card_spades">
                            <?php
                                foreach ($card_names[3] as $card_name):
                                ?>
                                    <img id="<?php echo $card_name ?>" src="<?php echo "assets/img/cards/{$card_name}.png" ?>" alt="">
                                <?php
                                endforeach;
                            ?>
                            </div>
                        </div>
                        <div class="cards_owned" id="cards_owned_">
                            <div class="ready_section">
                                <button id="ready_btn" value="0"><h1>Ready</h1></button>
                            </div>
                            <div class="call_section">
                                Call: <input type="range" name="call" id="call" min="1" max="13" value="1"> <span id="range_val">1</span>
                            </div>
                            <p class="owned_card_heading">Owned: <span id="no_of_owned_cards"><?php echo sizeof($owned_cards) ?></span></p>
                            <div class="owned_card_section">
                                <div class="owned_cards">
                                    <?php
                                        foreach ($owned_cards as $owned_card_set):
                                            ?><div class="owned_card_set"><?php
                                            foreach ($owned_card_set as $owned_card):
                                                ?><img src="assets/img/cards/<?php echo $owned_card ?>.png" alt=""><?php
                                            endforeach;
                                            ?></div><?php
                                        endforeach;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vr"></div>

                <div class="board_section">
                    <div class="heading_section">
                        <h1>Bridge Table</h1>
                    </div>

                    <hr>

                    <div class="playing_table">
                        <div class="row"><?php echo $table_player_element[3] ?></div>

                        <div class="row">
                            <?php echo $table_player_element[0] ?>

                            <div id="assistant_section">
                                <button id="discard_round_btn">Discard Round</button>
                            </div>

                            <?php echo $table_player_element[2] ?>
                        </div>

                        <div class="row"><?php echo $table_player_element[1] ?></div>
                    </div>
                </div>

                <div class="vr"></div>

                <div class="log_section">
                    <div class="button_section">
                        <button id="game_log">Game Log</button>
                        <button id="chat">Chat</button>
                    </div>
                    <div class="game_log">
                        <div class="current_round_heading">Current Round:</div> 
                        <div class="current_round_section">

                            <?php
                                foreach ($current_round_log as $player_log):
                                ?>
                                    <div class="player_log" id="log_<?php echo $player_log["id"] ?>">
                                        <div class="name">
                                            <?php echo $player_log["name"] ?>
                                        </div>
                                        <hr>
                                        <div class="call">
                                            Call: <?php echo $player_log["call"] == -1 ? "N/A" : $player_log["call"] ?>
                                        </div>
                                        <div class="owned">
                                            Owned: <?php echo $player_log["owned"] ?>
                                        </div>
                                    </div>
                                <?php
                                endforeach;
                            ?>

                        </div>

                        <div class="score_heading">Total Scores:</div>
                        <div class="total_score_section">
                            <?php
                                $scores = $call_bridge->get_score();
                                for ($i=0; $i<4; $i++) { 
                                    $id = $call_bridge->players[($this_player + $i) % 4]->id;
                                    $name = $call_bridge->players[($this_player + $i) % 4]->name;

                                    echo "
                                        <div class='player_score'>
                                            <span class='score_{$id}'>{$scores[($this_player + $i) % 4][1]}</span>
                                            <span class='name'>{$name}</span>
                                        </div>
                                    ";
                                }    
                            ?>
                        </div>

                        <div class="previous_round_heading">Previous Rounds:</div>
                        <div class="previous_round_section">
                            <?php 
                                $size = sizeof($previous_rounds_log);
                                for ($i=$size; $i>=1; $i--): 
                                    echo "<span class='round_number'> Round: {$i}</span>";
                                    $round_log = $previous_rounds_log[$i-1];    
                            ?>
                                <div class="round_section">
                                <?php
                                    foreach ($round_log as $player_log):
                                    ?>
                                        <div class="player_log">
                                            <div class="name">
                                                <?php echo $player_log["name"] ?>
                                            </div>
                                            <hr>
                                            <div class="call">
                                                Call: <?php echo $player_log["call"] == -1 ? "N/A" : $player_log["call"] ?>
                                            </div>
                                            <div class="owned">
                                                Owned: <?php echo $player_log["owned"] ?>
                                            </div>
                                        </div>
                                    <?php
                                    endforeach;
                                ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="chat">

                    </div>
                </div>
            </div>
        <?php
    }

    function get_general_info($session_id) {
        $session = retrive_session($session_id);

        return [
            "round_number"=> sizeof($session->rounds) + 1,
            "cycle_number"=> sizeof($session->current_round->cycles) + 1
        ];
    }

    function get_turn_info($session_id) {

        $call_bridge = retrive_session($session_id);

        return [
            "id"=> $call_bridge->current_round->next_turn()
        ];
    }

    function table_update($session_id) {
        $session = retrive_session($session_id);
        $updates = [];
        $played_cards = $session->current_round->current_cycle->get_played_cards();
        
        foreach ($played_cards as $played_card) {
            array_push($updates, [
                "player_id"=> $played_card["player"],
                "card"=> "<img src='assets/img/cards/{$played_card['card']}.png'>"
            ]);
        }

        return $updates;
    }

    function load_new_round($session_id, $player_id, $round_number) {
        $session = retrive_session($session_id);
        
        if (sizeof($session->rounds) > $round_number) {

            $this_player = -1;

            for ($i=0; $i<4; $i++) {
                if ($session->players[$i]->id == $player_id) {
                    $this_player = $i;
                }
            }

            $previous_round_log = [];
            $temp = $session->previous_rounds_log();
    
            for ($i=0; $i<4; $i++) {
                array_push($previous_round_log, $temp[sizeof($temp) - 1][($this_player + $i) % 4]);
            }
            $round_number++;
            $last_round = "
                <span class='round_number'> Round: {$round_number}</span>
                <div class='round_section'>
            ";

            foreach ($previous_round_log as $player_log) {
                $call = $player_log["call"] == -1 ? "N/A" : $player_log["call"];
                $last_round .= "
                    <div class='player_log'>
                        <div class='name'>{$player_log['name']}</div>
                        <hr>
                        <div class='call'>Call:{$call}</div>
                        <div class='owned'>Owned:{$player_log['owned']}</div>
                    </div>
                ";
            }

            $last_round .= "</div>";

            $player = $session->current_round->get_player($player_id);
            $cards = [];
    
            foreach ($player->cards_in_hand as $card) {
                if ($card["exists"]) {
                    array_push($cards, clone $card["card"]);
                }
            }
    
            $cards = sort_cards($cards);
            $card_names = [[],[],[],[]];
            for ($i=0; $i<4; $i++) {
                foreach ($cards[$i] as $card) {
                    array_push($card_names[$i], $card->view());
                }
            }

            $suit_names = ["card_hearts", "card_clubs", "card_diamonds", "card_spades"];
            $new_cards = [
                "card_hearts"=> "",
                "card_clubs"=> "",
                "card_diamonds"=> "",
                "card_spades"=> "",
            ];

            for ($i=0; $i<4; $i++) {
                foreach ($card_names[$i] as $card_name) {
                    $new_cards[$suit_names[$i]] .= "<img id='{$card_name}' src='assets/img/cards/{$card_name}.png'>";
                }                 
            }

            return [
                "status"=> true,
                "last_round"=> $last_round,
                "new_cards"=> $new_cards,
                "total_scores"=> $session->get_score(),
            ];
        }

        return [
            "status"=> false
        ];
    }

    function owned_card_updates($session_id, $player_id, $have) {
        $session = retrive_session($session_id);

        $player = $session->current_round->get_player($player_id);
        
        $cards = [];

        foreach ($player->cards_in_hand as $card) {
            if ($card["exists"]) {
                array_push($cards, clone $card["card"]);
            }
        }

        $cards = sort_cards($cards);
        $card_names = [[],[],[],[]];
        for ($i=0; $i<4; $i++) {
            foreach ($cards[$i] as $card) {
                array_push($card_names[$i], $card->view());
            }
        }
        $owned_cards = $session->current_round->player_log($player_id);

        $updates = "";

        for ($i = $have; $i < sizeof($owned_cards); $i++) {
            $owned_card_set = $owned_cards[$i];
            $updates.="<div class='owned_card_set'>";
            foreach ($owned_card_set as $owned_card) {
                $updates.="<img src='assets/img/cards/{$owned_card}.png'>";
            }
            $updates.="</div>";
        }

        return [
            "update_length"=> sizeof($owned_cards) - $have,
            "updates"=> $updates
        ];
    }

    function round_update($session_id){

        $session = retrive_session($session_id);

        $current_round_log = [];

        $temp = $session->current_round->round_log();

        for ($i=0; $i<4; $i++) {
            array_push($current_round_log, [
                "id"=> $temp[$i]["id"],
                "owned"=> $temp[$i]["owned"]
            ]);
        }

        return $current_round_log;
    }

    function is_new_cycle($session_id) {
        $session = retrive_session($session_id);
        if ($session->current_round->current_cycle->is_new_cycle()) {
            return true;
        }
        return false;
    }

    function make_call($session_id, $player_id, $call) {
        $session = retrive_session($session_id);
        try {
            $session->call($player_id, $call);
            save_session($session_id, $session);
            return true;
        }
        catch (InvalidCall $e) {
            return false;
        }
    }

    function play($session_id, $player_id, $card) {
        $session = retrive_session($session_id);
        
        $card = explode("_", $card);
        $card = new Card(suit: $card[0], code: $card[1]);
        
        $player = $session->current_round->get_player($player_id);

        $session->play($player, $card);

        if ($session->current_round->current_cycle->is_completed()) {
            $session->next_cycle();

            if ($session->current_round->is_completed()) {
                $session->new_round();
            }
        }

        save_session($session_id, $session);
    }

    function validate_card_play($session_id, $player_id, $card) {
        $session = retrive_session($session_id);
        $next_turn = $session->next_turn();

        $suit = explode("_", $card)[0];
        $cycle_started_by='';

        try{
            $cycle_started_by = $session->current_round->current_cycle->first_played_suit();
        }
        catch (CycleNotStarted $e){
            $cycle_started_by = $suit;
        }

        $player = $session->current_round->get_player($player_id);

        if ($player->has_suit($cycle_started_by)) {
            if ($cycle_started_by != $suit) {
                return false;
            }
        }

        if ($next_turn == $player_id) {
            play($session_id, $player_id, $card);
            return true;
        }

        return false;
    }

    function login_interface() {
        setcookie("login_status", false, time() - 3600);
        setcookie("id", "", time() - 3600);

        ?>
            <div class="login_section">
                <div class="id">
                    <label>ID:</label>
                    <input type="text" name="id" id="id" placeholder="Enter ID">
                </div>
                <div class="passcode">
                    <label>Passcode:</label>
                    <input type="password" name="passcode" id="passcode" placeholder="Enter Passcode">
                </div>
                <button class="login_btn" id="login_btn">Login</button>
            </div>        
        <?php
    }

    function compare_card($card_1, $card_2) {
        if ($card_1->is_equal_to($card_2)) {
            return 0;
        }

        if ($card_1->is_less_than($card_2)) {
            return -1;
        }

        if ($card_1->is_greater_than($card_2)) {
            return 1;
        }
    }

    function sort_cards($cards) {
        $suit_wise = [[],[],[],[]];
        foreach ($cards as $card) {
            if ($card->suit == "heart") {
                array_push($suit_wise[0], $card);
            }
            elseif ($card->suit == "club") {
                array_push($suit_wise[1], $card);
            }
            elseif ($card->suit == "diamond") {
                array_push($suit_wise[2], $card);
            }
            elseif ($card->suit == "spade") {
                array_push($suit_wise[3], $card);
            }
        }

        // echo "<pre>";
        // for ($i=0; $i<4; $i++) {
        //     print_r($suit_wise[$i]);
        // }
        // echo "</pre>";

        $cards = [];

        for ($i=0; $i<4; $i++) {
            usort($suit_wise[$i], "compare_card");
            $suit_wise[$i] = array_reverse($suit_wise[$i]);
            // foreach ($suit_wise[$i] as $card) {
            //     array_push($cards, $card);
            // }
        }

        return $suit_wise;
        // return $cards;
    }

    function copy_of($ar) {
        $copy_of_ar = [];

        foreach ($ar as $obj) {
            array_push($copy_of_ar, clone $obj);
        }

        return $copy_of_ar;
    }

?>
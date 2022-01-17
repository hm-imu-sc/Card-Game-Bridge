<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
            function dir_depth_count($dir="global_assets") {
        
        $root = "C:";
        $spliter = "\\";
        $depth = "";

        if (__DIR__[0] == "/") {
            $spliter = "/";
        }

        $dir_parts = explode($spliter, __DIR__);

        if (empty($dir_parts[0])) {
            $root = "/{$dir_parts[1]}";
        }

                        $bound = $spliter=="/" ? 1 : 0;

                for ($i=sizeof($dir_parts)-1; $i > $bound; $i--) {
            $directory = $root; 
            for ($j = $spliter=="/" ? 2 : 1; $j <= $i; $j++) {$directory.="{$spliter}{$dir_parts[$j]}";}
            if (is_dir($directory)) {
                $folder = opendir($directory);
                while (($subfolder = readdir($folder)) !== FALSE && $folder) {
                    if (is_dir($directory.$spliter.$subfolder) && $subfolder==$dir) {return $depth;}
                }
            }

            $depth.="../";
        }

        return null;
    }
            $dir_depth = dir_depth_count();
            include_once "{$dir_depth}my_modules/system.php";
        ?>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?php echo $dir_depth ?>global_assets/fonts/font-awesome-pro-5/css/all.css">
        <link rel="stylesheet" href="<?php echo $dir_depth ?>global_assets/css/style.css">
        <title>test</title>
    </head>
    <body>
        <button id="hide_unhide_system">
            <i class="fad fa-chevron-circle-left"></i>
        </button>
        <br>
        
        <div class="system">
            <?php access_denied($_COOKIE, true); echo system_controls("test.php", file:__FILE__) ?>
        </div>
        
        <div class="project">
            <?php

                include_once "classes.php";
                include_once "functions.php";

                // $sessions = JSON::get_array("assets/data/game_sessions.json");

                echo "<pre>";
                // print_r($sessions);
                // print_r(JSON::array_to_object($sessions["850-G1"]));
                // $call_bridge = new CallBridge(call_bridge_obj: JSON::array_to_object($sessions["850-G1"]));

                // $player = $call_bridge->current_round->get_player("850");

                // echo $player->has_suit("heart");

                // new_session("850-G4", [
                //     ["id"=> "850", "name"=> "Imu"],
                //     ["id"=> "851", "name"=> "Tanzil"],
                //     ["id"=> "852", "name"=> "Jamil"],
                //     ["id"=> "853", "name"=> "Sadik"],
                // ]);

                $players = [
                    new Player(id: "850", name: "Imu"),
                    new Player(id: "851", name: "Tanzil"),
                    new Player(id: "853", name: "Sadik"),
                    new Player(id: "852", name: "Jamil"),
                ];

                // $cards = [
                //     new Card(suit: "heart", code: "7"),
                //     new Card(suit: "diamond", code: "9"),
                //     new Card(suit: "diamond", code: "8"),
                //     new Card(suit: "club", code: "9"),
                // ];

                // $cycle = new Cycle(no_of_players: 4);

                // $cycle->insert($players[2], $cards[2]);
                // $cycle->insert($players[0], $cards[0]);
                // $cycle->insert($players[1], $cards[1]);
                // $cycle->insert($players[3], $cards[3]);

                // print_r($cycle->log());
                // print_r($cycle->winner);
                // print_r($cycle->played_cards[0]);
                    
                // $players = [
                //     ["won"=> 0],
                //     ["won"=> 0],
                //     ["won"=> 0],
                //     ["won"=> 0],
                // ];

                // for ($i=0; $i<4; $i++) {
                //     $players[$i]["won"]+=10;
                // }

                // print_r($players);

                // print_r(load_new_round("850-G3", "852", 1)["last_round"]);

                echo "</pre>";
            ?>
        </div>

        <script src="<?php echo $dir_depth ?>global_assets/js/jquery-3.6.0.min.js"></script>
        <script src="<?php echo $dir_depth ?>global_assets/js/script.js"></script>
    </body>
</html>
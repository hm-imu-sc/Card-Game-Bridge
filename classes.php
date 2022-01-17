<?php

    class JSON {
        public static function get_array($filename) {
            $file = fopen($filename, "r");
            $data = fread($file, filesize($filename));
            fclose($file);
            return json_decode($data, true);
        }

        public static function get_object($filename) {
            $file = fopen($filename, "r");
            $data = fread($file, filesize($filename));
            fclose($file);
            return json_decode($data, false);
        }

        public static function array_to_object($ar) {
            return json_decode(json_encode($ar));
        }

        public static function save($filename, $data) {
            $file = fopen($filename, "w");
            fwrite($file, json_encode($data));
            fclose($file);
        }
    }

    class WrongCardCode extends Exception {
        public function error_message() {
            return "Wrong Card Code";
        }
    }

    class WrongSuitName extends Exception {
        public function error_message() {
            return "Wrong Suit Name";
        }
    }

    class NotEnoughPlayer extends Exception {
        public function error_message() {
            return "Not Enough Player";
        }
    }

    class CycleNotStarted extends Exception {
        public function error_message() {
            return "CycleNotStarted";
        }
    }

    class PlayerNotFound extends Exception {
        public function error_message() {
            return "Player Not Found";
        }
    }

    class WrongObjectArgument extends Exception {
        public function error_message() {
            return "Object Argument Ivalid";
        }
    }

    class InvalidProperty extends Exception {
        public function error_message() {
            return "Ivalid Property";
        }
    }

    class InvalidCall extends Exception {}

    class Card {
        public $suit, $code, $weight;

        function __construct($suit=null, $code=null, $card_obj=null) {
            
            if ($card_obj != null) {

                // echo "<pre>";
                // print_r($card_obj);
                // echo "</pre>";

                $this->suit = $card_obj->suit;
                $this->code = $card_obj->code;
                $this->weight = $card_obj->weight;
            }
            else {
                $this->suit = $suit;
                $this->code = $code;

                $this->__validate_suite_name();
                $this->__assign_weight();
            }
        }

        public function is_greater_than($card) {

            if ($this->suit == "spade" && $card->suit != "spade") {
                return true;
            }

            if ($this->suit != "spade" && $card->suit == "spade") {
                return false;
            }

            if ($this->suit == $card->suit) {
                if ($this->weight > $card->weight) {
                    return true;
                }
                return false;
            }

            return true;
        }

        public function is_less_than($card) {

            if ($this->suit == "spade" && $card->suit != "spade") {
                return false;
            }

            if ($this->suit != "spade" && $card->suit == "spade") {
                return true;
            }

            if ($this->suit == $card->suit) {
                if ($this->weight < $card->weight) {
                    return true;
                }
                return false;
            }

            return false;
        }

        public function is_equal_to($card) {
            if ($this->suit == $card->suit) {
                if ($this->weight == $card->weight) {
                    return true;
                }
                return false;
            }

            return false;
        }

        public function view() {
            return "{$this->suit}_{$this->code}";
        }
        
        public function name() {
            return "{$this->suit}_{$this->code}";
        }

        private function __validate_suite_name() {
            
            $valid_suit_names = ["club", "spade", "diamond", "heart"];

            foreach ($valid_suit_names as $valid_suit_name) {
                if ($this->suit == $valid_suit_name) {
                    return;
                }
            }

            throw new WrongSuitName();
        }

        private function __assign_weight() {
            for ($i=2; $i<=10; $i++) {
                if ($this->code == $i) {
                    $this->weight = $i;
                    return;
                }
            }

            $special_weights = [["A", 14],["K", 13],["Q", 12],["J", 11]];
        
            foreach ($special_weights as $special_weight) {
                if ($this->code == $special_weight[0]) {
                    $this->weight = $special_weight[1];
                    return;
                }
            }

            throw new WrongCardCode();
        }
    }

    class Player {

        public $id, $name, $cards_in_hand=[], $score=0; 

        function __construct($name=null, $id=null, $player_obj=null) {
            if ($player_obj != null) {

                // echo "<pre>";
                // echo "</pre>";

                if (!isset($player_obj->name)) {
                    throw new WrongObjectArgument();
                }

                $this->name = $player_obj->name;
                $this->id = $player_obj->id;

                foreach ($player_obj->cards_in_hand as $card) {
                    array_push($this->cards_in_hand, [
                        "card"=> new Card(card_obj: $card->card),
                        "exists"=> $card->exists
                    ]);
                }

                $this->score = $player_obj->score;
            }
            else {
                $this->name = $name;
                $this->id = $id;
            }
        }

        public function assign_cards($cards) {
            foreach ($cards as $card) {
                array_push($this->cards_in_hand, clone $card);
            }
        }

        public function give_card($card) {
            array_push($this->cards_in_hand, [
                "card"=> clone $card,
                "exists"=> true
            ]);
        }

        public function remove_card($card) {
            for ($i=0; $i<sizeof($this->cards_in_hand); $i++) {
                if ($this->cards_in_hand[$i]["card"]->is_equal_to($card)) {
                    $this->cards_in_hand[$i]["exists"] = false;
                    return;
                }
            }
        }

        public function is_same_as($player) {

            if (!isset($player->id)) {
                throw new WrongObjectArgument();
            }

            if ($this->id == $player->id) {
                return true;
            }
            return false;
        }

        public function has_suit($suit_name) {
            foreach ($this->cards_in_hand as $card) {
                if ($card["exists"] && $card["card"]->suit == $suit_name) {
                    return true;
                }
            }
            return false;
        }
    }

    class Cycle {

        public $played_cards=[], $no_of_players, $winner;

        function __construct($no_of_players=null, $cycle_obj=null) {
            if ($cycle_obj != null) {
                foreach ($cycle_obj->played_cards as $played_card) {
                    // echo "<pre>";
                    // print_r($played_card);
                    // echo "</pre>";
                    array_push($this->played_cards, [
                        "player"=> $played_card->player,
                        "card"=> new Card(card_obj: $played_card->card)
                    ]);
                }

                $this->no_of_players = $cycle_obj->no_of_players;
                
                if (sizeof($this->played_cards) == $this->no_of_players) {
                    $this->__find_winner();
                }
            }
            else {
                $this->no_of_players = $no_of_players;
            }
        }

        public function insert($player, $card) {

            array_push($this->played_cards, [
                "player"=> $player->id,
                "card"=> clone $card
            ]);

            if (sizeof($this->played_cards) == $this->no_of_players) {
                $this->__find_winner();
            }
        }

        public function get_card_for($player_id) {
            foreach ($this->played_cards as $played_card) {
                if ($played_card["player"] == $player_id) {
                    return $played_card["card"]->view();
                }
            }

            return "null";
        }

        public function next_turn() {
            for ($i=0; $i<$this->no_of_players; $i++) {
                if(!isset($this->played_cards[$i])) {
                    return $i;
                }
            }

            return -1;
        }

        public function is_new_cycle() {
            if (sizeof($this->played_cards) == 0) {
                return true;
            }
            return false;
        }

        public function is_completed() {
            if (sizeof($this->played_cards) == $this->no_of_players) {
                return true;
            }
            return false;
        }

        public function cycle_started_by() {

            if (sizeof($this->played_cards) == 0) {
                throw new CycleNotStarted();
            }

            return $this->played_cards[0]["player"];
        }

        public function first_played_suit() {
            
            if (sizeof($this->played_cards) == 0) {
                throw new CycleNotStarted();
            }

            return $this->played_cards[0]["card"]->suit;
        }

        public function log() {

            $log = [];

            foreach ($this->played_cards as $played_card) {
                array_push($log, [
                    "player"=> "{$played_card["player"]}",
                    "card"=> $played_card["card"]->view()
                ]);
            }

            return $log;
        }

        public function get_played_cards() {
            $cards = [];
            foreach($this->played_cards as $played_card) {
                array_push($cards, [
                    "player"=> $played_card["player"],
                    "card"=> $played_card["card"]->name()
                ]);
            }
            return $cards;
        }

        private function __find_winner() {
            if (sizeof($this->played_cards) != $this->no_of_players) {
                throw new NotEnoughPlayer();
            }

            $winner = $this->played_cards[0];

            foreach ($this->played_cards as $played_card) {

                if ($winner["card"]->is_less_than($played_card["card"])) {
                    $winner = $played_card;
                }
            }

            $this->winner = $winner;
        }
    }

    class Round {

        public $players=[], $all_cards=[], $start_from = 0, $cycles=[], $current_cycle;

        function __construct($players=null, $round_obj=null) {
            if ($round_obj != null) {
                foreach ($round_obj->players as $player) {
                    array_push($this->players, [
                        "player"=> new Player(player_obj: $player->player),
                        "call"=> $player->call,
                        "won"=> $player->won,
                        "score"=> $player->score,
                    ]);
                }
                
                foreach ($round_obj->all_cards as $card) {
                    array_push($this->all_cards, new Card(card_obj: $card));
                }
                
                foreach ($round_obj->cycles as $cycle) {
                    array_push($this->cycles, new Cycle(cycle_obj: $cycle));
                }
                
                $this->start_from = $round_obj->start_from;
                $this->current_cycle = new Cycle(cycle_obj: $round_obj->current_cycle);
            }
            else {
                foreach ($players as $player) {
                    array_push($this->players, [
                        "player"=> $player,
                        "call"=> -1,
                        "won"=> 0,
                        "score"=> 0,
                    ]);
                }
            }
        }

        public function call($player_id, $call) {
            for ($i=0; $i<4; $i++) {
                if ($this->players[$i]["player"]->id == $player_id) {

                    if ($this->players[$i]["call"] != -1) {
                        throw new InvalidCall();
                    }

                    $this->players[$i]["call"] = $call;
                    return;
                }
            }
        }

        public function next_cycle() {
            if (isset($this->current_cycle)) {
                array_push($this->cycles, clone $this->current_cycle);
                
                for ($i=0; $i<4; $i++) {
                    if ($this->players[$i]["player"]->id == $this->current_cycle->winner["player"]) {
                        $this->players[$i]["won"]++;
                        break;
                    }
                }                
            }
            $this->current_cycle = new Cycle(no_of_players: sizeof($this->players));
        }

        public function next_turn() {
            if (sizeof($this->cycles) == 0) {
                $idx = $this->start_from + sizeof($this->current_cycle->played_cards);
                $idx %= $this->current_cycle->no_of_players;
                
                return $this->players[$idx]["player"]->id;
            }
            else {
                $last_winner = $this->cycles[sizeof($this->cycles)-1]->winner["player"];
                $idx = 0;

                for ($i=0; $i<4; $i++) {
                    if($this->players[$i]["player"]->id == $last_winner) {
                        $idx = $i + sizeof($this->current_cycle->played_cards);
                        $idx %= $this->current_cycle->no_of_players;
                    }
                }

                return $this->players[$idx]["player"]->id;
            }
        }

        public function play($player, $card) {
            $this->current_cycle->insert($player, $card);

            foreach ($this->players as $_player) {
                if ($_player["player"]->is_same_as($player)) {
                    $_player["player"]->remove_card($card);
                    break;
                }
            }
        }

        public function is_completed() {
            if (sizeof($this->cycles) == 13) {
                return true;
            }
            return false;
        }

        public function end() {
            // foreach ($this->cycles as $cycle) {
            //     for ($i=0; $i<4; $i++) {
            //         if ($this->players[$i]["player"]->id == $cycle->winner["player"]) {
            //             $this->players[$i]["won"]++;
            //             break;
            //         }
            //     }
            // }

            for ($i=0; $i<4; $i++) {

                if ($this->players[$i]["call"] == -1) {
                    $this->players[$i]["score"] = $this->players[$i]["won"];
                    continue;
                }

                if ($this->players[$i]["won"] >= $this->players[$i]["call"] || $this->players[$i]["won"] < $this->players[$i]["call"] + 2) {
                    $this->players[$i]["score"] = $this->players[$i]["call"];
                }
                else {
                    $this->players[$i]["score"] = -$this->players[$i]["call"];
                }
            }
        }

        public function next_round() {

            $players = [];

            foreach ($this->players as $player) {
                array_push($players, new Player(name: $player["player"]->name, id: $player["player"]->id));
            }

            $new_round = new Round(players: $players);
            $new_round->all_cards = $this->all_cards;
            $new_round->start_from = ($this->start_from + 1)%sizeof($this->players);
            return $new_round;
        }

        public function serve_cards() {
            shuffle($this->all_cards);

            $next_to_serve = 0;

            foreach ($this->all_cards as $card) {

                // print_r($this->players);
                // print_r($this->players[$next_to_serve]);
                // print_r($this->players[$next_to_serve]["player"]);
                
                $this->players[$next_to_serve]["player"]->give_card($card);
                $next_to_serve = ($next_to_serve+1) % sizeof($this->players);
            }
        }

        public function prepare_deck() {
            $suits = ["spade", "club", "diamond", "heart"];
            $codes = ["2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K", "A"];
            
            shuffle($suits);
            shuffle($codes);

            foreach ($suits as $suit) {
                shuffle($codes);
                foreach ($codes as $code) {
                    array_push($this->all_cards, new Card($suit, $code));
                }
            }
        }

        public function get_player($id) {
            foreach ($this->players as $player) {
                if ($player["player"]->id == $id) {
                    return $player["player"];
                }
            }
        }

        public function log() {

            $cycles = [];
            $players = [];

            foreach ($this->cycles as $cycle) {
                array_push($cycles, $cycle->log());
            }

            foreach ($this->players as $player) {

                $cards_in_hand = [];

                foreach ($player["player"]->cards_in_hand as $card) {
                    array_push($cards_in_hand, "<img src='assets/img/cards/{$card["card"]->view()}.png'>");
                }

                array_push($players, [
                    "player"=> "{$player["player"]->id}_{$player["player"]->name}",
                    "cards"=> $cards_in_hand
                ]);
            }

            return [
                "players"=> $players,
                "cycles"=> $cycles,
            ];
        }

        public function player_log($id) {
            $owned_cards = [];
            foreach ($this->cycles as $cycle) {
                if ($cycle->winner["player"] == $id) {
                    $played_cards = $cycle->get_played_cards();
                    $temp = [];
                    foreach($played_cards as $played_card) {
                        array_push($temp, $played_card["card"]);
                    }
                    array_push($owned_cards, $temp);
                }
            }
            return $owned_cards;
        }  
        
        public function round_log() {
            $log = [];

            foreach ($this->players as $player) {
                array_push($log, [
                    "id"=> $player["player"]->id,
                    "name"=> $player["player"]->name,
                    "call"=> $player["call"],
                    // "owned"=> $this->__count_owned_for($player["player"]),
                    "owned"=> $this->is_completed() ? $player["won"] : $this->__count_owned_for($player["player"]),
                ]);
            }

            return $log;
        }

        private function __count_owned_for($player) {
            $score = 0;
            foreach ($this->cycles as $cycle) {

                if (!isset($player->id)) {
                    throw new InvalidProperty();
                }

                if($cycle->winner["player"] == $player->id) {
                    $score++;
                }
            }
            return $score;
        }
    }

    class CallBridge {

        public $players=[], $rounds=[], $current_round;

        function __construct($players=null, $call_bridge_obj=null, $filename=null) {

            if ($call_bridge_obj != null) {
                $this->__recreate(call_bridge_obj: $call_bridge_obj);
            }
            else if ($filename != null) {
                $this->__recreate(filename: $filename);
            }
            else {
                foreach ($players as $player) {
                    array_push($this->players, clone $player);
                }
            }
        }

        public function authenticate_player($id) {
            foreach ($this->players as $player) {
                if ($player->id == $id) {
                    return true;
                }
            }

            return false;
        }

        public function start() {
            $this->__prepare_first_round();
        }

        public function call($player_id, $call) {
            $this->current_round->call($player_id, $call);
        }

        public function next_cycle() {
            $this->current_round->next_cycle();
        }

        public function play($player, $card) {
            $this->current_round->play($player, $card);
        }

        public function next_turn() {
            return $this->current_round->next_turn();
        }

        public function new_round() {
            $this->current_round->end();
            array_push($this->rounds, clone $this->current_round);
            $this->current_round = $this->current_round->next_round();
            $this->__round_start();
        }

        public function get_score() {
            $scores = [];

            foreach ($this->players as $player) {
                array_push($scores, [$player->id, 0]);
            }

            foreach ($this->rounds as $round) {
                for ($i=0; $i<4; $i++) {
                    $scores[$i][1] += $round->players[$i]["score"];
                }
            }

            return $scores;
        }

        public function log() {
            $rounds = [];

            foreach ($this->rounds as $round) {
                array_push($rounds, $round->log());
            }

            return $rounds;
        }

        public function previous_rounds_log() {
            $logs = [];

            foreach ($this->rounds as $round) {
                array_push($logs, $round->round_log());
            }

            return $logs;
        }

        private function __round_start() {
            $this->current_round->serve_cards();
            $this->current_round->next_cycle();
        }

        private function __prepare_first_round() {

            $players = [];

            foreach ($this->players as $player) {
                array_push($players, clone $player);
            }

            $this->current_round = new Round(players: $players);
            $this->current_round->prepare_deck();
            $this->__round_start();
        }

        private function __recreate($call_bridge_obj=null, $filename=null) {

            if ($filename != null) {
                $call_bridge_obj = JSON::get_object($filename);
            }

            foreach ($call_bridge_obj->players as $player) {
                array_push($this->players, new Player(player_obj: $player));
            }

            foreach ($call_bridge_obj->rounds as $round) {
                array_push($this->rounds, new Round(round_obj: $round));
            }

            $this->current_round = new Round(round_obj: $call_bridge_obj->current_round);
        }
    }
?>
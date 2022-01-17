
let turn = null;
let sync_game = null;
let wait = 0;

function tabing_css_part_1(btn_tab_1, btn_tab_2) {
    $(btn_tab_2).css("border", "2px solid aqua");
    $(btn_tab_2).css("border-right", "0");
    $(btn_tab_2).css("border-top", "0");
    $(btn_tab_2).css("border-bottom-left-radius", "10px");
    $(btn_tab_2).css("font-weight", "normal");

    $(btn_tab_1).css("border", "2px solid aqua");
    $(btn_tab_1).css("border-bottom", "0");
    $(btn_tab_1).css("border-right", "0");
    $(btn_tab_1).css("border-top-left-radius", "10px");
    $(btn_tab_1).css("font-weight", "bold");
}

function tabing_css_part_2(btn_tab_1, btn_tab_2) {
    $(btn_tab_2).css("border", "2px solid aqua");
    $(btn_tab_2).css("border-left", "0");
    $(btn_tab_2).css("border-top", "0");
    $(btn_tab_2).css("border-bottom-right-radius", "10px");
    $(btn_tab_2).css("font-weight", "normal");

    $(btn_tab_1).css("border", "2px solid aqua");
    $(btn_tab_1).css("border-bottom", "0");
    $(btn_tab_1).css("border-left", "0");
    $(btn_tab_1).css("border-top-right-radius", "10px");
    $(btn_tab_1).css("font-weight", "bold");
}

function tab_toggle(div_1, div_2) {
    $(div_1).toggle();
    $(div_2).toggle();
}

function activate_session_buttons() {

    $("#logout_btn").click(logout);

    $(".game_sessions button").click(function(){

        // console.log("activated");

        let session_id = $(this).attr("id");

        $.ajax({
            url: "game_processor.php",
            type: "POST",
            data: {job: "verify_session", session_id: session_id},
            success: function (data) {

                // console.log(data);
                // return;

                let session_validity = JSON.parse(data);

                if (session_validity["available"]) {
                    load_session(session_id);
                    game_syncroniser();
                }
                else {
                    alert(session_validity["alert"]);
                }
            }
        });
    });
}

function load_session(session_id) {
    $.ajax({
        url: "game_processor.php",
        type: "POST",
        data: {job: "load_game", session_id: session_id},
        success: function(data){
            $(".heading").remove();
            $(".game_sessions").remove();
            enable_game_session(data);
        }
    });
}

function enable_game_session(data) {

    // console.log(data);

    // return;

    $(".project").html(data);

    $("#cards_to_play").click(function() {
        tabing_css_part_1("#cards_to_play", "#cards_owned");
        tab_toggle("#cards_in_hand", "#cards_owned_");
    });
    
    $("#cards_owned").click(function(){
        tabing_css_part_2("#cards_owned", "#cards_to_play");
        tab_toggle("#cards_in_hand", "#cards_owned_");
    });
    
    $("#game_log").click(function(){
        tabing_css_part_1("#game_log", "#chat");
        tab_toggle(".game_log", ".chat");
    });
    
    $("#chat").click(function(){
        tabing_css_part_2("#chat", "#game_log");
        tab_toggle(".game_log", ".chat");
    });
    
    // $("#ready_btn").click(function(){
    //     // let val = $(this).attr("value");
    
    //     // if (val == 0) {
    //     //     $(this).css("border-color", "lime");
    //     //     $("#ready_btn h1").css("color", "lime");
    //     //     $(this).attr("value", "1");
    //     // }
    //     // else {
    //     //     $(this).css("border-color", "gray");
    //     //     $("#ready_btn h1").css("color", "gray");
    //     //     $(this).attr("value", "0");
    //     // }

    //     let call = $(".player_section .call_section #range_val").val();

    //     $.ajax({
    //         url: "game_processor.php",
    //         type: "POST",
    //         data: {job: "make_call", call_val: call},
    //         success: function (data) {

    //             // console.log(data);
    //             // return;

    //             data = JSON.parse(data);

    //             if (data["call_success"]) {
    //                 $(".player_section .call_section #call").toggle();
    //                 $(this).hide();
    //             }
    //         } 
    //     });
    // });
    
    $("#call").change(function(){
        $("#range_val").text($(this).val());
    });

    $("#logout_btn").click(logout);

    $("#discard_round_btn").css("display", "block");
    
    activate_card_play();
}

function login() {

    let id = $("#id").val();
    let passcode = $("#passcode").val();

    $.ajax({
        url: "login_processor.php",
        type: "POST",
        data: {job: "login", id: id, passcode: passcode},
        success: function(data) {

            data = JSON.parse(data);
            
            console.log(data)

            if (data["login_status"] == 1) {
                $.ajax({
                    url: "game_processor.php",
                    type: "POST",
                    data: {job: "load_sessions", id: id, token: data["token"]},
                    success: function(data) { 

                        $(".login_section").remove();
                        $(".heading").after(data);

                        activate_session_buttons();
                    }
                });
            }
            else {
                if ($(".alert_section").length==0) {
                    $(".login_section").prepend("<div class='alert_section'></div>");
                }
                $(".alert_section").html(data["alerts"]);
                $(".alert_close").click(function(){
                    $(".alert_section").remove();
                });
            }
        }
    });
}

function logout() {
    $.ajax({
        url: "login_processor.php",
        type: "POST",
        data: {job: "logout"},
        success: function(data) {
            if ($(".game_section").length == 0) {
                $(".heading").remove();
                $(".game_sessions").before(data);  
                $(".game_sessions").remove();
            }
            else {
                $(".game_section").before(data);     
                $(".game_section").remove();     
            }
            
            activate_login();

            /**
             * clear intervals 
             **/   
        }
    });
}

function activate_login() {
    $("#login_btn").click(login);
    $("#passcode").keyup(function(e) {
        if (e.key == "Enter") {
            login();
        }
    });
}

function activate_card_play() {
    let card_selector = ".player_section .card_section .cards_in_hand img";
    // alert("just testing ...");

    $(card_selector).click(function() {
        // alert("just testing ...");

        let card = this;

        $.ajax({
            url: "game_processor.php",
            type: "POST",
            data: {job: "card_play", card: $(card).attr("id")},
            success: function(data) {

                // console.log("__________________________________________");
                // console.log(data);
                // return;

                data = JSON.parse(data);
                if (data["valid"]) {
                    $($(".table_card .card")[1]).html(card);
                }
                else {
                    console.log(data["alert"]);
                }
            }
        });
    });
}

function turn_processor(turn_data) {
    turn = turn_data["id"];

    if ($("#" + turn_data["id"]).attr("active_status") != "1") {
        $(".player_name h1").attr("active_status", "0");
        $("#" + turn_data["id"]).attr("active_status", "1");
    }
}

function setup_new_round(new_round) {
    let new_cards = new_round["new_cards"];
    let suit_names = ["card_hearts", "card_clubs", "card_diamonds", "card_spades"];
    for (let i=0; i<4; i++) {
        $(".player_section .cards_in_hand ." + suit_names[i]).html(new_cards[suit_names[i]]);
    }
    activate_card_play();

    $(".player_section .owned_card_set").remove();    
    $(".player_section .cards_owned #no_of_owned_cards").text("0");

    $(".log_section .previous_round_section").prepend(new_round["last_round"]);

    for (let i=0; i<4; i++) {
        $(".log_section .total_score_section .player_score .score_" + new_round["total_scores"][i][0]).text(new_round["total_scores"][i][1]);
    }
}

function update_table() {

    $.ajax({
        url: "game_processor.php",
        type: "POST",
        data: {
            job: "table_update", 
            round_number: $(".log_section .game_log .previous_round_section .round_number").length
        },
        success: function(data) {

            // console.log(data);
            // console.log(data.length);
            // return;

            data = JSON.parse(data);

            if (data["new_round"]["status"]) {
                setup_new_round(data["new_round"]);
            }

            let selector = ".playing_table .row .table_card ";

            for (let i=0; i<data["table_card_update"].length; i++) {

                let id = "#card_" + data["table_card_update"][i]["player_id"];

                // console.log(selector + id);

                if ($(selector + id + " img").length == 0) {
                    $(selector + id).html(data["table_card_update"][i]["card"]);
                }
            }
        }
    });
}

function update_my_log() {

    let len = Number($(".player_section #no_of_owned_cards").text());

    $.ajax({
        url: "game_processor.php",
        type: "POST",
        data: {job: "update_owned_card", have: len},
        success: function(data) {
            data = JSON.parse(data);

            if (data["update_length"] != 0) {
                $(".player_section #no_of_owned_cards").text(len + data["update_length"]);
                $(".player_section .owned_cards").append(data["updates"]);
            }
        }
    });
}

function update_game_log() {
    $.ajax({
        url: "game_processor.php",
        type: "POST",
        data: {job: "update_round"},
        success: function(data) {
            data = JSON.parse(data);

            for (let i=0; i<4; i++) {
                $(".log_section #log_" + data[i]["id"] + " .owned").text("Owned: " + data[i]["owned"]);
            }
        }
    });
}

function game_syncroniser() {
    sync_game = setInterval(function(){
        $.ajax({
            url: "game_processor.php",
            type: "POST",
            data: {job: "sync_game"},
            success: function(data) {

                // console.log("__________________________________________");
                // console.log(data);
                // clearInterval(sync_game);
                // return;

                data = JSON.parse(data);
                turn_processor(data["turn"]);
                update_table();
                update_my_log();
                update_game_log();

                if (data["is_new_cycle"]) {

                    // wait = 3000;

                    // let t_int = setInterval(function(){

                    //     console.log("wait: " + wait);

                    //     if (wait == 0) {
                    //         clearInterval(t_int);
                    //     }

                    //     wait -= 1000;

                    // }, 1000);

                    $(".playing_table .card img").remove();
                }
            }
        });
    }, 500);
}

if ($(".game_sessions").length == 1) {
    activate_session_buttons();
}
else {
    activate_login();
}

// just to develop, comment out on deployment
$(".system").hide();
// $("#cards_owned").click();
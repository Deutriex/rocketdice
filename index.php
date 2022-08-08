<html>
<head>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<title>Rocket Dice strategy analyzer</title>

<style>
    .probability_board TD, .probability_board TH {
        width: 30px;
        height: 30px;
        text-align: center;
        margin: 0px;
    }

    .probability_board, .martingale_table > TABLE {
        border-collapse: collapse;
    }

    .winning {
        background-color: #CCFFCC;
    }

    .losing {
        background-color: #FFCCCC;
    }

    .selected, TR > .selected {
        background-color: #CFDEF3;
    }

    .criteria_table TD {
        cursor: pointer;
    }
</style>

<script>
    var Game = new (function(){
        this.selectThreshold = function(num, crit){
            var cells = document.querySelectorAll(".probability_board TD");
            cells = [].slice.call(cells);
            var winning_count = 0
            cells.map(x => {
                if ((crit == 0 && +x.innerText > num) || (crit == 1 && +x.innerText < num)){
                    x.className = "winning"
                    winning_count++
                } else {
                    x.className = "losing"
                }
            })
            var martingale_table = document.querySelectorAll(".multiplier_stats > TR > TD")
            var get_multiplier = Game.get_multiplier(num, crit)
            var get_martingale = Game.calc_martingale(get_multiplier)
            document.querySelector(".win_mul").innerText = get_multiplier + "x"
            document.querySelector(".martingale_mul").innerText = get_martingale.toFixed(2) + "x"

            var martingale_model = document.querySelector(".martingale_model")
            var martingale_array

            if (winning_count/36 < 0.5){
                martingale_model.selectedIndex = 0
            }

            if (martingale_model.selectedIndex == 0){
                martingale_array = Game.get_martingale_table(1, 100, 1-winning_count/36, get_martingale)
            } else {
                martingale_array = Game.get_martingale_table_v2(1, 100, 1-winning_count/36, get_multiplier)
            }


            computed_table = "<table>" + martingale_array.map((x, i) => "<tr class=\"" + ((i == 0) ? "selected" : "") + "\"><th>" + x.iteration + "</th><td>R$ " + x.bet.toFixed(2) + "</td><td>" + (x.cumuled_losing_odds*100).toFixed(2) + "%</td><td>0</td></tr>").join("") + "</table>"

            document.querySelector(".martingale_table").innerHTML = computed_table

            var criteria_cells = document.querySelectorAll(".criteria_table TD")
            criteria_cells = [].slice.call(criteria_cells)
            criteria_cells.forEach(x => x.className = "")

            event.target.className = "selected"
        }

        this.calc_martingale = function(multiplier){
            return 1 + 1/(multiplier - 1)
        }

        this.get_multiplier = function(num, crit){
            var mul = [1.01, 1.07, 1.18, 1.36, 1.68, 2.35, 3.53, 5.88, 11.8, 35.3]
            if (crit){
                num = 14 - num
            }
            arr_idx = num - 2
            return mul[arr_idx]
        }

        this.get_martingale_table = function(starting_bet, max_bet, losing_odds, multiplier){
            var martingale_array = []
            var current_bet = starting_bet
            var is_bankrupt = false
            var iteration = 1
            var allowed_values = [1, 2, 3, 5, 10, 15, 20, 30, 50, 75, 100]
            var last_std_bet_value = 0

            while (!is_bankrupt){
                var standarized_bet_value = allowed_values.filter(x => x >= current_bet)[0]

                martingale_array.push({iteration: iteration, bet: standarized_bet_value, cumuled_losing_odds: Math.pow(losing_odds, iteration)})
                if (standarized_bet_value < max_bet){
                    current_bet = Math.min(standarized_bet_value*multiplier, max_bet)
                    iteration++
                } else {
                    is_bankrupt = true
                }
            }

            return martingale_array
        }

        this.get_martingale_table_v2 = function(starting_bet, max_bet, losing_odds, winning_multiplier){
            var martingale_array = []
            var current_bet = starting_bet
            var is_bankrupt = false
            var iteration = 1
            var allowed_values = [1, 2, 3, 5, 10, 15, 20, 30, 50, 75, 100]
            var last_std_bet_value = 0
            var loss_sum = 0
            var delta = 0

            while (!is_bankrupt && iteration < 100){
                var standarized_bet_value = allowed_values.filter(x => x >= current_bet)[0]

                martingale_array.push({iteration: iteration, bet: standarized_bet_value, cumuled_losing_odds: Math.pow(losing_odds, iteration), loss_sum: loss_sum, delta: delta})
                if (standarized_bet_value < max_bet){
                    loss_sum += standarized_bet_value
                    current_bet = Math.min(loss_sum/(winning_multiplier - 1), max_bet)

                    current_bet -= delta
                    var next_value = allowed_values.filter(x => x >= current_bet)[0]

                    delta += next_value - current_bet
                    iteration++
                } else {
                    is_bankrupt = true
                }
            }

            return martingale_array
        }        

        this.martingale_next = function(){
            var selected_elem = document.querySelector(".martingale_table > TABLE TR.selected")
            selected_elem.querySelector("TD:last-child").innerText++            
            var next_index = selected_elem.rowIndex+1

            var martingale_table = document.querySelectorAll(".martingale_table > TABLE TR")            
            martingale_table = [].slice.call(martingale_table)
            martingale_table.map(x => x.className = "")

            if (next_index < martingale_table.length){
                martingale_table[next_index].className = "selected";
                document.querySelector(".gain_btn").style.display = "initial";                
            } else {
                martingale_table[0].className = "selected"
                document.querySelector(".gain_btn").style.display = "none";                
            }
        }

        this.martingale_reset = function(){
            var martingale_table = document.querySelectorAll(".martingale_table > TABLE TR")            
            martingale_table = [].slice.call(martingale_table)
            martingale_table.map(x => x.className = "")
            martingale_table[0].className = "selected"
            document.querySelector(".gain_btn").style.display = "none";
        }

        this.keyboardHandler = function(){
            if (event.keyCode == 32) Game.martingale_reset()
            if (event.keyCode == 13) Game.martingale_next()
        }

        this.getWinningOdds = function(num, crit){
            let numbers = [1, 2, 3, 4, 5, 6]
            let winning_count = 0

            numbers.forEach(x => {
                numbers.forEach(y => {
                    if ((crit == 0 && x+y>num) || (crit == 1 && x+y<num)){
                        winning_count++
                    }
                })
            })

            return winning_count/36
        }

        this.simmulateGame = function(balance, num, crit, martingale_table, max_iteration){
            var history = []
            var iteration = 1
            var margingale_pointer = 0

            while (max_iteration-- && balance > 0){
                var dice1 = Math.ceil(Math.random()*6)
                var dice2 = Math.ceil(Math.random()*6)
                var result = dice1+dice2
                var winner = false
                if ((crit == 0 && result > num) || (crit == 1 && result < num)){
                    winner = true
                }

                var mul = Game.get_multiplier(num, crit)
                var bet = martingale_table[margingale_pointer].bet

                if (winner){ 
                    balance += bet*(mul - 1)
                    margingale_pointer = 0
                } else {
                    margingale_pointer = (margingale_pointer+1)%martingale_table.length
                    balance = Math.max(balance - bet, 0)
                }

                var parsed_balance = 0
                if (balance.toFixed){
                    parsed_balance = +(balance.toFixed(2))
                } else (
                    console.log(balance)
                )

                history.push({iteration: iteration++, bet: bet, winner: winner, resulting_balance: parsed_balance})
            }

            return history
        }

        this.drawSimulationChart = function(){
            var selected_criteria = document.querySelector(".criteria_table TD.selected").onclick.toString()
            selected_criteria = selected_criteria.split("selectThreshold(")[1].split(")")[0].split(", ")
            var num = +selected_criteria[0]
            var crit = +selected_criteria[1]
            var balance = document.querySelector(".balance").value
            var plays = document.querySelector(".plays").value

            var get_multiplier = Game.get_multiplier(num, crit)
            var get_martingale = Game.calc_martingale(get_multiplier)
            var get_winning_odds = Game.getWinningOdds(num, crit)
            var martingale_table

            //get_martingale_table_v2

            var martingale_model = document.querySelector(".martingale_model").selectedIndex
            if (martingale_model.selectedIndex == 0){
                martingale_table = Game.get_martingale_table(1, 100, 1-get_winning_odds, get_martingale)
            } else {
                martingale_table = Game.get_martingale_table_v2(1, 100, 1-get_winning_odds, get_multiplier)
            }

            var evolution = Game.simmulateGame(balance, num, crit, martingale_table, plays)
            console.log(evolution)

            google.charts.load('current', {packages: ['corechart', 'line']});
            google.charts.setOnLoadCallback(() => {
                var data = new google.visualization.DataTable();
                data.addColumn('number', 'X');
                data.addColumn('number', 'Balance');
                var rows = evolution.map(x => [x.iteration, x.resulting_balance])
                data.addRows(rows);

                var options = {
                    hAxis: {
                    title: 'Plays'
                    },
                    vAxis: {
                    title: 'Balance'
                    }
                };

                var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
                chart.draw(data, options);

            });


        }
    })()
</script>

</head>
<body onkeypress="Game.keyboardHandler()">

<?php
    function calc_martingale($multiplier){
        return 1 + 1/($multiplier - 1);
    }

    const OVER = 0;
    const UNDER = 1;

    function get_winning_odds($number, $criteria = OVER){
        $numbers = array(1, 2, 3, 4, 5, 6);
        $combinations = array();

        foreach($numbers as $x){
            foreach($numbers as $y){
                if (($criteria == OVER && $x+$y > $number) || ($criteria == UNDER && $x+$y < $number)){
                    array_push($combinations, $x+$y);
                }
            }
        }


        return count($combinations)/36;
    }

    function get_multiplier($number, $criteria = OVER){
        $mul = array(1.01, 1.07, 1.18, 1.36, 1.68, 2.35, 3.53, 5.88, 11.8, 35.3);
        if ($criteria == UNDER){
            $number = 14 - $number;
        }
        $arr_idx = $number - 2;

        return $mul[$arr_idx];
    }

    echo "<div style=\"display: inline-flex\">";
    echo "<div>";
    echo "<table class=\"probability_board\">";
    echo "<tr>";
    for ($y = 0; $y <= 6; $y++){
        echo "<th>" . (($y > 0) ? "$y" : "") . "</th>";
    }
    echo "</tr>";


    for ($y = 1; $y <= 6; $y++){
        echo "<tr>";
        echo "<th>" . $y . "</th>";
        for ($x = 1; $x <= 6; $x++){
            echo "<td>" . $x + $y . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>"; 
    echo "</div>";

    echo "<div>";
    echo "<table class=\"criteria_table\">";
    echo "<tr>";
    echo "<th></th>";
    echo "<th>OVER</th>";
    echo "<th>UNDER</th>";
    echo "</tr>";

    for ($y = 2; $y <= 12; $y++){
        echo "<tr>";
        echo "<th>" . $y . "</th>";
        echo "<td onclick=\"Game.selectThreshold(" . $y . ", " . OVER . ")\">" . number_format(get_winning_odds($y, OVER)*100, 2) . "%</td>";
        echo "<td onclick=\"Game.selectThreshold(" . $y . ", " . UNDER . ")\">" . number_format(get_winning_odds($y, UNDER)*100, 2) . "%</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "</div>";

    echo "<div>";
    echo "<table class=\"multiplier_stats\">";
    echo "<tr><th>Simulate</th><th>";

    echo "<table>";
    echo "<tr><td>Balance</td><td><input type=\"number\" class=\"balance\"></td></tr>";
    echo "<tr><td>Plays</td><td><input type=\"number\" class=\"plays\"></td></tr>";
    echo "<tr><td colspan=\"2\"><button onclick=\"Game.drawSimulationChart()\">Simulate</button></td></tr>";
    echo "</table>";


    echo "</th></tr>";
    echo "<tr><th>Martingale model</th><th><select class=\"martingale_model\"><option>v1</option><option>v2</option></select></th></tr>";
    echo "<tr><th>Winning multiplier</th><td class=\"win_mul\"></td></tr>";
    echo "<tr><th>Martingale multiplier</th><td class=\"martingale_mul\"></td></tr>";
    echo "</table>";

    echo "<div class=\"martingale_table\"></div>";

    echo "<button onclick=\"Game.martingale_next()\">loss</button><button class=\"gain_btn\" style=\"display: none\" onclick=\"Game.martingale_reset()\">gain</button>";

    echo "</div>";


    echo "</div>";
?>

<div id="chart_div"></div>

</body>
</html>
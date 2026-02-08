<?php

// Admin Area: expedition settings & simulator.

class Admin_Expedition extends Page {

    private bool $show_pie = false;
    private string $pie_values = "";

    public function controller () : bool {

        // POST request processing.
        if ( method () === "POST" )
        {

            // Simulate expeditions
            if (key_exists('action', $_GET) && $_GET['action'] === "sim") {

                $result = array();
                $exptab = LoadExpeditionSettings ();

                $result[EXP_NOTHING] = 0;
                $result[EXP_ALIENS] = 0;
                $result[EXP_PIRATES] = 0;
                $result[EXP_DARK_MATTER] = 0;
                $result[EXP_BLACK_HOLE] = 0;
                $result[EXP_DELAY] = 0;
                $result[EXP_ACCEL] = 0;
                $result[EXP_RESOURCES] = 0;
                $result[EXP_FLEET] = 0;
                $result[EXP_TRADER] = 0;            

                $expcount = intval ($_POST['expcount']);
                for ($i=0; $i<$expcount; $i++) {

                    $visits = 0;
                    $hold_time = 1;   // in hours
                    $exp_res = Expedition ($visits, $exptab, $hold_time);
                    $result[$exp_res]++;
                }

                $this->show_pie = true;
                $first = true;

                foreach ($result as $i=>$val) {
                    if (!$first) {
                        $this->pie_values .= ", ";
                    }                
                    $this->pie_values .= $val;
                    $first = false;
                }
            }

            // New Settings
            if (key_exists('action', $_GET) && $_GET['action'] === "settings") {

                if (key_exists('dm_factor', $_POST)) $exptab['dm_factor'] = intval($_POST['dm_factor']);

                if (key_exists('chance_success', $_POST)) $exptab['chance_success'] = intval($_POST['chance_success']);

                if (key_exists('depleted_min', $_POST)) $exptab['depleted_min'] = intval($_POST['depleted_min']);
                if (key_exists('depleted_med', $_POST)) $exptab['depleted_med'] = intval($_POST['depleted_med']);
                if (key_exists('depleted_max', $_POST)) $exptab['depleted_max'] = intval($_POST['depleted_max']);
                if (key_exists('chance_depleted_min', $_POST)) $exptab['chance_depleted_min'] = intval($_POST['chance_depleted_min']);
                if (key_exists('chance_depleted_med', $_POST)) $exptab['chance_depleted_med'] = intval($_POST['chance_depleted_med']);
                if (key_exists('chance_depleted_max', $_POST)) $exptab['chance_depleted_max'] = intval($_POST['chance_depleted_max']);

                if (key_exists('chance_alien', $_POST)) $exptab['chance_alien'] = intval($_POST['chance_alien']);
                if (key_exists('chance_pirates', $_POST)) $exptab['chance_pirates'] = intval($_POST['chance_pirates']);
                if (key_exists('chance_dm', $_POST)) $exptab['chance_dm'] = intval($_POST['chance_dm']);
                if (key_exists('chance_lost', $_POST)) $exptab['chance_lost'] = intval($_POST['chance_lost']);
                if (key_exists('chance_delay', $_POST)) $exptab['chance_delay'] = intval($_POST['chance_delay']);
                if (key_exists('chance_accel', $_POST)) $exptab['chance_accel'] = intval($_POST['chance_accel']);
                if (key_exists('chance_res', $_POST)) $exptab['chance_res'] = intval($_POST['chance_res']);
                if (key_exists('chance_fleet', $_POST)) $exptab['chance_fleet'] = intval($_POST['chance_fleet']);

                if (key_exists('score_cap1', $_POST)) $exptab['score_cap1'] = intval($_POST['score_cap1']);
                if (key_exists('score_cap2', $_POST)) $exptab['score_cap2'] = intval($_POST['score_cap2']);
                if (key_exists('score_cap3', $_POST)) $exptab['score_cap3'] = intval($_POST['score_cap3']);
                if (key_exists('score_cap4', $_POST)) $exptab['score_cap4'] = intval($_POST['score_cap4']);
                if (key_exists('score_cap5', $_POST)) $exptab['score_cap5'] = intval($_POST['score_cap5']);
                if (key_exists('score_cap6', $_POST)) $exptab['score_cap6'] = intval($_POST['score_cap6']);
                if (key_exists('score_cap7', $_POST)) $exptab['score_cap7'] = intval($_POST['score_cap7']);
                if (key_exists('score_cap8', $_POST)) $exptab['score_cap8'] = intval($_POST['score_cap8']);
                if (key_exists('limit_cap1', $_POST)) $exptab['limit_cap1'] = intval($_POST['limit_cap1']);
                if (key_exists('limit_cap2', $_POST)) $exptab['limit_cap2'] = intval($_POST['limit_cap2']);
                if (key_exists('limit_cap3', $_POST)) $exptab['limit_cap3'] = intval($_POST['limit_cap3']);
                if (key_exists('limit_cap4', $_POST)) $exptab['limit_cap4'] = intval($_POST['limit_cap4']);
                if (key_exists('limit_cap5', $_POST)) $exptab['limit_cap5'] = intval($_POST['limit_cap5']);
                if (key_exists('limit_cap6', $_POST)) $exptab['limit_cap6'] = intval($_POST['limit_cap6']);
                if (key_exists('limit_cap7', $_POST)) $exptab['limit_cap7'] = intval($_POST['limit_cap7']);
                if (key_exists('limit_cap8', $_POST)) $exptab['limit_cap8'] = intval($_POST['limit_cap8']);
                if (key_exists('limit_max', $_POST)) $exptab['limit_max'] = intval($_POST['limit_max']);

                SaveExpeditionSettings ($exptab);
            }
        }

        return true;
    }

    public function view () : void {
        global $session;

        $exptab = LoadExpeditionSettings ();

?>
<h2><?=loca("ADM_EXP_SETTINGS");?></h2>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Expedition&action=settings" method="POST">
<table>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_DM_FACTOR");?></td> <td> <input type=text size=20 name=dm_factor value="<?=$exptab['dm_factor'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_SUCCESS");?></td> <td> <input type=text size=20 name=chance_success value="<?=$exptab['chance_success'];?>"></td></tr>

<tr><td class=c colspan=2><?=loca("ADM_EXP_SETTINGS_DEPLETION");?></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_DEPLETED_MIN");?></td> <td> <input type=text size=20 name=depleted_min value="<?=$exptab['depleted_min'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_DEPLETED_MED");?></td> <td> <input type=text size=20 name=depleted_med value="<?=$exptab['depleted_med'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_DEPLETED_MAX");?></td> <td> <input type=text size=20 name=depleted_max value="<?=$exptab['depleted_max'];?>"></td></tr>

<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_DEPLETED_MIN");?></td> <td> <input type=text size=20 name=chance_depleted_min value="<?=$exptab['chance_depleted_min'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_DEPLETED_MED");?></td> <td> <input type=text size=20 name=chance_depleted_med value="<?=$exptab['chance_depleted_med'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_DEPLETED_MAX");?></td> <td> <input type=text size=20 name=chance_depleted_max value="<?=$exptab['chance_depleted_max'];?>"></td></tr>

<tr><td class=c colspan=2><?=loca("ADM_EXP_SETTINGS_CHANCE");?></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_ALIEN");?></td> <td> <input type=text size=20 name=chance_alien value="<?=$exptab['chance_alien'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_PIRATES");?></td> <td> <input type=text size=20 name=chance_pirates value="<?=$exptab['chance_pirates'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_DM");?></td> <td> <input type=text size=20 name=chance_dm value="<?=$exptab['chance_dm'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_LOST");?></td> <td> <input type=text size=20 name=chance_lost value="<?=$exptab['chance_lost'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_DELAY");?></td> <td> <input type=text size=20 name=chance_delay value="<?=$exptab['chance_delay'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_ACCEL");?></td> <td> <input type=text size=20 name=chance_accel value="<?=$exptab['chance_accel'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_RES");?></td> <td> <input type=text size=20 name=chance_res value="<?=$exptab['chance_res'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_FLEET");?></td> <td> <input type=text size=20 name=chance_fleet value="<?=$exptab['chance_fleet'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CHANCE_TRADER");?></td> <td> &nbsp; </td></tr>

<tr><td class=c colspan=2><?=loca("ADM_EXP_SETTINGS_CAP_INFO");?></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP1");?></td> <td> <input type=text size=20 name=score_cap1 value="<?=$exptab['score_cap1'];?>">  <input type=text size=20 name=limit_cap1 value="<?=$exptab['limit_cap1'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP2");?></td> <td> <input type=text size=20 name=score_cap2 value="<?=$exptab['score_cap2'];?>">  <input type=text size=20 name=limit_cap2 value="<?=$exptab['limit_cap2'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP3");?></td> <td> <input type=text size=20 name=score_cap3 value="<?=$exptab['score_cap3'];?>">  <input type=text size=20 name=limit_cap3 value="<?=$exptab['limit_cap3'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP4");?></td> <td> <input type=text size=20 name=score_cap4 value="<?=$exptab['score_cap4'];?>">  <input type=text size=20 name=limit_cap4 value="<?=$exptab['limit_cap4'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP5");?></td> <td> <input type=text size=20 name=score_cap5 value="<?=$exptab['score_cap5'];?>">  <input type=text size=20 name=limit_cap5 value="<?=$exptab['limit_cap5'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP6");?></td> <td> <input type=text size=20 name=score_cap6 value="<?=$exptab['score_cap6'];?>">  <input type=text size=20 name=limit_cap6 value="<?=$exptab['limit_cap6'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP7");?></td> <td> <input type=text size=20 name=score_cap7 value="<?=$exptab['score_cap7'];?>">  <input type=text size=20 name=limit_cap7 value="<?=$exptab['limit_cap7'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP8");?></td> <td> <input type=text size=20 name=score_cap8 value="<?=$exptab['score_cap8'];?>">  <input type=text size=20 name=limit_cap8 value="<?=$exptab['limit_cap8'];?>"></td></tr>
<tr><td class=d><?=loca("ADM_EXP_SETTINGS_CAP_MAX");?></td> <td> <input type=text size=20 name=limit_max value="<?=$exptab['limit_max'];?>"> </td></tr>

<tr><td colspan=2 class=d><center><input type="submit" value="<?=loca("ADM_EXP_SETTINGS_SUBMIT");?>"></center></td></tr>
</table>
</form>

<?=loca("ADM_EXP_SETTINGS_TIP");?>




<h2><?=loca("ADM_EXP_SIM");?></h2>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Expedition&action=sim" method="POST">
<table>
<tr><td class=d><?=loca("ADM_EXP_SIM_EXPCOUNT");?></td> <td> <input type=text size=20 name=expcount value="<?=key_exists('expcount', $_POST) ? $_POST['expcount'] : 1000;?>"></td></tr>
<tr><td colspan=2 class=d><center><input type="submit" value="<?=loca("ADM_EXP_SIM_SUBMIT");?>"></center></td></tr>
</table>
</form>


<?php
    if ($this->show_pie) {
?>

<script src="js/chart.js"></script>

<canvas id="myChart" style="width:100%;max-width:800px"></canvas>

<script>
var xValues = [
    "<?=loca("ADM_EXP_NOTHING");?>", 
    "<?=loca("ADM_EXP_ALIENS");?>", 
    "<?=loca("ADM_EXP_PIRATES");?>", 
    "<?=loca("ADM_EXP_DARK_MATTER");?>", 
    "<?=loca("ADM_EXP_BLACK_HOLE");?>", 
    "<?=loca("ADM_EXP_DELAY");?>", 
    "<?=loca("ADM_EXP_ACCEL");?>", 
    "<?=loca("ADM_EXP_RESOURCES");?>", 
    "<?=loca("ADM_EXP_FLEET");?>", 
    "<?=loca("ADM_EXP_TRADER");?>" ];
var yValues = [<?=$this->pie_values;?>];
var barColors = ["#404040","#92ffdc","#ffb592","#33bcdb","#d11515","#ff5e00","#00c23a","#2242e2","#dddddd","#fbbc04"];

var pieOptions = {
  elements: {
    arc: {
      borderWidth: 0
    }
  },
  events: false,
  title: {
    display: true,
    text: "<?=loca("ADM_EXP_SIM_RESULT");?>"
  },
  animation: {
    duration: 200,
    easing: "easeOutQuart",
    onComplete: function () {
      var ctx = this.chart.ctx;
      ctx.font = Chart.helpers.fontString(9, 'bold', Chart.defaults.global.defaultFontFamily);
      ctx.textAlign = 'center';
      ctx.textBaseline = 'bottom';

      this.data.datasets.forEach(function (dataset) {

        for (var i = 0; i < dataset.data.length; i++) {
          var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
              total = dataset._meta[Object.keys(dataset._meta)[0]].total,
              mid_radius = model.innerRadius + (model.outerRadius - model.innerRadius)/2,
              start_angle = model.startAngle,
              end_angle = model.endAngle,
              mid_angle = start_angle + (end_angle - start_angle)/2;

          var x = mid_radius * Math.cos(mid_angle);
          var y = mid_radius * Math.sin(mid_angle);

          ctx.fillStyle = '#111';
          var percent = String((dataset.data[i]/total*100).toFixed(2)) + "%";
          ctx.fillText(dataset.data[i], model.x + x, model.y + y);
          // Display percent in another line, line break doesn't work for fillText
          ctx.fillText(percent, model.x + x, model.y + y + 15);
        }
      });               
    }
  }
};

Chart.defaults.global.defaultFontColor = "#fff";

new Chart("myChart", {
  type: "doughnut",
  data: {
    labels: xValues,
    datasets: [{
      backgroundColor: barColors,
      data: yValues
    }]
  },
  options: pieOptions
});
</script>

<?php
    } // show pie

    } // view
}

?>
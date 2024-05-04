<?php

// Admin Area: expedition settings & simulator.

function Admin_Expedition ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $show_pie = false;
    $pie_values = "";

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

            $show_pie = true;
            $first = true;

            foreach ($result as $i=>$val) {
                if (!$first) {
                    $pie_values .= ", ";
                }                
                $pie_values .= $val;
                $first = false;
            }
        }
    }

?>

<?=AdminPanel();?>



<h2><?=loca("ADM_EXP_SIM");?></h2>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Expedition&action=sim" method="POST">
<table>
<tr>
    <td class=d><?=loca("ADM_EXP_SIM_EXPCOUNT");?></td> <td> <input type=text size=20 name=expcount value="<?=key_exists('expcount', $_POST) ? $_POST['expcount'] : 1000;?>"></td>
</tr>

<tr>   <td colspan=2 class=d><center><input type="submit" value="<?=loca("ADM_EXP_SIM_SUBMIT");?>"></center></td></tr>

</table>
</form>


<?php
    if ($show_pie) {
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
var yValues = [<?=$pie_values;?>];
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
?>




<?php

    //$exptab = LoadExpeditionSettings ();
    //print_r ( $exptab );

}
?>
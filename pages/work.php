<div class="headerTitle">
    <h1>Praca</h1>
</div>

<div class="mainBoxContent">
<div class="circle-container"></div>

    <div class="title">Rynek</div>
    <hr>
    <div class="workContent">
        <div class="workDescription">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lectus metus, viverra non erat et, condimentum pulvinar nulla. Curabitur vitae nibh convallis, ultrices nisi auctor, lacinia nulla. Vestibulum id sapien rutrum, sagittis dolor ac, blandit risus. Nunc rutrum vehicula augue, vitae dignissim mauris iaculis at. Duis aliquet dictum metus sodales facilisis.
        <div class="workEarning">
            <div>
                <input type="submit" name="signin" value="Rozpocznij" class="mainButton" onclick="startProgress()"/>
            </div>
            <div class="timeLevelEarning">
                <hr>
                Czas: <b> 8 godzin </b>
                <hr>
                Zarobek: <b> 247 </b>
                <hr>
            </div>
        </div>
        <div class="progress-bar1">
            <?php
            $max_date = 5*60;
            echo '<input type="hidden" id="max_date" value="' . $max_date . '">';
            ?>
            <div class="prog striped animated green">
                <div class="progBar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" id="myBar">
                    <span class="srOnly"></span>
                </div>
            </div>
        </div>
        </div>

        
        <div class="imageWork">
            <img src="img/work/empty-market.png"/>
        </div>
    </div>

</div>

<script>
var totalTime = document.getElementById('max_date').value;
var progressBar = document.getElementById("myBar");
progressBar.innerHTML = "Ukończono";

function startProgress() {

  var startTime = Date.now();
  const secondsInDay = (24 * 60 * 60*1000);

  var intervalId = setInterval(function() {
    var elapsedTime = (Date.now() - startTime) / 1000;
    var progressPercent = (elapsedTime / totalTime) * 100;
    progressBar.style.width = progressPercent + "%";

    // Elapsed time display
    var elapsedTimeSeconds = new Date(elapsedTime * 1000);
    var totalTimeSeconds = new Date(totalTime * 1000);
    var elapsedTimeDisplay= new Date (totalTimeSeconds.getTime() - elapsedTimeSeconds.getTime());

    // if hours is less than 1 hour display 0
    var diffInHrs = elapsedTimeDisplay / (1000 * 60 * 60);
    var days = Math.floor(elapsedTimeDisplay / secondsInDay);
    if(days < 0){
        if (diffInHrs < 1 ) 
            elapsedTimeDisplay.setHours(0);
        else{
            elapsedTimeDisplay.setHours(elapsedTimeDisplay.getHours() - 1);
        }
    }
    else {
        elapsedTimeDisplay.setDate(elapsedTimeDisplay.getDate() - 1);
        elapsedTimeDisplay.setHours(elapsedTimeDisplay.getHours() - 1);
    }

    // Display time
    if(days > 0)
    progressBar.innerHTML = elapsedTimeDisplay.getDate() + ":"+elapsedTimeDisplay.getHours() + ":"+ elapsedTimeDisplay.getMinutes() +":"+elapsedTimeDisplay.getSeconds() ;
    else 
    progressBar.innerHTML = +elapsedTimeDisplay.getHours() + ":"+ elapsedTimeDisplay.getMinutes() +":"+elapsedTimeDisplay.getSeconds() ;

    if (elapsedTime >= totalTime) {
      clearInterval(intervalId);
      progressBar.innerHTML = "Ukończono";
    }
  }, 10);
}


</script>
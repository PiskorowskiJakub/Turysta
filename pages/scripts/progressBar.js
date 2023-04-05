function onLoadProgressBar(){
//var totalTime = document.getElementById('max_date').value;
var progressBar = document.getElementById("myBar");
progressBar.innerHTML = "Ukończono";
}

function startProgress() {
    var totalTime = document.getElementById('max_date').value;
    var progressBar = document.getElementById("myBar");
    //progressBar.innerHTML = "Ukończono";

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


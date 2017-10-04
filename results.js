window.onload = function () {
	var now = new Date();
	var dateStart16 = null;
	
	if (document.getElementById('StartTime_7') != null) {
		dateStart16 = document.getElementById('StartTime_7').innerHTML;
		var diff16 = (Date.parse(now) - Date.parse(dateStart16)) / 1000;
		display16 = document.querySelector('#time_7');
		if (display16 != null) {
			if (diff16 < 0) {
				startTimer(-diff16, display16, true);
			} else {
				startTimer(diff16, display16, false);
			}
		}
	}
	
	var dateStart31 = null;
	if (document.getElementById('StartTime_6') != null) {
		dateStart31 = document.getElementById('StartTime_6').innerHTML;
		var diff31 = (Date.parse(now) - Date.parse(dateStart31)) / 1000;
		display31 = document.querySelector('#time_6');
		if (display31 != null) {
			if (diff31 < 0) {
				startTimer(-diff31, display31, true);
			} else {
				startTimer(diff31, display31, false);
			}
		}
	}
	
	var dateStart65 = null;
	if (document.getElementById('StartTime_5') != null) {
		dateStart65 = document.getElementById('StartTime_5').innerHTML;
		var diff65 = (Date.parse(now) - Date.parse(dateStart65)) / 1000;
		display65 = document.querySelector('#time_5');
		if (display65 != null) {
			if (diff65 < 0) {
				startTimer(-diff65, display65, true);
			} else {
				startTimer(diff65, display65, false);
			}
		}
	}
	
	var dateStart110 = null;
	if (document.getElementById('StartTime_4') != null) {
		dateStart110 = document.getElementById('StartTime_4').innerHTML;
		var diff110 = (Date.parse(now) - Date.parse(dateStart110)) / 1000;
		display110 = document.querySelector('#time_4');	
		if (display110 != null) {
			if (diff110 < 0) {
				startTimer(-diff110, display110, true);
			} else {
				startTimer(diff110, display110, false);
			}
		}		
	}
	
};

function startTimer(duration, display, backwards) {
    var timer = duration, hours, minutes, seconds;
    setInterval(function () {
		hours = parseInt(timer / 3600, 10)
        minutes = parseInt((timer / 60) % 60, 10)
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = hours + ":" + minutes + ":" + seconds;
		if (backwards) {
			if (--timer < 0) {
				timer = duration;
			}
		} else {
			if (++timer < 0) {
				timer = duration;
			}

		}
    }, 1000);
}


/* Toggle between adding and removing the "responsive" class to topnav when the user clicks on the icon */
function myFunction() {
    var x = document.getElementById("myTopnav");
    if (x.className === "topnav") {
        x.className += " responsive";
    } else {
        x.className = "topnav";
    }
}



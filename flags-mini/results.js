/* Toggle between adding and removing the "responsive" class to topnav when the user clicks on the icon */
function myFunction() {
    var x = document.getElementById("myTopnav");
    if (x.className === "topnav") {
        x.className += " responsive";
    } else {
        x.className = "topnav";
    }
}

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

window.onload = function () {
    var hundredMinutes = 60 * 100,
        display = document.querySelector('#time');
    startTimer(hundredMinutes, display, false);
};
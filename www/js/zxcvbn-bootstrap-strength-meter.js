//Requires zxcvbn.js and Bootstrap
(function ($) {

	$.fn.zxcvbnProgressBar = function (options) {

		//init settings
		var settings = $.extend({
			passwordInput: '#Password',
			userInputs: [],
			ratings: ["Very poor", "Poor", "Lukewarm", "Good", "Great"],
			//all progress bar classes removed before adding score specific css class
			allProgressBarClasses: "progress-bar-danger progress-bar-warning progress-bar-success progress-bar-striped active",
			//bootstrap css classes (0-4 corresponds with zxcvbn score)
			progressBarClass0: "progress-bar-danger progress-bar-striped active",
			progressBarClass1: "progress-bar-danger progress-bar-striped active",
			progressBarClass2: "progress-bar-warning progress-bar-striped active",
			progressBarClass3: "progress-bar-success",
			progressBarClass4: "progress-bar-success"
		}, options);

		return this.each(function () {
			settings.progressBar = this;
			//init progress bar display
			UpdateProgressBar();
			//Update progress bar on each keypress of password input
			$(settings.passwordInput).keyup(function (event) {
				UpdateProgressBar();
			});
		});

		function UpdateProgressBar() {
			var progressBar = settings.progressBar;
			var password = $(settings.passwordInput).val();
			if (password) {
				var result = zxcvbn(password, settings.userInputs);
				//result.score: 0, 1, 2, 3 or 4 - if crack time is less than 10**2, 10**4, 10**6, 10**8, Infinity.
				var scorePercentage = (result.score + 1) * 20;
				$(progressBar).css('width', scorePercentage + '%');
    $( "input#pass_score" ).val(result.score);
    
				if (result.score == 0) {
					//weak
					$(progressBar).removeClass(settings.allProgressBarClasses).addClass(settings.progressBarClass0);
					$(progressBar).html(settings.ratings[0]);
				}
				else if (result.score == 1) {
					//normal
					$(progressBar).removeClass(settings.allProgressBarClasses).addClass(settings.progressBarClass1);
					$(progressBar).html(settings.ratings[1]);
				}
				else if (result.score == 2) {
					//medium
					$(progressBar).removeClass(settings.allProgressBarClasses).addClass(settings.progressBarClass2);
					$(progressBar).html(settings.ratings[2]);
				}
				else if (result.score == 3) {
					//strong
					$(progressBar).removeClass(settings.allProgressBarClasses).addClass(settings.progressBarClass3);
					$(progressBar).html(settings.ratings[3]);
				}
				else if (result.score == 4) {
					//very strong
					$(progressBar).removeClass(settings.allProgressBarClasses).addClass(settings.progressBarClass4);
					$(progressBar).html(settings.ratings[4]);
				}
			}
			else {
				$(progressBar).css('width', '0%');
				$(progressBar).removeClass(settings.allProgressBarClasses).addClass(settings.progressBarClass0);
				$(progressBar).html('');
			}
		}
  
  update_password_bar = UpdateProgressBar;
	
 };
})(jQuery);

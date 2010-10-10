/**
*@author nicolaas[at]sunnysideup . co . nz
*
**/

(function($){

	$(document).ready(
		function() {
			PaymentsReport.init();
		}
	);


})(jQuery);


var PaymentsReport = {

	reportID: "",

	formID: "Form_EditForm",

	loadingClass: "loading",

	dropdownStatusArray: new Array(),

	init: function() {
		jQuery('.statusDropdownChange').livequery(
			'click',
			function(event) {
				PaymentsReport.initiateStatusUpdates();
        return false;
    	}
		);
		var options = {
			beforeSubmit:  PaymentsReport.showRequest,  // pre-submit callback
			success: PaymentsReport.showResponse,  // post-submit callback
			url: PaymentsReportURL + "processform/"
		};
		jQuery('#' + PaymentsReport.formID).ajaxForm(options);
	},

	addStatus:function(v) {
		this.dropdownStatusArray[this.dropdownStatusArray.length] = v;
	},
	// pre-submit callback
	showRequest: function (formData, jqForm, options) {
		for (var i=0; i < formData.length; i++) {
			if ("ID" == formData[i].name) {
				PaymentsReport.reportID = formData[i].value
			}
		}
		options.url = options.url + PaymentsReport.reportID;
		return true;
	},

	// post-submit callback
	showResponse: function (responseText, statusText)  {
		if("ok" == responseText) {
			jQuery("li#" +PaymentsReport.reportID+ " a").click();
			jQuery("a#tab-Root_Report").click();
		}
		else {
			alert("sorry could not apply filter");
		}
	},

	initiateStatusUpdates: function() {
		jQuery(".statusDropdownChange").each(
			function() {
				var defaultValue = jQuery(this).text();
				var id = jQuery(this).attr("rel");
				var html = '<select name="statusUpdate'+id+'" onchange="PaymentsReport.updateStatusDropdown('+id+')" >';
				for(i=0;i<PaymentsReport.dropdownStatusArray.length;i++){
					var selected = '';
					if(defaultValue == PaymentsReport.dropdownStatusArray[i]) {
						selected = ' selected="selected"';
					}
					html += '<option value="'+PaymentsReport.dropdownStatusArray[i]+'"'+selected+'>'+PaymentsReport.dropdownStatusArray[i]+'</option>';
				}
				html += '</select>';
				jQuery(this).replaceWith(html);
			}
		);
	},

	updateStatusDropdown: function(id) {
		var myControlSelector = "select[name='statusUpdate"+id+"']"
		var value = jQuery(myControlSelector).val();
		jQuery(myControlSelector).next("span.outcome").text("processing").fadeIn(500);
		jQuery.ajax({
			url: PaymentsReportURL+"setstatus/"+id+"/"+value,
			cache: false,
			success: function(html){
				jQuery(myControlSelector).next("span.outcome").text(html);
				jQuery(myControlSelector).next("span.outcome").fadeOut(3000);
			}
		});

	}
}



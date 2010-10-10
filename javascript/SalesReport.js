/**
*@author nicolaas[at]sunnysideup . co . nz
*
**/

(function($){

	$(document).ready(
		function() {
			SalesReport.init();
		}
	);


})(jQuery);


var SalesReport = {

	reportID: "",

	formID: "Form_EditForm",

	loadingClass: "loading",

	dropdownStatusArray: new Array(),

	init: function() {
		SalesReport.initiatePopUps();
		jQuery('.statusDropdownChange').livequery(
			'click',
			function(event) {
				SalesReport.initiateStatusUpdates();
        return false;
    	}
		);
		var options = {
			beforeSubmit:  SalesReport.showRequest,  // pre-submit callback
			success: SalesReport.showResponse,  // post-submit callback
			url: SalesReportURL + "processform/"
		};
		jQuery('#' + SalesReport.formID).ajaxForm(options);
	},

	addStatus:function(v) {
		this.dropdownStatusArray[this.dropdownStatusArray.length] = v;
	},
	// pre-submit callback
	showRequest: function (formData, jqForm, options) {
		for (var i=0; i < formData.length; i++) {
			if ("ID" == formData[i].name) {
				SalesReport.reportID = formData[i].value
			}
		}
		options.url = options.url + SalesReport.reportID;
		return true;
	},

	// post-submit callback
	showResponse: function (responseText, statusText)  {
		if("ok" == responseText) {
			jQuery("li#" +SalesReport.reportID+ " a").click();
			jQuery("a#tab-Root_Report").click();
		}
		else {
			alert("sorry could not apply filter");
		}
	},

	initiatePopUps: function() {
		jQuery(".makeIntoPopUp").livequery('click',
			function(event) {
				day = new Date();
				id = day.getTime();
				url = jQuery(this).attr("href");
				window.open(url, id, 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=1,resizable=1,width=770,height=800,left = 0,top = 0');
				return false;
			}
		);
	},

	initiateStatusUpdates: function() {
		jQuery(".statusDropdownChange").each(
			function() {
				var defaultValue = jQuery(this).text();
				var id = jQuery(this).attr("rel");
				var html = '<select name="statusUpdate'+id+'" onchange="SalesReport.updateStatusDropdown('+id+')" >';
				for(i=0;i<SalesReport.dropdownStatusArray.length;i++){
					var selected = '';
					if(defaultValue == SalesReport.dropdownStatusArray[i]) {
						selected = ' selected="selected"';
					}
					html += '<option value="'+SalesReport.dropdownStatusArray[i]+'"'+selected+'>'+SalesReport.dropdownStatusArray[i]+'</option>';
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
			url: SalesReportURL+"setstatus/"+id+"/"+value,
			cache: false,
			success: function(html){
				jQuery(myControlSelector).next("span.outcome").text(html);
				jQuery(myControlSelector).next("span.outcome").fadeOut(3000);
			}
		});
	}

}



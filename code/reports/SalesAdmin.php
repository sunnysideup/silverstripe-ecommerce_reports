<?php
/**
 * @author Nicolaas [at] sunnysideup.co.nz
 */
class SalesAdmin extends ReportAdmin {

	static $url_segment = 'sales';

	static $url_rule = '/$Action/$ID';

	static $menu_title = 'Sales';

	static $template_path = null; // defaults to (project)/templates/email

	public function init() {
		parent::init();
		//generic requirements
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-livequery/jquery.livequery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");


		//payment requirements
		Requirements::javascript("ecommercextras/javascript/PaymentsReport.js");
		Requirements::customScript('var PaymentsReportURL = "'.Director::baseURL()."PaymentsReport_Handler".'/";', 'PaymentsReport_Handler_Base_URL');
		$list = singleton('Payment')->dbObject('Status')->enumValues();
		$js = '';
		foreach($list as $key => $value) {
			if($key && $value) {
				$js .= 'PaymentsReport.addStatus("'.$value.'");';
			}
		}
		Requirements::customScript($js, "PaymentsReport_Handler_PaymentStatusList");

		//sales report
		Requirements::javascript("ecommercextras/javascript/SalesReport.js");
		Requirements::customScript('var SalesReportURL = "'.Director::baseURL()."SalesReport_Handler".'/";', 'SalesReport_Handler_Base_URL');
		$list = OrderDecorator::get_order_status_options();
		$js = '';
		foreach($list as $key => $value) {
			if($key && $value) {
				$js .= 'SalesReport.addStatus("'.$value.'");';
			}
		}
		Requirements::customScript($js, "SalesReport_Handler_OrderStatusList");

	}

	/**
	 * Does the parent permission checks, but also
	 * makes sure that instantiatable subclasses of
	 * {@link Report} exist. By default, the CMS doesn't
	 * include any Reports, so there's no point in showing
	 *
	 * @param Member $member
	 * @return boolean
	 */


	/**
	 * Return a DataObjectSet of SSReport subclasses
	 * that are available for use.
	 *
	 * @return DataObjectSet
	 */
	public function Reports() {
		$processedReports = array();
		$subClasses = ClassInfo::subclassesFor('SalesReport');

		if($subClasses) {
			foreach($subClasses as $subClass) {
				if($subClass != 'SalesReport') {
					$processedReports[] = new $subClass();
				}
			}
		}
		$subClasses = ClassInfo::subclassesFor('PaymentsReport');

		if($subClasses) {
			foreach($subClasses as $subClass) {
				if($subClass != "PaymentsReport") {
					$processedReports[] = new $subClass();
				}
			}
		}

		$reports = new DataObjectSet($processedReports);

		return $reports;
	}

}

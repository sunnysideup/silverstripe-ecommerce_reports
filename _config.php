<?php


// *** REPORTS
Director::addRules(50, array(
	'SalesReport_Handler//$Action/$ID' => 'SalesReport_Handler',
	'PaymentsReport_Handler//$Action/$ID' => 'PaymentsReport_Handler'
	//'silverstripe/HourlyTask/$Action/$ID' => 'HourlyTask' // optional..
	// setup cron job 0 * * * * wget http://www.mysite.com/silverstripe/HourlyTask/
	//if you add the OrderDecorator then this also runs this task at Order::onAfterWrite();
));
/*  ` will automatically be replaced by " if needed (i.e. SS 2.4+)
SalesReport::set_full_export_select_statement(
	array(
		"`Order`.`ID`" => "Order ID",
		"`Order`.`Created`" => "Created"
	)
);
*/
//SearchableOrderReport::set_default_from_time("10:00pm");
//SearchableOrderReport::set_default_until_time("10:00pm");

if(class_exists("SS_Report")) {
	SS_Report::register('ReportAdmin', 'SearchableOrderReport',10);
	SS_Report::register('ReportAdmin', 'SearchableProductSalesReport',20);
	SS_Report::register('ReportAdmin', 'SearchablePaymentReport',30);
	SS_Report::register('ReportAdmin', 'SalesReport',30);
	SS_Report::register('ReportAdmin', 'PaymentsReport',30);
}

<?php
/**
 *
 * @package ecommercextras
 * @author nicolaas[at]sunnysideup.co.nz
 */
class SearchablePaymentReport extends PaymentsReport {

	protected $title = 'Searchable Payments';

	protected $description = 'Search all payments';

	protected static $default_from_time = "11:00 am";
		static function set_default_from_time($v) { self::$default_from_time = $v;}
		static function get_default_from_time() { return self::$default_from_time;}
		static function get_default_from_time_as_full_date_time() {return date("Y-m-d",time()) . " " . date("H:i",strtotime(self::get_default_from_time()));}
	protected static $default_until_time = "10:00 pm";
		static function set_default_until_time($v) { self::$default_until_time = $v;}
		static function get_default_until_time() { return self::$default_until_time;}
		static function get_default_until_time_as_full_date_time() {return date("Y-m-d",time()) . " " . date("H:i",strtotime(self::get_default_until_time()));}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$stats[] = "Count: ".$this->statistic("count");
		$stats[] = "Sum: ".$this->currencyFormat($this->statistic("sum"));
		$stats[] = "Avg: ".$this->currencyFormat($this->statistic("avg"));
		$stats[] = "Min: ".$this->currencyFormat($this->statistic("min"));
		$stats[] = "Max: ".$this->currencyFormat($this->statistic("max"));
		if($this->statistic("count") > 3) {
			$fields->addFieldToTab("Root.Report", new LiteralField("stats", '<h2>Payment Statistics</h2><ul><li>'.implode('</li><li>', $stats).'</li></ul>'));
		}
		if($humanWhere = Session::get("SearchablePaymentReport.humanWhere")) {
			$fields->addFieldToTab("Root.Report", new LiteralField("humanWhere", "<p>Current Search: ".$humanWhere."</p>"), "ReportDescription");
			$fields->removeByName("ReportDescription");
			$fields->addFieldToTab("Root.Search", new FormAction('clearSearch', 'Clear Search'));
		}
		$paymentStatusList = singleton('Payment')->dbObject('Status')->enumValues();
		$dropDownValues = array();
		foreach($paymentStatusList as $paymentStatus) {
			$dropDownValues[$paymentStatus] = $paymentStatus;
		}
		$fields->addFieldToTab("Root.Search", new CheckboxSetField("Status", "Payment Status", $dropDownValues));
		$fields->addFieldToTab("Root.Search", new NumericField("OrderID", "Order ID"));
		$fields->addFieldToTab("Root.Search", new DateField("From", "From..."));
		$fields->addFieldToTab("Root.Search", new DropdownTimeField("FromTime", "Start time...", self::get_default_from_time_as_full_date_time(), "H:i a"));
		$fields->addFieldToTab("Root.Search", new DateField("Until", "Until..."));
		$fields->addFieldToTab("Root.Search", new DropdownTimeField("UntilTime", "End time...", self::get_default_until_time_as_full_date_time(), "H:i a"));
		$fields->addFieldToTab("Root.Search", new NumericField("HasMinimumPayment", "Amout at least..."));
		$fields->addFieldToTab("Root.Search", new NumericField("HasMaximumPayment", "Amount no more than ..."));
		$fields->addFieldToTab("Root.Search", new FormAction('doSearch', 'Apply Search'));

		return $fields;
	}

	function processform() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$where = array();
		$having = array();
		$humanWhere = array();
		foreach($_REQUEST as $key => $value) {
			$value = Convert::raw2sql($value);
			if($value) {
				switch($key) {
					case "OrderID":
						$where[] = ' {$bt}Order{$bt}.{$bt}ID{$bt} = '.intval($value);
						$humanWhere[] = ' OrderID equals '.intval($value);
						break;
					case "From":
						$d = new Date("date");
						$d->setValue($value);
						$t = new Time("time");
						$cleanTime = trim(preg_replace('/([ap]m)/', "", Convert::raw2sql($_REQUEST["FromTime"])));
						$t->setValue($cleanTime); //
						$exactTime = strtotime($d->format("Y-m-d")." ".$t->Nice24());
						$where[] = ' UNIX_TIMESTAMP({$bt}Payment{$bt}.{$bt}Created{$bt}) >= "'.$exactTime.'"';
						$humanWhere[] = ' Order on or after '.Date("r", $exactTime);//r = Example: Thu, 21 Dec 2000 16:01:07 +0200 // also consider: l jS \of F Y H:i Z(e)
						break;
					case "Until":
						$d = new Date("date");
						$d->setValue($value);
						$t = new Time("time");
						$cleanTime = trim(preg_replace('/([ap]m)/', "", Convert::raw2sql($_REQUEST["FromTime"])));
						$t->setValue($cleanTime); //
						$exactTime = strtotime($d->format("Y-m-d")." ".$t->Nice24());
						$where[] = ' UNIX_TIMESTAMP({$bt}Payment{$bt}.{$bt}Created{$bt}) <= "'.$exactTime.'"';
						$humanWhere[] = ' Order before or on '.Date("r", $exactTime);//r = Example: Thu, 21 Dec 2000 16:01:07 +0200 // also consider: l jS \of F Y H:i Z(e)

						break;
					case "Status":
						$subWhere = array();
						foreach($value as $item) {
							$subWhere[] = ' {$bt}Payment{$bt}.{$bt}Status{$bt} = "'.$item.'"';
							$humanWhere[] = ' Payment Status equals "'.$item.'"';
						}
						if(count($subWhere)) {
							$where[] = implode(" OR ", $subWhere);
						}
						break;
					case "HasMinimumPayment":
						$having[] = ' Amount > '.intval($value);
						$humanWhere[] = ' Payment of at least '.$this->currencyFormat($value);
						break;
					case "HasMaximumPayment":
						$having[] = ' Amount < '.intval($value);
						$humanWhere[] = ' Payment of no more than '.$this->currencyFormat($value);
						break;
					default:
					 break;
				}
			}
		}
		Session::set("SearchablePaymentReport.having", implode(" AND ", $having));
		Session::set("SearchablePaymentReport.where",implode(" AND", $where));
		Session::set("SearchablePaymentReport.humanWhere", implode(", ", $humanWhere));
		return "ok";
	}

	function getCustomQuery() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
		$where = Session::get("SearchablePaymentReport.where");
		if(trim($where)) {
		 $where = " ( $where ) AND ";
		}
		$where .= " 1 = 1";
		$query = singleton('Payment')->buildSQL(
			$where,
			$sort = "{$bt}Payment{$bt}.{$bt}Created{$bt} DESC",
			$limit = "",
			$join = " INNER JOIN {$bt}Order{$bt} on {$bt}Order{$bt}.{$bt}ID{$bt} = {$bt}Payment{$bt}.{$bt}OrderID{$bt}"
		);
		if($having = Session::get("SearchablePaymentReport.having")) {
			$query->having($having);
		}
		return $query;
	}


	function getReportField() {
		$report = parent::getReportField();
		$report->setCustomCsvQuery($this->getExportQuery());
		return $report;
	}


	function getExportFields() {
		return array(
			"OrderSummary" => "Order Details",
			'Amount' => 'Amount',
			'Currency' => 'Currency',
			'Message' => 'Message',
			'IP' => 'Varchar',
			'ProxyIP' => 'Varchar'
		);
	}

	function getExportQuery() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
		$where = Session::get("SearchablePaymentReport.where");
		if(trim($where)) {
		 $where = " ( $where ) AND ";
		}
		$query = singleton('Payment')->buildSQL(
			$where,
			$sort = "{$bt}Payment{$bt}.{$bt}Created{$bt} DESC",
			$limit = "",
			$join = " INNER JOIN {$bt}Order{$bt} on {$bt}Order{$bt}.{$bt}ID{$bt} = {$bt}Payment{$bt}.{$bt}OrderID{$bt}"
		);
		$fieldArray = $this->getExportFields();
		if(is_array($fieldArray)) {
			if(count($fieldArray)) {
				foreach($fieldArray as $key => $field) {
					$query->select[] = $key;
				}
			}
		}
		foreach($query->select as $key=>$value) {
			if($value == "OrderSummary") {
				$query->select[$key] = "CONCAT({$bt}Order{$bt}.{$bt}ID{$bt}, ' :: ', {$bt}Order{$bt}.{$bt}Created{$bt}, ' :: ', {$bt}Order{$bt}.{$bt}Status{$bt}) AS OrderSummary";
			}
		}
		if($having = Session::get("SearchableOrderReport.having")) {
			$query->having($having);
		}
		return $query;
	}




}



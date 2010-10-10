<?php
/**
 *
 * @package ecommercextras
 * @author nicolaas[at]sunnysideup.co.nz
 */
class SearchableOrderReport extends SalesReport {

	protected $title = 'Searchable Orders';

	protected $description = 'Search all orders';

	protected static $default_from_time = "11:00 am";
		static function set_default_from_time($v) { self::$default_from_time = $v;}
		static function get_default_from_time() { return self::$default_from_time;}
		static function get_default_from_time_as_full_date_time() {return date("Y-m-d",time()) . " " . date("H:i",strtotime(self::get_default_from_time()));}
	protected static $default_until_time = "10:00 pm";
		static function set_default_until_time($v) { self::$default_until_time = $v;}
		static function get_default_until_time() { return self::$default_until_time;}
		static function get_default_until_time_as_full_date_time() {return date("Y-m-d",time()) . " " . date("H:i",strtotime(self::get_default_until_time()));}

	function parameterFields() {
		//$fields = parent::getCMSFields();
		$fields =new FieldSet();
		$stats[] = "Count: ".$this->statistic("count");
		$stats[] = "Sum: ".$this->currencyFormat($this->statistic("sum"));
		$stats[] = "Avg: ".$this->currencyFormat($this->statistic("avg"));
		$stats[] = "Min: ".$this->currencyFormat($this->statistic("min"));
		$stats[] = "Max: ".$this->currencyFormat($this->statistic("max"));
		if($this->statistic("count") > 3) {
			$fields->push(new LiteralField("stats", '<h2>Payment Statistics</h2><ul><li>'.implode('</li><li>', $stats).'</li></ul>'));
		}
		if($humanWhere = Session::get("SearchableOrderReport.humanWhere")) {
			$fields->push(new LiteralField("humanWhere", "<p>Current Search: ".$humanWhere."</p>"), "ReportDescription");
			$fields->removeByName("ReportDescription");
			$fields->push( new FormAction('clearSearch', 'Clear Search'));
		}
		$fields->push(new CheckboxSetField("Status", "Order Status", OrderDecorator::get_order_status_options()));
		$fields->push(new NumericField("OrderID", "Order ID"));
		$fields->push(new DateField("From", "From..."));
		$fields->push(new TimeField("FromTime", "Start time...", self::get_default_from_time_as_full_date_time(), "H:i a"));
		$fields->push(new DateField("Until", "Until..."));
		$fields->push(new TimeField("UntilTime", "End time...", self::get_default_until_time_as_full_date_time(), "H:i a"));
		$fields->push(new TextField("Email", "Email"));
		$fields->push(new TextField("FirstName", "First Name"));
		$fields->push(new TextField("Surname", "Surname"));
		$fields->push(new NumericField("HasMinimumPayment", "Has Minimum Payment of ..."));
		$fields->push(new NumericField("HasMaximumPayment", "Has Maximum Payment of ..."));
		$fields->push(new FormAction('doSearch', 'Apply Search'));
		$fields->push(new LiteralField('doExport', '<a href="SalesReport_Handler/fullsalesexport/">export all details (do a search first to limit results)</a>'));
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
						$where[] = " {$bt}Order{$bt}.{$bt}ID{$bt} = ".intval($value);
						$humanWhere[] = ' OrderID equals '.intval($value);
						break;
					case "From":
						$d = new Date("date");
						$d->setValue($value);
						$t = new Time("time");
						$cleanTime = trim(preg_replace('/([ap]m)/', "", Convert::raw2sql($_REQUEST["FromTime"])));
						$t->setValue($cleanTime); //
						$exactTime = strtotime($d->format("Y-m-d")." ".$t->Nice24());
						$where[] = " UNIX_TIMESTAMP({$bt}Order{$bt}.{$bt}Created{$bt}) >= '".$exactTime."'";
						$humanWhere[] = ' Order on or after '.Date("r", $exactTime);//r = Example: Thu, 21 Dec 2000 16:01:07 +0200 // also consider: l jS \of F Y H:i Z(e)
						break;
					case "Until":
						$d = new Date("date");
						$d->setValue($value);
						$t = new Time("time");
						$cleanTime = trim(preg_replace('/([ap]m)/', "", Convert::raw2sql($_REQUEST["FromTime"])));
						$t->setValue($cleanTime); //
						$exactTime = strtotime($d->format("Y-m-d")." ".$t->Nice24());
						$where[] = " UNIX_TIMESTAMP({$bt}Order{$bt}.{$bt}Created{$bt}) <= '".$exactTime."'";
						$humanWhere[] = ' Order before or on '.Date("r", $exactTime);//r = Example: Thu, 21 Dec 2000 16:01:07 +0200 // also consider: l jS \of F Y H:i Z(e)
						break;
					case "Email":
						$where[] = " {$bt}Member{$bt}.{$bt}Email{$bt} = '".$value."'";
						$humanWhere[] = ' Customer Email equals "'.$value.'"';
						break;
					case "FirstName":
						$where[] = " {$bt}Member{$bt}.{$bt}FirstName{$bt} LIKE '%".$value."%'";
						$humanWhere[] = ' Customer First Name equals '.$value.'"';
						break;
					case "Surname":
						$where[] = " {$bt}Member{$bt}.{$bt}Surname{$bt} LIKE '%".$value."%'";
						$humanWhere[] = ' Customer Surname equals "'.$value.'"';
						break;
					case "Status":
						$subWhere = array();
						foreach($value as $item) {
							$subWhere[] = " {$bt}Order{$bt}.{$bt}Status{$bt} = '".$item."'";
							$humanWhere[] = ' Order Status equals "'.$item.'"';
						}
						if(count($subWhere)) {
							$where[] = implode(" OR ", $subWhere);
						}
						break;
					case "HasMinimumPayment":
						$having[] = ' RealPayments > '.intval($value);
						$humanWhere[] = ' Real Payment of at least '.$this->currencyFormat($value);
						break;
					case "HasMaximumPayment":
						$having[] = ' RealPayments < '.intval($value);
						$humanWhere[] = ' Real Payment of no more than '.$this->currencyFormat($value);
						break;
					//this has been included for SearchableProductSalesReport
					case "Product":
						$where[] = " IF(ProductVariationsForVariations.Title IS NOT NULL, CONCAT(ProductSiteTreeForVariations.Title,' : ', ProductVariationsForVariations.Title), IF(SiteTreeForProducts.Title IS NOT NULL, SiteTreeForProducts.Title, OrderAttribute.ClassName)) LIKE '%".$value."%'";
						$humanWhere[] = ' Product includes the phrase '.$value.'"';
						break;

					default:
					 break;
				}
			}
		}
		return $this->saveProcessedForm($having, $where, $humanWhere);
	}

	protected function saveProcessedForm($having, $where, $humanWhere) {
		Session::set("SearchableOrderReport.having", implode(" AND ", $having));
		Session::set("SearchableOrderReport.where",implode(" AND", $where));
		Session::set("SearchableOrderReport.humanWhere", implode(", ", $humanWhere));
		return "ok";
	}

	function getReportField() {
		$report = parent::getReportField();
		$report->setCustomCsvQuery($this->getExportQuery());
		return $report;
	}

	function getCustomQuery() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
		$where = Session::get("SearchableOrderReport.where");
		if(trim($where)) {
		 $where = " ( $where ) AND ";
		}
		$where .= "({$bt}Payment{$bt}.{$bt}Status{$bt} = 'Success' OR {$bt}Payment{$bt}.{$bt}Status{$bt} = 'Pending' OR  {$bt}Payment{$bt}.{$bt}Status{$bt} IS NULL)";
		$query = singleton('Order')->buildSQL(
			$where,
			$sort = "{$bt}Order{$bt}.{$bt}Created{$bt} DESC",
			$limit = "",
			$join = "
				INNER JOIN {$bt}Member{$bt} ON {$bt}Member{$bt}.{$bt}ID{$bt} = {$bt}Order{$bt}.{$bt}MemberID{$bt}
				LEFT JOIN Payment ON {$bt}Payment{$bt}.{$bt}OrderID{$bt} = {$bt}Order{$bt}.{$bt}ID{$bt}
			"
		);
		$query->select[] = "SUM({$bt}Payment{$bt}.{$bt}Amount{$bt}) RealPayments";
		if($having = Session::get("SearchableOrderReport.having")) {
			$query->having($having);
		}
		$query->groupby("{$bt}Order{$bt}.{$bt}ID{$bt}");
		return $query;
	}

	function getExportFields() {
		return array(
			"OrderSummary" => "Order Details",
			"RealPayments" => "Payments",
			"Payment.Message" => "Reference",
			"Member.FirstName" => "Customer first name",
			"Member.Surname" => "Customer last name",
			"Member.HomePhone" => "Customer home phone",
			"Member.MobilePhone" => "Customer mobile phone",
			"Member.Email" => "Customer phone",
			"Member.Address" => "Customer address 1",
			"Member.AddressLine2" => "Customer address 2",
			"Member.City" => "Customer City",
		);
	}

	function getExportQuery() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
		$where = Session::get("SearchableOrderReport.where");
		if(trim($where)) {
		 $where = " ( $where ) AND ";
		}
		$where .= "({$bt}Payment{$bt}.{$bt}Status{$bt} = 'Success' OR {$bt}Payment{$bt}.{$bt}Status{$bt} = 'Pending' OR  {$bt}Payment{$bt}.{$bt}Status{$bt} IS NULL)";
		$query = singleton('Order')->buildSQL(
			$where,
			$sort = "{$bt}Order{$bt}.{$bt}Created{$bt} DESC",
			$limit = "",
			$join = "
				INNER JOIN {$bt}Member{$bt} ON {$bt}Member{$bt}.{$bt}ID{$bt} = {$bt}Order{$bt}.{$bt}MemberID{$bt}
				LEFT JOIN Payment ON {$bt}Payment{$bt}.{$bt}OrderID{$bt} = {$bt}Order{$bt}.{$bt}ID{$bt}
			"//
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
			if($value == "RealPayments") {
				$query->select[$key] = "SUM(IF(Payment.Status = 'Success',{$bt}Payment{$bt}.{$bt}Amount{$bt}, 0)) RealPayments";
			}
		}
		foreach($query->select as $key=>$value) {
			if($value == "OrderSummary") {
				$query->select[$key] = "CONCAT({$bt}Order{$bt}.{$bt}ID{$bt}, ' :: ', {$bt}Order{$bt}.{$bt}Created{$bt}, ' :: ', {$bt}Order{$bt}.{$bt}Status{$bt}) AS OrderSummary";
			}
		}
		$query->groupby("{$bt}Order{$bt}.{$bt}ID{$bt}");
		if($having = Session::get("SearchableOrderReport.having")) {
			$query->having($having);
		}
		return $query;
	}

	protected function currencyFormat($v) {
		$c = new Currency("currency");
		$c->setValue($v);
		return $c->Nice();
	}

}



<?php
/**
 *
 * @package ecommercextras
 * @author nicolaas[at]sunnysideup.co.nz
 */
class SearchableProductSalesReport extends SearchableOrderReport {

	protected $title = 'Searchable Product Sales';

	protected $description = 'Search all products ordered';


	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Search", new TextField("Product", "Product name (or part of the name)"), "OrderID");
		return $fields;
	}

	function getCustomQuery() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
		$where = Session::get("SearchableProductSalesReport.where");
		if(trim($where)) {
		 $where = " ( $where ) AND";
		}
		$where .= ' (OrderModifier.Amount <> 0 OR OrderModifier.Amount IS NULL)';
		$query = singleton('OrderAttribute')->buildSQL(
			$where,
			$sort = "{$bt}Order{$bt}.{$bt}Created{$bt} DESC",
			$limit = "",
			$join = SalesReport::get_full_export_join_statement()
		);
		//make sure to do variations first
		$fieldArrayToAdd = SalesReport::get_full_export_select_statement();
		if(is_array($fieldArrayToAdd) && count($fieldArrayToAdd)) {
			foreach($fieldArrayToAdd as $sql => $name) {
				$query->select[] = $sql;
			}
		}
		$query->groupby("OrderAttribute.ID");
		if($having = Session::get("SearchableProductSalesReport.having")) {
			$query->having($having);
		}
		return $query;
	}

	function getReportField() {
		$fields = array(
			"OrderDetails" => "Order ID",
			"MemberDetails" => "Customer",
			"ProductOrModifierName" => "Name",
			"ProductOrModifierPrice" => "Price",
			"ProductOrModifierQuantity" => "Quantity",
			"RealPayments" => "Order Payment"
		);

		$table = new TableListField(
			'Orders',
			'Order',
			$fields
		);

		$table->setCustomQuery($this->getCustomQuery());

		$table->setFieldCasting(array(
			'Created' => 'Date',
			'Total' => 'Currency->Nice'
		));

		$table->setPermissions(array(
			'edit',
			'show',
			'export',
		));
		$table->setPageSize(250);


		$table->setFieldListCsv($this->getExportFields());

		$table->setCustomCsvQuery($this->getExportQuery());

		//$tableField->removeCsvHeader();

		return $table;
	}


	function getExportFields() {

		$fields = array(
			"Order.ID" => "Order ID",
			"Order.Created" => "Order date and time",
			"Payment.Message" => " Reference",
			//"Total" => "Total Order Amount",
			"Member.FirstName" => "Customer first name",
			"Member.Surname" => "Customer last name",
			"Member.HomePhone" => "Customer home phone",
			"Member.MobilePhone" => "Customer mobile phone",
			"Member.Email" => "Customer phone",
			"Member.Address" => "Customer address 1",
			"Member.AddressLine2" => "Customer address 2",
			"Member.City" => "Customer City",
			"Order.Status" => "Order Status"
			//"PlaintextProductSummary" => "Products"
			//"PlaintextModifierSummary" => "Additions",
			//"PlaintextLogDescription" => "Dispatch Notes"
		);
		return $fields;
	}

	function getExportQuery() {
		if("SalesReport" == $this->class) {
			user_error('Please implement getExportFields() on ' . $this->class, E_USER_ERROR);
		}
		else {
			return $this->getCustomQuery();
		}
	}




}



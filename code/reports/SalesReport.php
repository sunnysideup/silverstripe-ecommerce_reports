<?php
/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package ecommercextras
 */
class SalesReport extends SS_Report {


	protected static $full_export_select_statement =
		array(
			"`Order`.`ID`" => "Order number",
			"`Order`.`Created`" => "Order date and time",
			"GROUP_CONCAT(`Payment`.`Message` SEPARATOR ', ')" => "payment gateway reference",
			"GROUP_CONCAT(`Payment`.`ID` SEPARATOR ', ')" =>  "Transaction Id",
			"SUM(IF(Payment.Status = 'Success',`Payment`.`Amount`, 0)) RealPayments" => "Total Order Amount",
			"IF(ProductVariationsForVariations.Title IS NOT NULL, CONCAT(ProductSiteTreeForVariations.Title,' : ', ProductVariationsForVariations.Title), IF(SiteTreeForProducts.Title IS NOT NULL, SiteTreeForProducts.Title, OrderAttribute.ClassName)) ProductOrModifierName" => "Product Name",
			"IF(OrderItem.Quantity IS NOT NULL, OrderItem.Quantity, 1)  ProductOrModifierQuantity" => "Quantity",
			"IF(ProductSiteTreeForVariations.ID IS NOT NULL, ProductVariationsForVariations.Price, 0) +  IF(ProductForProducts.Price IS NOT NULL, ProductForProducts.Price, 0) + IF(OrderModifier.Amount IS NOT NULL, OrderModifier.Amount, 0) ProductOrModifierPrice" => "Amount",
			"IF(ProductForProducts.Price IS NOT NULL, ProductForProducts.Price, 0) ProductOrModifierPriceProduct" => "Product Amount",
			"IF(ProductSiteTreeForVariations.ID IS NOT NULL, ProductVariationsForVariations.Price, 0) ProductOrModifierPriceVariation" => "Variation Amount",
			"IF(OrderModifier.Amount IS NOT NULL, OrderModifier.Amount, 0) ProductOrModifierPriceModifier" => "Modifier Amount",
			"CONCAT(Member.Address, ' ', Member.AddressLine2,' ', Member.City, ' ', Member.Country,' ', Member.HomePhone,' ', Member.MobilePhone,' ', Member.Notes,' ', Member.Notes ) MemberContactDetails" => "Customer contact details",
			"IF(`Order`.`UseShippingAddress`, CONCAT(`Order`.`ShippingName`, ' ',`Order`.`ShippingAddress`, ' ',`Order`.`ShippingAddress2`, ' ',`Order`.`ShippingCity`, ' ',`Order`.`ShippingCountry`), 'no alternative delivery address') MemberShippingDetailsAddress" => "Costumer delivery",
			"`Order`.`Status`" => "Order status",
			"GROUP_CONCAT(`OrderStatusLogWithDetails`.`DispatchTicket` SEPARATOR ', ')" => "Dispatch ticket code",
			"GROUP_CONCAT(`OrderStatusLogWithDetails`.`DispatchedOn` SEPARATOR ', ')" => "Dispatch date",
			"GROUP_CONCAT(`OrderStatusLogWithDetails`.`DispatchedBy` SEPARATOR ', ')" => "Dispatched by",
			"GROUP_CONCAT(`OrderStatusLog`.`Note` SEPARATOR ', ')" => "Dispatch notes"
		);
		static function set_full_export_select_statement($v) {self::$full_export_select_statement = $v;}
		static function get_full_export_select_statement() {
			$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			if($bt == '"') {
				return str_replace('`', '"', self::$full_export_select_statement);
			}
			else {
				return self::$full_export_select_statement;
			}
		}

	protected static $full_export_join_statement = '
			INNER JOIN `Order` ON `Order`.`ID` = `OrderAttribute`.`OrderID`
			INNER JOIN `Member` On `Order`.`MemberID` = `Member`.`ID`
			LEFT JOIN `Payment` ON `Payment`.`OrderID` = `Order`.`ID`
			LEFT JOIN `Product_versions` ProductForProducts ON `Product_OrderItem`.`ProductID` = ProductForProducts.`RecordID` AND `Product_OrderItem`.`ProductVersion` = ProductForProducts.`Version`
			LEFT JOIN `SiteTree_versions` SiteTreeForProducts ON SiteTreeForProducts.`RecordID` = `Product_OrderItem`.`ProductID` AND `Product_OrderItem`.`ProductVersion` = SiteTreeForProducts.`Version`
			LEFT JOIN `ProductVariation_versions` ProductVariationsForVariations ON `ProductVariation_OrderItem`.`ProductVariationID` = ProductVariationsForVariations.`RecordID` AND `ProductVariation_OrderItem`.`ProductVariationVersion` = ProductVariationsForVariations.`Version`
			LEFT JOIN `SiteTree_versions` ProductSiteTreeForVariations ON `ProductSiteTreeForVariations`.`RecordID` = `Product_OrderItem`.`ProductID` AND `Product_OrderItem`.`ProductVersion` = `ProductSiteTreeForVariations`.`Version`
			LEFT JOIN `OrderStatusLog` ON `OrderStatusLog`.`OrderID` = `Order`.`ID`
			LEFT JOIN `OrderStatusLogWithDetails` ON `OrderStatusLogWithDetails`.`ID` = `OrderStatusLog`.`ID`';
		static function set_full_export_join_statement($v) {self::$full_export_join_statement = $v;}
		static function get_full_export_join_statement() {
			$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			if($bt == '"') {
				return str_replace('`', '"', self::$full_export_join_statement);
			}
			else {
				return self::$full_export_join_statement;
			}
		}

	protected static $full_export_file_name = "SalesExport";
		static function set_full_export_file_name($v) {self::$full_export_file_name = $v;}
		static function get_full_export_file_name() {
			$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			if($bt == '"') {
				return str_replace('`', '"', self::$full_export_file_name);
			}
			else {
				return self::$full_export_file_name;
			}
		}

	protected $title = 'All Orders';

	protected static $sales_array = array();

	protected $description = 'Search Orders';

	/**
	 * Return a {@link ComplexTableField} that shows
	 * all Order instances that are not printed. That is,
	 * Order instances with the property "Printed" value
	 * set to "0".
	 *
	 * @return ComplexTableField
	 */
	function getReportField() {
		// Get the fields used for the table columns
		Order::$table_overview_fields["Created"] = "Received";
		$fields = Order::$table_overview_fields;

		// Add some fields specific to this report
		$fields['Invoice'] = '';
		$fields['PackingSlip'] = '';
		$fields['ChangeStatus'] = '';

		$table = new TableListField(
			'Orders',
			'Order',
			$fields
		);

		// Customise the SQL query for Order, because we don't want it querying
		// all the fields. Invoice and Printed are dummy fields that just have some
		// text in them, which would be automatically queried if we didn't specify
		// a custom query.

		$table->setCustomQuery($this->getCustomQuery());

		// Set the links to the Invoice and Print fields allowing a user to view
		// another template for viewing an Order instance
		$table->setFieldFormatting(array(
			'Invoice' => '<a href=\"OrderReportWithLog_Popup/invoice/$ID\" class=\"makeIntoPopUp\">Invoice and Update</a>',
			'PackingSlip' => '<a href=\"OrderReport_Popup/packingslip/$ID\" class=\"makeIntoPopUp\">Packing Slip</a>',
			'ChangeStatus' => '<a href=\"#\" class=\"statusDropdownChange\" rel=\"$ID\">$Status</a><span class=\"outcome\"></span>'
		));

		$table->setFieldCasting(array(
			'Total' => 'Currency->Nice'
		));

		$table->setPermissions(array(
			'edit',
			'show',
			'export'
		));
		$table->setPageSize(250);


		$table->setFieldListCsv($this->getExportFields());

		$table->setCustomCsvQuery($this->getExportQuery());

		//$tableField->removeCsvHeader();

		return $table;
	}

	function getCustomQuery() {
		if("SalesReport" == $this->class) {
			user_error('Please implement getCustomQuery() on ' . $this->class, E_USER_ERROR);
		}
		else {
				//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
			$query = singleton('Order')->buildSQL('', 'Order.Created DESC');
			$query->groupby[] = 'Order.ID';
			return $query;
		}
	}

	function getExportFields() {
		if("SalesReport" == $this->class) {
			user_error('Please implement getExportFields() on ' . $this->class, E_USER_ERROR);
		}
		else {
		}
	}

	function getExportQuery() {
		if("SalesReport" == $this->class) {
			user_error('Please implement getExportFields() on ' . $this->class, E_USER_ERROR);
		}
		else {
			$query = singleton('Order')->buildSQL('', 'Order.Created DESC');
			$query->groupby[] = 'Order.Created';
			return $query;
		}
	}

	protected function statistic($type) {
		if(!count(self::$sales_array)) {
			$data = $this->getCustomQuery()->execute();
			if($data) {
				$array = array();
				foreach($data as $row) {
					if($row["RealPayments"]) {
						self::$sales_array[] = $row["RealPayments"];
					}
				}
			}
		}
		if(count(self::$sales_array)) {
			switch($type) {
				case "count":
					return count(self::$sales_array);
					break;
				case "sum":
					return array_sum(self::$sales_array);
					break;
				case "avg":
					return array_sum(self::$sales_array) / count(self::$sales_array);
					break;
				case "min":
					asort(self::$sales_array);
					foreach(self::$sales_array as $item) {return $item;}
					break;
				case "max":
					arsort(self::$sales_array);
					foreach(self::$sales_array as $item) {return $item;}
					break;
				default:
					user_error("Wrong statistic type speficied in SalesReport::statistic", E_USER_ERROR);
			}
		}
		return -1;
	}

	function processform() {
		if("SalesReport" == $this->class) {
			user_error('Please implement processform() on ' . $this->class, E_USER_ERROR);
		}
		else {
			die($_REQUEST);
		}
	}


}

class SalesReport_Handler extends Controller {

	function processform() {
		$ClassName = Director::URLParam("ID");
		$object = new $ClassName;
		return $object->processform();
	}


	function setstatus() {
		$id = $this->urlParams['ID'];
		if(!is_numeric($id)) {
			return "could not update order status";
		}
		$order = DataObject::get_by_id('Order', $id);
		if($order) {
			$oldStatus = $order->Status;
			$newStatus = $this->urlParams['OtherID'];
			if($oldStatus != $newStatus) {
				$order->Status = $newStatus;
				$order->write();
				$orderlog = new OrderStatusLog();
				$orderlog->OrderID = $order->ID;
				$orderlog->Status = "Status changed from ".$oldStatus." to ".$newStatus.".";
				$orderlog->Note = "Status changed from ".$oldStatus." to ".$newStatus.".";
				$orderlog->write();
			}
			else {
				return "no change";
			}
		}
		else {
			return "order not found";
		}
		return "updated to ".$newStatus;
	}

	function fullsalesexport() {
		$exportReport = new SearchableProductSalesReport();
		$query = $exportReport->getCustomQuery();
		$query->select = null;
		$array = SalesReport::get_full_export_select_statement();
		if(is_array($array) && count($array)) {
			$fileData = '"row number"';
			foreach($array as $sql => $name) {
				$query->select[] = $sql;
				$fileData .= ',"'.$name.'"';
			}
			$data = $query->execute();
			if($data) {
				$i = 0;
				foreach($data as $row) {
					$i++;
					$fileData .= "\r\n".'"'.$i.'"';
					foreach($row as $fieldName => $value) {
						$fileData .= ',"'.$value.'"';
					}
				}
			}
			else {
				$fileData = "no data available";
			}
		}
		else {
			$fileData = "please select fields first";
		}
		$fileName = SalesReport::get_full_export_file_name()."-".date("Y-m-d", strtotime("today")).".csv";
		header("Content-Type: text/csv; name=\"" . addslashes($fileName) . "\"");
		header("Content-Disposition: attachment; filename=\"" . addslashes($fileName) . "\"");
		header("Content-length: " . strlen($fileData));
		echo $fileData;
		exit();

	}

}

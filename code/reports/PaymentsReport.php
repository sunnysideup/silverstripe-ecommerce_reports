<?php
/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package ecommercextras
 */
class PaymentsReport extends SSReport {

	protected $title = 'All Payments';

	protected static $payment_array = array();

	protected $description = 'Show all payments';

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
		$fields = array(
			'Created' => 'Time',
			'Amount' => 'Amount',
			'Currency' => 'Currency',
			'Message' => 'Note',
			'IP' => 'Purchaser IP',
			'ProxyIP' => 'Purchaser Proxy'
		);
		$fields['ChangeStatus'] = '';

		$table = new TableListField(
			'Payments',
			'Payment',
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
			'ChangeStatus' => '<a href=\"#\" class=\"statusDropdownChange\" rel=\"$ID\">$Status</a><span class=\"outcome\"></span>'
		));

		$table->setFieldCasting(array(
			'Amount' => 'Currency->Nice'
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

	function getCustomQuery() {
		if("PaymentsReport" == $this->class) {
			//user_error('Please implement getCustomQuery() on ' . $this->class, E_USER_ERROR);
		}
		else {
				//buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "")
			$query = singleton('Payment')->buildSQL('', 'Payment.Created DESC');
			$query->groupby[] = 'Payment.ID';
			return $query;
		}
	}

	function getExportFields() {
		if("PaymentsReport" == $this->class) {
			//user_error('Please implement getExportFields() on ' . $this->class, E_USER_ERROR);
		}
		else {
			return array("Order.ID" => "Order ID", "Order.Total" => "Order Total");
		}
	}

	function getExportQuery() {
		if("PaymentsReport" == $this->class) {
			//user_error('Please implement getExportFields() on ' . $this->class, E_USER_ERROR);
		}
		else {
			$query = singleton('Payment')->buildSQL('', 'Payment.Created DESC');
			$query->groupby[] = 'Payment.ID';
			return $query;
		}
	}

	protected function statistic($type) {
		if(!count(self::$payment_array)) {
			$data = $this->getCustomQuery()->execute();
			if($data) {
				$array = array();
				foreach($data as $row) {
					if($row["Amount"] && $row["Status"] == "Success") {
						self::$payment_array[] = $row["Amount"];
					}
				}
			}
		}
		if(count(self::$payment_array)) {
			switch($type) {
				case "count":
					return count(self::$payment_array);
					break;
				case "sum":
					return array_sum(self::$payment_array);
					break;
				case "avg":
					return array_sum(self::$payment_array) / count(self::$payment_array);
					break;
				case "min":
					asort(self::$payment_array);
					foreach(self::$payment_array as $item) {return $item;}
					break;
				case "max":
					arsort(self::$payment_array);
					foreach(self::$payment_array as $item) {return $item;}
					break;
				default:
					user_error("Wrong statistic type speficied in PaymentsReport::statistic", E_USER_ERROR);
			}
		}
		return -1;
	}

	function processform() {
		if("PaymentsReport" == $this->class) {
			//user_error('Please implement processform() on ' . $this->class, E_USER_ERROR);
		}
		else {
			die($_REQUEST);
		}
	}


	protected function currencyFormat($v) {
		$c = new Currency("currency");
		$c->setValue($v);
		return $c->Nice();
	}


}

class PaymentsReport_Handler extends Controller {

	function processform() {
		$ClassName = Director::URLParam("ID");
		$object = new $ClassName;
		return $object->processform();
	}


	function setstatus() {
		$id = $this->urlParams['ID'];
		if(!is_numeric($id)) {
			return "could not update payment status";
		}
		$payment = DataObject::get_by_id('Payment', $id);
		if($payment) {
			$oldStatus = $payment->Status;
			$newStatus = $this->urlParams['OtherID'];
			if($oldStatus != $newStatus) {
				$payment->Status = $newStatus;
				$payment->write();
				$orderLog = new OrderStatusLog();
				$orderLog->OrderID = $orderLog->OrderID;
				$orderLog->Status = "Payment status changed from ".$oldStatus." to ".$newStatus.".";
				$orderLog->Note = "Payment changed from ".$oldStatus." to ".$newStatus.".";
				$orderLog->write();
			}
			else {
				return "no change";
			}
		}
		else {
			return "payment not found";
		}
		return "updated to ".$newStatus;
	}

}
<?php
/**
 * An extension to {@link SSReport} that allows a user
 * to view all Order instances in the system.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: reports
 *
 **/

class AllOrdersReport extends SS_Report
{
    protected $title = 'All orders';

    protected $description = 'Show all orders in the system.';

    public function sourceRecords($params, $sort = "", $limit = "")
    {
        $filters = array();
        if (isset($params['OrderID']) && $params['OrderID']) {
            $filters[] = "\"ID\" = ".$params['OrderID'];
        }
        if (isset($params['Status']) && $params['Status']) {
            $filters[] = "\"Status\" = '".$params['Status']."'";
        }
        $sort = "";
        $filter = implode(" AND ", $filters);
        return DataObject::get('Order', $filter, $sort, "", $limit);
    }

    public function columns()
    {
        $cols = Order::get_summary_fields();
        $cols['Invoice'] = 'Invoice';
        return $cols;
    }

    public function getReportField()
    {
        $tlf = parent::getReportField();
        $tlf->setFieldFormatting(array(
            'Invoice' => '<a target=\"_blank\" href=\"OrderReport_Popup/invoice/$ID\">'.i18n::_t('Report.VIEW', 'view').'</a> ' .
                    '<a target=\"_blank\" href=\"OrderReport_Popup/index/$ID?print=1\">'.i18n::_t('Report.PRINT', 'print').'</a>',
        ));
        $tlf->setFieldCasting(array(
            'Created' => 'Date->Long',
            'Total' => 'Currency->Nice'
        ));
        $tlf->setPermissions(array('edit', 'show', 'export', 'delete', 'print'));
        return $tlf;
    }

    public function parameterFields()
    {
        $fields = new FieldSet(
            new TextField('OrderID', 'Order No'),
            new DateField('Created', 'Created'),
            //new TextField('FirstName','First Name'),
            //new TextField('Surname','Surname'),
            //new NumericField('Total','Total'),
            $ddf = new DropdownField('StatusID', 'Status', DataObject::get("OrderStep")->toDropdownMap())
        );
        $ddf->setHasEmptyDefault(true);
        return $fields;
    }
}

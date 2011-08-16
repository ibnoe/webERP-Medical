<?php

/* $Id$ */

// POReport.php

// Inquiry on Purchase Orders


// If Date Type is Order, the main file is purchorderdetails


// If Date Type is Delivery, the main file is grns

include ('includes/session.inc');
$title = _('Purchase Order Report');
include ('includes/header.inc');

# Sets default date range for current month
if (!isset($_POST['FromDate'])) {
	$_POST['FromDate'] = Date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, Date('m') , 1, Date('Y')));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = Date($_SESSION['DefaultDateFormat']);
}
if (isset($_POST['submit']) or isset($_POST['submitcsv'])) {
	if (isset($_POST['PartNumber'])) {
		$PartNumber = trim(mb_strtoupper($_POST['PartNumber']));
	}
	elseif (isset($_GET['PartNumber'])) {
		$PartNumber = trim(mb_strtoupper($_GET['PartNumber']));
	}

	# Part Number operator - either LIKE or =
	$PartNumberOp = $_POST['PartNumberOp'];
	if (isset($_POST['SupplierId'])) {
		$SupplierId = trim(mb_strtoupper($_POST['SupplierId']));
	}
	elseif (isset($_GET['SupplierId'])) {
		$SupplierId = trim(mb_strtoupper($_GET['SupplierId']));
	}
	$SupplierIdOp = $_POST['SupplierIdOp'];
	$SupplierNameOp = $_POST['SupplierNameOp'];

	// Save $_POST['SummaryType'] in $SaveSummaryType because change $_POST['SummaryType'] when

	// create $sql

	$SaveSummaryType = $_POST['SummaryType'];
}
if (isset($_POST['SupplierName'])) {
	$SupplierName = trim(mb_strtoupper($_POST['SupplierName']));
}
elseif (isset($_GET['SupplierName'])) {
	$SupplierName = trim(mb_strtoupper($_GET['SupplierName']));
}

// Had to add supplierid to SummaryType when do summary by name because there could be several accounts

// with the same name. Tried passing 'suppname,supplierid' in form, but it only read 'suppname'

if (isset($_POST['SummaryType']) and $_POST['SummaryType'] == 'suppname') {
	$_POST['SummaryType'] = 'suppname,suppliers.supplierid';
}
if (isset($_POST['submit'])) {
	echo '<p class="page_title_text"><img src="' . $rootpath . '/css/' . $theme . '/images/maintenance.png" title="' . _('Search') . '" alt="" />' . $title . '</p>';
	submit($db, $PartNumber, $PartNumberOp, $SupplierId, $SupplierIdOp, $SupplierName, $SupplierNameOp, $SaveSummaryType, $rootpath);
}
else if (isset($_POST['submitcsv'])) {
	echo '<p class="page_title_text"><img src="' . $rootpath . '/css/' . $theme . '/images/maintenance.png" title="' . _('Search') . '" alt="" />' . $title . '</p>';
	submitcsv($db, $PartNumber, $PartNumberOp, $SupplierId, $SupplierIdOp, $SupplierName, $SupplierNameOp, $SaveSummaryType);
}
else {
	echo '<p class="page_title_text"><img src="' . $rootpath . '/css/' . $theme . '/images/maintenance.png" title="' . _('Search') . '" alt="" />' . $title . '</p>';
	display($db);
}

//####_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT####

function submit(&$db, $PartNumber, $PartNumberOp, $SupplierId, $SupplierIdOp, $SupplierName, $SupplierNameOp, $SaveSummaryType, $rootpath) {

	//initialize no input errors
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (!Is_Date($_POST['FromDate'])) {
		$InputError = 1;
		prnMsg(_('Invalid From Date') , 'error');
	}
	if (!Is_Date($_POST['ToDate'])) {
		$InputError = 1;
		prnMsg(_('Invalid To Date') , 'error');
	}

	// Add more to WHERE statement, if user entered something for the part number,supplierid, name
	$WherePart = ' ';
	if (mb_strlen($PartNumber) > 0 && $PartNumberOp == 'LIKE') {
		$PartNumber = $PartNumber . '%';
	}
	else {
		$PartNumberOp = '=';
	}
	if (mb_strlen($PartNumber) > 0) {
		$WherePart = " AND purchorderdetails.itemcode " . $PartNumberOp . " '" . $PartNumber . "'  ";
	}
	else {
		$WherePart = ' ';
	}
	$WhereSupplierID = ' ';
	if ($SupplierIdOp == 'LIKE') {
		$SupplierId = $SupplierId . '%';
	}
	else {
		$SupplierIdOp = '=';
	}
	if (mb_strlen($SupplierId) > 0) {
		$WhereSupplierID = " AND purchorders.supplierno " . $SupplierIdOp . " '" . $SupplierId . "'  ";
	}
	else {
		$WhereSupplierID = ' ';
	}
	$WhereSupplierName = ' ';
	if (mb_strlen($SupplierName) > 0 && $SupplierNameOp == 'LIKE') {
		$SupplierName = $SupplierName . '%';
	}
	else {
		$SupplierNameOp = '=';
	}
	if (mb_strlen($SupplierName) > 0) {
		$WhereSupplierName = " AND suppliers.suppname " . $SupplierNameOp . " '" . $SupplierName . "'  ";
	}
	else {
		$WhereSupplierName = ' ';
	}
	if (mb_strlen($_POST['OrderNo']) > 0) {
		$WhereOrderNo = " AND purchorderdetails.orderno = " . " '" . $_POST['OrderNo'] . "'  ";
	}
	else {
		$WhereOrderNo = ' ';
	}
	$WhereLineStatus = ' ';

	// Had to use IF statement instead of comparing 'linestatus' to $_POST['LineStatus']

	//in WHERE clause because the WHERE clause didn't recognize


	// that had used the IF statement to create a field called linestatus

	if ($_POST['LineStatus'] != 'All') {
		if ($_POST['DateType'] == 'Order') {
			$WhereLineStatus = " AND IF(purchorderdetails.quantityord = purchorderdetails.qtyinvoiced ||
			  purchorderdetails.completed = 1,'Completed','Open') = '" . $_POST['LineStatus'] . "'";
		}
		else {
			$WhereLineStatus = " AND IF(grns.qtyrecd - grns.quantityinv <> 0,'Open','Completed') = '" . $_POST['LineStatus'] . "'";
		}
	}
	$WhereCategory = ' ';
	if ($_POST['Category'] != 'All') {
		$WhereCategory = " AND stockmaster.categoryid = '" . $_POST['Category'] . "'";
	}
	if ($InputError != 1) {
		$FromDate = FormatDateForSQL($_POST['FromDate']);
		$ToDate = FormatDateForSQL($_POST['ToDate']);
		if ($_POST['ReportType'] == 'Detail') {
			if ($_POST['DateType'] == 'Order') {
				$sql = "SELECT purchorderdetails.orderno,
								purchorderdetails.itemcode,
								purchorderdetails.deliverydate,
								purchorders.supplierno,
								purchorders.orddate,
								purchorders.status,
								purchorders.initiator,
								purchorderdetails.quantityord,
								purchorderdetails.qtyinvoiced,
								(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
								(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
								IF (purchorderdetails.quantityord = purchorderdetails.qtyinvoiced ||
								  purchorderdetails.completed = 1,'Completed','Open') AS linestatus,
								suppliers.suppname,
								suppliers.currcode,
								stockmaster.decimalplaces,
								stockmaster.description
							FROM purchorderdetails
							LEFT JOIN purchorders
								ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers
								ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster
								ON purchorderdetails.itemcode = stockmaster.stockid
							WHERE purchorders.orddate >='" . $FromDate . "'
								AND purchorders.orddate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							ORDER BY " . $_POST['SortBy'];
			}
			else {

				// Selects by delivery date from grns
				$sql = "SELECT purchorderdetails.orderno,
								purchorderdetails.itemcode,
								grns.deliverydate,
								purchorders.supplierno,
								purchorders.orddate,
								purchorders.status,
								purchorders.initiator,
								grns.qtyrecd as quantityord,
								grns.quantityinv as qtyinvoiced,
								(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
								(grns.qtyrecd * grns.stdcostunit) as extcost,
								IF (grns.qtyrecd - grns.quantityinv <> 0,'Open','Completed') as linestatus,
								suppliers.suppname,
								suppliers.currcode,
								stockmaster.decimalplaces,
								stockmaster.description
							FROM grns
							LEFT JOIN purchorderdetails
								ON grns.podetailitem = purchorderdetails.podetailitem
							LEFT JOIN purchorders
								ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers
								ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster
								ON purchorderdetails.itemcode = stockmaster.stockid
							WHERE grns.deliverydate >='$FromDate'
								AND grns.deliverydate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							ORDER BY " . $_POST['SortBy'];
			}
		}
		else {

			// sql for Summary report
			$orderby = $_POST['SummaryType'];

			// The following is because the 'extprice' summary is a special case - with the other

			// summaries, you group and order on the same field; with 'extprice', you are actually


			// grouping on the stkcode and ordering by extprice descending

			if ($_POST['SummaryType'] == 'extprice') {
				$_POST['SummaryType'] = 'itemcode';
				$orderby = 'extprice DESC';
			}
			if ($_POST['DateType'] == 'Order') {
				if ($_POST['SummaryType'] == 'extprice' || $_POST['SummaryType'] == 'itemcode') {
					$sql = "SELECT purchorderdetails.itemcode,
									SUM(purchorderdetails.quantityord) as quantityord,
									SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
									SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
									SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
									stockmaster.decimalplaces,
									purchorders.status,
									purchorders.initiator,
									suppliers.currcode,
									stockmaster.description
								FROM purchorderdetails
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE purchorders.orddate >='$FromDate'
									AND purchorders.orddate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									stockmaster.decimalplaces,
								stockmaster.description
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'orderno') {
					$sql = "SELECT purchorderdetails.orderno,
									purchorders.supplierno,
									SUM(purchorderdetails.quantityord) as quantityord,
									SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
									SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
									SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
									purchorders.status,
									purchorders.initiator,
									suppliers.currcode,
									suppliers.suppname
								FROM purchorderdetails
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE purchorders.orddate >='$FromDate'
									AND purchorders.orddate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									purchorders.supplierno,
									suppliers.suppname
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'supplierno' || $_POST['SummaryType'] == 'suppname,suppliers.supplierid') {
					$sql = "SELECT purchorders.supplierno,
									SUM(purchorderdetails.quantityord) as quantityord,
									SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
									SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
									SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
									purchorders.status,
									purchorders.initiator,
									suppliers.currcode,
									suppliers.suppname
								FROM purchorderdetails
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE purchorders.orddate >='$FromDate'
									AND purchorders.orddate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									purchorders.supplierno,
									suppliers.suppname
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'month') {
					$sql = "SELECT EXTRACT(YEAR_MONTH from purchorders.orddate) as month,
									CONCAT(MONTHNAME(purchorders.orddate),' ',
									YEAR(purchorders.orddate)) as monthname,
									purchorders.status,
									purchorders.initiator,
									suppliers.currcode,
									SUM(purchorderdetails.quantityord) as quantityord,
									SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
									SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
									SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost
									FROM purchorderdetails
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE purchorders.orddate >='$FromDate'
									AND purchorders.orddate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									monthname
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'categoryid') {
					$sql = "SELECT SUM(purchorderdetails.quantityord) as quantityord,
									SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
									SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
									SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
									stockmaster.categoryid,
									purchorders.status,
									suppliers.currcode,
									purchorders.initiator,
									stockcategory.categorydescription
								FROM purchorderdetails
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE purchorders.orddate >='$FromDate'
									AND purchorders.orddate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									categorydescription
								ORDER BY " . $orderby;
				}
			}
			else {

				// Selects by delivery date from grns
				if ($_POST['SummaryType'] == 'extprice' || $_POST['SummaryType'] == 'itemcode') {
					$sql = "SELECT purchorderdetails.itemcode,
									SUM(grns.qtyrecd) as quantityord,
									SUM(grns.quantityinv) as qtyinvoiced,
									SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
									SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
									purchorders.status,
									suppliers.currcode,
									purchorders.initiator,
									stockmaster.description
								FROM grns
								LEFT JOIN purchorderdetails
									ON grns.podetailitem = purchorderdetails.podetailitem
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE grns.deliverydate >='$FromDate'
									AND grns.deliverydate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									stockmaster.description
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'orderno') {
					$sql = "SELECT purchorderdetails.orderno,
									purchorders.supplierno,
									SUM(grns.qtyrecd) as quantityord,
									SUM(grns.quantityinv) as qtyinvoiced,
									SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
									SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
									purchorders.initiator,
									purchorders.status,
									suppliers.currcode,
									suppliers.suppname
								FROM grns
								LEFT JOIN purchorderdetails
									ON grns.podetailitem = purchorderdetails.podetailitem
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE grns.deliverydate >='$FromDate'
									AND grns.deliverydate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									purchorders.supplierno,
									suppliers.suppname
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'supplierno' || $_POST['SummaryType'] == 'suppname,suppliers.supplierid') {
					$sql = "SELECT purchorders.supplierno,
									SUM(grns.qtyrecd) as quantityord,
									SUM(grns.quantityinv) as qtyinvoiced,
									SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
									SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
									purchorders.initiator,
									purchorders.status,
									suppliers.currcode,
									suppliers.suppname
								FROM grns
								LEFT JOIN purchorderdetails
									ON grns.podetailitem = purchorderdetails.podetailitem
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE grns.deliverydate >='$FromDate'
									AND grns.deliverydate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									purchorders.supplierno,
									suppliers.suppname
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'month') {
					$sql = "SELECT EXTRACT(YEAR_MONTH from purchorders.orddate) as month,
									CONCAT(MONTHNAME(purchorders.orddate),
									' ',
									YEAR(purchorders.orddate)) as monthname,
									SUM(grns.qtyrecd) as quantityord,
									SUM(grns.quantityinv) as qtyinvoiced,
									SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
									suppliers.currcode,
									purchorders.initiator,
									purchorders.status,
									SUM(grns.qtyrecd * grns.stdcostunit) as extcost
								FROM grns
								LEFT JOIN purchorderdetails
									ON grns.podetailitem = purchorderdetails.podetailitem
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE grns.deliverydate >='$FromDate'
									AND grns.deliverydate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									monthname
								ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'categoryid') {
					$sql = "SELECT stockmaster.categoryid,
									stockcategory.categorydescription,
									SUM(grns.qtyrecd) as quantityord,
									SUM(grns.quantityinv) as qtyinvoiced,
									SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
									purchorders.initiator,
									purchorders.status,
									suppliers.currcode,
									SUM(grns.qtyrecd * grns.stdcostunit) as extcost
								FROM grns
								LEFT JOIN purchorderdetails
									ON grns.podetailitem = purchorderdetails.podetailitem
								LEFT JOIN purchorders
									ON purchorders.orderno=purchorderdetails.orderno
								LEFT JOIN suppliers
									ON purchorders.supplierno = suppliers.supplierid
								LEFT JOIN stockmaster
									ON purchorderdetails.itemcode = stockmaster.stockid
								LEFT JOIN stockcategory
									ON stockcategory.categoryid = stockmaster.categoryid
								WHERE grns.deliverydate >='$FromDate'
									AND grns.deliverydate <='$ToDate'
								$WherePart
								$WhereSupplierID
								$WhereSupplierName
								$WhereOrderNo
								$WhereLineStatus
								$WhereCategory
								GROUP BY " . $_POST['SummaryType'] . ",
									categorydescription
								ORDER BY " . $orderby;
				}
			}
		} // End of if ($_POST['ReportType']


		//echo "<br/>$sql<br/>";

		$ErrMsg = _('The SQL to find the parts selected failed with the message');
		$result = DB_query($sql, $db, $ErrMsg);
		$ctr = 0;
		$TotalQty = 0;
		$TotalExtCost = 0;
		$TotalExtPrice = 0;
		$TotalInvQty = 0;

		// Create array for summary type to display in header. Access it with $SaveSummaryType
		$Summary_Array['orderno'] = _('Order Number');
		$Summary_Array['itemcode'] = _('Part Number');
		$Summary_Array['extprice'] = _('Extended Price');
		$Summary_Array['supplierno'] = _('Customer Number');
		$Summary_Array['suppname'] = _('Customer Name');
		$Summary_Array['month'] = _('Month');
		$Summary_Array['categoryid'] = _('Stock Category');

		// Create array for sort for detail report to display in header
		$Detail_Array['purchorderdetails.orderno'] = _('Order Number');
		$Detail_Array['purchorderdetails.itemcode'] = _('Part Number');
		$Detail_Array['suppliers.supplierid,purchorderdetails.orderno'] = _('Supplier Number');
		$Detail_Array['suppliers.suppname,suppliers.supplierid,purchorderdetails.orderno'] = _('Supplier Name');

		// Display Header info
		echo '<table class="selection">';
		if ($_POST['ReportType'] == 'Summary') {
			$SortBy_Display = $Summary_Array[$SaveSummaryType];
		}
		else {
			$SortBy_Display = $Detail_Array[$_POST['SortBy']];
		}
		echo '<tr><th colspan="2"><font size="3" color="navy">' . _('Header Details') . '</font></th></tr>';
		echo '<tr><td>' . _('Purchase Order Report') . '</td><td>' . $_POST['ReportType'] . ' By ' . $SortBy_Display . '</td></tr>';
		echo '<tr><td>' . _('Date Type') . '</td><td>' . $_POST['DateType'] . '</tr>';
		echo '<tr><td>' . _('Date Range') . '</td><td>' . $_POST['FromDate'] . _(' To ') . $_POST['ToDate'] . '</td></tr>';
		if (mb_strlen(trim($PartNumber)) > 0) {
			echo '<tr><td>' . _('Part Number') . '</td><td>' . $_POST['PartNumberOp'] . ' ' . $_POST['PartNumber'] . '</td></tr>';
		}
		if (mb_strlen(trim($_POST['SupplierId'])) > 0) {
			echo '<tr><td>' . _('Supplier Number') . '</td><td>' . $_POST['SupplierIdOp'] . ' ' . $_POST['SupplierId'] . '</td></tr>';
		}
		if (mb_strlen(trim($_POST['SupplierName'])) > 0) {
			echo '<tr><td>' . _('Supplier Name') . '</td><td>' . $_POST['SupplierNameOp'] . ' ' . $_POST['SupplierName'] . '</td></tr>';
		}
		echo '<tr><td>' . _('Line Item Status') . '</td><td>' . $_POST['LineStatus'] . '</tr>';
		echo '<tr><td>' . _('Stock Category') . '</td><td>' . $_POST['Category'] . '</tr></table>';
		if ($_POST['ReportType'] == 'Detail') {
			echo '<br /><table class="selection" width="98%">';
			if ($_POST['DateType'] == 'Order') {
				printf('<tr>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%s</th>
							<th>%-s</th>
							<th>%s</th>',
							_('Order No') ,
							_('Order Date') ,
							_('Initiator') ,
							_('Part Description') ,
							_('Supplier Name') ,
							_('Order Qty') ,
							_('Extended Cost') ,
							_('Extended Price') ,
							_('Invoiced Qty') ,
							_('Line Status') ,
							_('Item Due')
						);
				$linectr = 0;
				$k = 0;
				while ($myrow = DB_fetch_array($result)) {
					if ($k == 1) {
						echo '<tr class="EvenTableRows">';
						$k = 0;
					}
					else {
						echo '<tr class="OddTableRows">';
						$k++;
					}
					$linectr++;

					// Detail for both DateType of Order
					if ($myrow['status'] == 'Completed' or $myrow['status'] == 'Cancelled' or $myrow['status'] == 'Rejected') {
						$ViewPurchOrder = $rootpath . '/PO_OrderDetails.php?OrderNo=' . $myrow['orderno'];
					}
					else {
						$ViewPurchOrder = $rootpath . '/PO_Header.php?ModifyOrderNumber=' . $myrow['orderno'];
					}
					printf('<td><a href="' . $ViewPurchOrder . '">%s</td>
							<td>%s</td>
							<td>%s</td>
							<td><a href="'.$rootpath . '/SelectProduct.php?StockID=%s">%s</a></td>
							<td><a href="'.$rootpath . '/SelectSupplier.php?SupplierID=%s">%s</a></td>
							<td class="number">%s</td>
							<td class="number">%s&nbsp;%s</td>
							<td class="number">%s&nbsp;%s</td>
							<td class="number">%s</td>
							<td>%s</td><td>%s</td></tr>',
							$myrow['orderno'],
							ConvertSQLDate($myrow['orddate']),
							$myrow['initiator'],
							$myrow['itemcode'] ,
							$myrow['description'] ,
							$myrow['supplierno'],
							$myrow['suppname'],
							number_format($myrow['quantityord'], $myrow['decimalplaces']) ,
							$myrow['currcode'],
							number_format($myrow['extcost'], $_SESSION['Currencies'][$myrow['currcode']]['DecimalPlaces']) ,
							$myrow['currcode'],
							number_format($myrow['extprice'], $_SESSION['Currencies'][$myrow['currcode']]['DecimalPlaces']) ,
							number_format($myrow['qtyinvoiced'], $myrow['decimalplaces']) ,
							$myrow['linestatus'],
							ConvertSQLDate($myrow['deliverydate'])
						);

					$LastDecimalPlaces = $myrow['decimalplaces'];
					$TotalQty+= $myrow['quantityord'];
					$TotalExtCost+= $myrow['extcost'];
					$TotalExtPrice+= $myrow['extprice'];
					$TotalInvQty+= $myrow['qtyinvoiced'];
				} //END WHILE LIST LOOP


			}
			else {

				// Header for Date Type of Delivery Date
				printf('<tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>', _('Order No') , _('Order Date') , _('Initiator') , _('Part Description') , _('Supplier Name') , _('Received') , _('Extended Cost') , _('Extended Price') , _('Invoiced Qty') , _('Line Status') , _('Delivered'));
				$linectr = 0;
				$k = 0;
				while ($myrow = DB_fetch_array($result)) {
					if ($k == 1) {
						echo '<tr class="EvenTableRows">';
						$k = 0;
					}
					else {
						echo '<tr class="OddTableRows">';
						$k++;
					}
					$linectr++;

					// Detail for both DateType of Ship

					// In sql, had to alias grns.qtyrecd as quantityord so could use same name here

					if ($myrow['status'] == 'Completed' or $myrow['status'] == 'Cancelled' or $myrow['status'] == 'Rejected') {
						$ViewPurchOrder = $rootpath . '/PO_OrderDetails.php?OrderNo=' . $myrow['orderno'];
					}
					else {
						$ViewPurchOrder = $rootpath . '/PO_Header.php?ModifyOrderNumber=' . $myrow['orderno'];
					}
					printf('<td><a href="' . $ViewPurchOrder . '">%s</td>
							<td>%s</td>
							<td>%s</td>
							<td><a href="'.$rootpath . '/SelectProduct.php?StockID=%s">%s</a></td>
							<td><a href="'.$rootpath . '/SelectSupplier.php?SupplierID=%s">%s</a></td>
							<td class="number">%s</td>
							<td class="number">%s</td>
							<td class="number">%s</td>
							<td class="number">%s</td>
							<td>%s</td>
							<td>%s</td>
							</tr>',
							$myrow['orderno'],
							ConvertSQLDate($myrow['orddate']) ,
							$myrow['initiator'],
							$myrow['itemcode'],
							$myrow['description'],
							$myrow['supplierno'],
							$myrow['suppname'],
							number_format($myrow['quantityord'], $myrow['decimalplaces']) ,
							number_format($myrow['extcost'], $_SESSION['Currencies'][$myrow['currcode']]['DecimalPlaces']) ,
							number_format($myrow['extprice'], $_SESSION['Currencies'][$myrow['currcode']]['DecimalPlaces']) ,
							number_format($myrow['qtyinvoiced'], $myrow['decimalplaces']) ,
							$myrow['linestatus'],
							ConvertSQLDate($myrow['deliverydate'])
						);

					$LastDecimalPlaces = $myrow['decimalplaces'];
					$TotalQty+= $myrow['quantityord'];
					$TotalExtCost+= $myrow['extcost'];
					$TotalExtPrice+= $myrow['extprice'];
					$TotalInvQty+= $myrow['qtyinvoiced'];
				} //END WHILE LIST LOOP


			}
			echo '</table>';
		}
		else {

			// Print summary stuff
			echo '<br /><table class="selection" width=98%>';
			$summarytype = $_POST['SummaryType'];

			// For SummaryType 'suppname' had to add supplierid to it for the GROUP BY in the sql,

			// but have to take it away for $myrow[$summarytype] to be valid


			// Set up description based on the Summary Type

			if ($summarytype == 'suppname,suppliers.supplierid') {
				$summarytype = 'suppname';
				$description = 'supplierno';
				$summaryheader = _('Supplier Name');
				$descriptionheader = _('Supplier Number');
			}
			if ($summarytype == 'itemcode' || $summarytype == 'extprice') {
				$description = 'description';
				$summaryheader = _('Part Number');
				$descriptionheader = _('Part Description');
			}
			if ($summarytype == 'supplierno') {
				$description = 'suppname';
				$summaryheader = _('Supplier Number');
				$descriptionheader = _('Supplier Name');
			}
			if ($summarytype == 'orderno') {
				$description = 'supplierno';
				$summaryheader = _('Order Number');
				$descriptionheader = _('Supplier Number');
			}
			if ($summarytype == 'categoryid') {
				$description = 'categorydescription';
				$summaryheader = _('Stock Category');
				$descriptionheader = _('Category Description');
			}
			$summarydesc = $summaryheader;
			if ($orderby == 'extprice DESC') {
				$summarydesc = _('Extended Price');
			}
			if ($summarytype == 'month') {
				$description = 'monthname';
				$summaryheader = _('Month');
				$descriptionheader = _('Month');
			}
			printf('<tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>', _($summaryheader) , _($descriptionheader) , _('Quantity') , _('Extended Cost') , _('Extended Price') , _('Invoiced Qty'));
			$suppname = ' ';
			$linectr = 0;
			$k = 0;
			while ($myrow = DB_fetch_array($result)) {
				$linectr++;
				if ($summarytype == 'orderno') {
					$suppname = $myrow['suppname'];
				}
				if ($k == 1) {
					echo '<tr class="EvenTableRows">';
					$k = 0;
				}
				else {
					echo '<tr class="OddTableRows">';
					$k++;
				}
				if ($myrow['status'] == 'Completed' or $myrow['status'] == 'Cancelled' or $myrow['status'] == 'Rejected') {
					$ViewPurchOrder = $rootpath . '/PO_OrderDetails.php?OrderNo=' . $myrow['orderno'];
				}
				else {
					$ViewPurchOrder = $rootpath . '/PO_Header.php?ModifyOrderNumber=' . $myrow['orderno'];
				}
				printf('<td><a href="' . $ViewPurchOrder . '">%s</td>
						<td>%s</td><td class="number">%s</td><td class="number">%s</td><td class="number">%s</td><td class="number">%s</td></tr>', $myrow[$summarytype], $myrow[$description], $myrow['quantityord'], number_format($myrow['extcost'], 2) , number_format($myrow['extprice'], 2) , $myrow['qtyinvoiced'], $suppname);
				$TotalQty+= $myrow['quantityord'];
				$TotalExtCost+= $myrow['extcost'];
				$TotalExtPrice+= $myrow['extprice'];
				$TotalInvQty+= $myrow['qtyinvoiced'];
			} //END WHILE LIST LOOP


			// Print totals

			printf('<tr><td>%s</td><td>%s</td><td class="number">%s</td><td class="number">%s</td><td class="number">%s</td><td class="number">%s</td></tr>', 'Totals', _('Lines - ') . $linectr, $TotalQty, number_format($TotalExtCost, 2) , number_format($TotalExtPrice, 2) , $TotalInvQty, ' ');
		} // End of if ($_POST['ReportType']

		echo '</table>';
		echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<input type="hidden" name="ReportType" value="' . $_POST['ReportType'] . '" />';
		echo '<input type="hidden" name="DateType" value="' . $_POST['DateType'] . '" />';
		echo '<input type="hidden" name="FromDate" value="' . $_POST['FromDate'] . '" />';
		echo '<input type="hidden" name="ToDate" value="' . $_POST['ToDate'] . '" />';
		echo '<input type="hidden" name="PartNumberOp" value="' . $_POST['PartNumberOp'] . '" />';
		echo '<input type="hidden" name="PartNumber" value="' . $_POST['PartNumber'] . '" />';
		echo '<input type="hidden" name="SupplierIdOp" value="' . $_POST['SupplierIdOp'] . '" />';
		echo '<input type="hidden" name="SupplierId" value="' . $_POST['SupplierId'] . '" />';
		echo '<input type="hidden" name="SupplierNameOp" value="' . $_POST['SupplierNameOp'] . '" />';
		echo '<input type="hidden" name="SupplierName" value="' . $_POST['SupplierName'] . '" />';
		echo '<input type="hidden" name="OrderNo" value="' . $_POST['OrderNo'] . '" />';
		echo '<input type="hidden" name="LineStatus" value="' . $_POST['LineStatus'] . '" />';
		echo '<input type="hidden" name="Category" value="' . $_POST['Category'] . '" />';
		echo '<input type="hidden" name="SortBy" value="' . $_POST['SortBy'] . '" />';
		echo '<input type="hidden" name="SummaryType" value="' . $_POST['SummaryType'] . '" />';
		echo '<br /><div class="centre"><input type="submit" name="submitcsv" value="' . _('Export as csv file') . '" /></div></td>';
		echo '<br /><div class="centre"><a href="">' . _('Return to Selection') . '</a></div>';
		echo '</form>';
	} // End of if inputerror != 1


} // End of function submit()


//####_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT####


function submitcsv(&$db, $PartNumber, $PartNumberOp, $SupplierId, $SupplierIdOp, $SupplierName, $SupplierNameOp, $SaveSummaryType) {

	//initialize no input errors
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (!Is_Date($_POST['FromDate'])) {
		$InputError = 1;
		prnMsg(_('Invalid From Date') , 'error');
	}
	if (!Is_Date($_POST['ToDate'])) {
		$InputError = 1;
		prnMsg(_('Invalid To Date') , 'error');
	}

	# Add more to WHERE statement, if user entered something for the part number,supplierid, name
	$WherePart = ' ';
	if (mb_strlen($PartNumber) > 0 && $PartNumberOp == 'LIKE') {
		$PartNumber = $PartNumber . '%';
	}
	else {
		$PartNumberOp = '=';
	}
	if (mb_strlen($PartNumber) > 0) {
		$WherePart = " AND purchorderdetails.itemcode " . $PartNumberOp . " '" . $PartNumber . "'  ";
	}
	else {
		$WherePart = ' ';
	}
	$WhereSupplierID = ' ';
	if ($SupplierIdOp == 'LIKE') {
		$SupplierId = $SupplierId . '%';
	}
	else {
		$SupplierIdOp = '=';
	}
	if (mb_strlen($SupplierId) > 0) {
		$WhereSupplierID = " AND purchorders.supplierno " . $SupplierIdOp . " '" . $SupplierId . "'  ";
	}
	else {
		$WhereSupplierID = ' ';
	}
	$WhereSupplierName = ' ';
	if (mb_strlen($SupplierName) > 0 && $SupplierNameOp == 'LIKE') {
		$SupplierName = $SupplierName . '%';
	}
	else {
		$SupplierNameOp = '=';
	}
	if (mb_strlen($SupplierName) > 0) {
		$WhereSupplierName = " AND suppliers.suppname " . $SupplierNameOp . " '" . $SupplierName . "'  ";
	}
	else {
		$WhereSupplierName = ' ';
	}
	if (mb_strlen($_POST['OrderNo']) > 0) {
		$WhereOrderNo = ' AND purchorderdetails.orderno = ' . " '" . $_POST['OrderNo'] . "'  ";
	}
	else {
		$WhereOrderNo = ' ';
	}
	$WhereLineStatus = ' ';

	// Had to use IF statement instead of comparing 'linestatus' to $_POST['LineStatus']

	//in WHERE clause because the WHERE clause didn't recognize


	// that had used the IF statement to create a field called linestatus

	if ($_POST['LineStatus'] != 'All') {
		if ($_POST['DateType'] == 'Order') {
			$WhereLineStatus = " AND IF(purchorderdetails.quantityord = purchorderdetails.qtyinvoiced ||
			  purchorderdetails.completed = 1,'Completed','Open') = '" . $_POST['LineStatus'] . "'";
		}
		else {
			$WhereLineStatus = " AND IF(grns.qtyrecd - grns.quantityinv <> 0,'Open','Completed') = '" . $_POST['LineStatus'] . "'";
		}
	}
	$WhereCategory = ' ';
	if ($_POST['Category'] != 'All') {
		$WhereCategory = " AND stockmaster.categoryid = '" . $_POST['Category'] . "'";
	}
	if ($InputError != 1) {
		$FromDate = FormatDateForSQL($_POST['FromDate']);
		$ToDate = FormatDateForSQL($_POST['ToDate']);
		if ($_POST['ReportType'] == 'Detail') {
			if ($_POST['DateType'] == 'Order') {
				$sql = "SELECT purchorderdetails.orderno,
							   purchorderdetails.itemcode,
							   purchorderdetails.deliverydate,
							   purchorders.supplierno,
							   purchorders.orddate,
							   purchorderdetails.quantityord,
							   purchorderdetails.qtyinvoiced,
							   (purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
							   (purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
							   IF(purchorderdetails.quantityord = purchorderdetails.qtyinvoiced ||
								  purchorderdetails.completed = 1,'Completed','Open') as linestatus,
							   suppliers.suppname,
							   stockmaster.decimalplaces,
							   stockmaster.description
							   FROM purchorderdetails
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						WHERE purchorders.orddate >='$FromDate'
						 AND purchorders.orddate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						ORDER BY " . $_POST['SortBy'];
			}
			else {

				// Selects by delivery date from grns
				$sql = "SELECT purchorderdetails.orderno,
							   purchorderdetails.itemcode,
							   grns.deliverydate,
							   purchorders.supplierno,
							   purchorders.orddate,
							   grns.qtyrecd as quantityord,
							   grns.quantityinv as qtyinvoiced,
							   (grns.qtyrecd * purchorderdetails.unitprice) as extprice,
							   (grns.qtyrecd * grns.stdcostunit) as extcost,
							   IF(grns.qtyrecd - grns.quantityinv <> 0,'Open','Completed') as linestatus,
							   suppliers.suppname,
							   stockmaster.decimalplaces,
							   stockmaster.description
							   FROM grns
						LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						WHERE grns.deliverydate >='$FromDate'
						 AND grns.deliverydate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						ORDER BY " . $_POST['SortBy'];
			}
		}
		else {

			// sql for Summary report
			$orderby = $_POST['SummaryType'];

			// The following is because the 'extprice' summary is a special case - with the other

			// summaries, you group and order on the same field; with 'extprice', you are actually


			// grouping on the stkcode and ordering by extprice descending

			if ($_POST['SummaryType'] == 'extprice') {
				$_POST['SummaryType'] = 'itemcode';
				$orderby = 'extprice DESC';
			}
			if ($_POST['DateType'] == 'Order') {
				if ($_POST['SummaryType'] == 'extprice' || $_POST['SummaryType'] == 'itemcode') {
					$sql = "SELECT purchorderdetails.itemcode,
								   SUM(purchorderdetails.quantityord) as quantityord,
								   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
								   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
								   stockmaster.decimalplaces,
								   stockmaster.description
								   FROM purchorderdetails
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE purchorders.orddate >='$FromDate'
							 AND purchorders.orddate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ",stockmaster.decimalplaces,
							  stockmaster.description
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'orderno') {
					$sql = "SELECT purchorderdetails.orderno,
								   purchorders.supplierno,
								   SUM(purchorderdetails.quantityord) as quantityord,
								   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
								   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
								   suppliers.suppname
								   FROM purchorderdetails
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE purchorders.orddate >='$FromDate'
							 AND purchorders.orddate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ",purchorders.supplierno,
							  suppliers.suppname
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'supplierno' || $_POST['SummaryType'] == 'suppname,suppliers.supplierid') {
					$sql = "SELECT purchorders.supplierno,
								   SUM(purchorderdetails.quantityord) as quantityord,
								   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
								   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
								   suppliers.suppname
								   FROM purchorderdetails
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE purchorders.orddate >='$FromDate'
							 AND purchorders.orddate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ",purchorders.supplierno,
							  suppliers.suppname
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'month') {
					$sql = "SELECT EXTRACT(YEAR_MONTH from purchorders.orddate) as month,
								   CONCAT(MONTHNAME(purchorders.orddate),' ',YEAR(purchorders.orddate)) as monthname,
								   SUM(purchorderdetails.quantityord) as quantityord,
								   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
								   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost
								   FROM purchorderdetails
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE purchorders.orddate >='$FromDate'
							 AND purchorders.orddate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ", monthname
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'categoryid') {
					$sql = "SELECT SUM(purchorderdetails.quantityord) as quantityord,
								   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
								   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
								   stockmaster.categoryid,
								   stockcategory.categorydescription
								   FROM purchorderdetails
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE purchorders.orddate >='$FromDate'
							 AND purchorders.orddate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ", categorydescription
							ORDER BY " . $orderby;
				}
			}
			else {

				// Selects by delivery date from grns
				if ($_POST['SummaryType'] == 'extprice' || $_POST['SummaryType'] == 'itemcode') {
					$sql = "SELECT purchorderdetails.itemcode,
								   SUM(grns.qtyrecd) as quantityord,
								   SUM(grns.quantityinv) as qtyinvoiced,
								   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
								   SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
								   stockmaster.description
								   FROM grns
							LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE grns.deliverydate >='$FromDate'
							 AND grns.deliverydate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ", stockmaster.description
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'orderno') {
					$sql = "SELECT purchorderdetails.orderno,
								   purchorders.supplierno,
								   SUM(grns.qtyrecd) as quantityord,
								   SUM(grns.quantityinv) as qtyinvoiced,
								   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
								   SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
								   suppliers.suppname
								   FROM grns
							LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE grns.deliverydate >='$FromDate'
							 AND grns.deliverydate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ", purchorders.supplierno,
							   suppliers.suppname
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'supplierno' || $_POST['SummaryType'] == 'suppname,suppliers.supplierid') {
					$sql = "SELECT purchorders.supplierno,
								   SUM(grns.qtyrecd) as quantityord,
								   SUM(grns.quantityinv) as qtyinvoiced,
								   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
								   SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
								   suppliers.suppname
								   FROM grns
							LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE grns.deliverydate >='$FromDate'
							 AND grns.deliverydate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ", purchorders.supplierno,
							   suppliers.suppname
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'month') {
					$sql = "SELECT EXTRACT(YEAR_MONTH from purchorders.orddate) as month,
								   CONCAT(MONTHNAME(purchorders.orddate),' ',YEAR(purchorders.orddate)) as monthname,
								   SUM(grns.qtyrecd) as quantityord,
								   SUM(grns.quantityinv) as qtyinvoiced,
								   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
								   SUM(grns.qtyrecd * grns.stdcostunit) as extcost
								   FROM grns
							LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE grns.deliverydate >='$FromDate'
							 AND grns.deliverydate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ",monthname
							ORDER BY " . $orderby;
				}
				elseif ($_POST['SummaryType'] == 'categoryid') {
					$sql = "SELECT stockmaster.categoryid,
								   stockcategory.categorydescription,
								   SUM(grns.qtyrecd) as quantityord,
								   SUM(grns.quantityinv) as qtyinvoiced,
								   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
								   SUM(grns.qtyrecd * grns.stdcostunit) as extcost
								   FROM grns
							LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
							LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
							LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
							LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							WHERE grns.deliverydate >='$FromDate'
							 AND grns.deliverydate <='$ToDate'
							$WherePart
							$WhereSupplierID
							$WhereSupplierName
							$WhereOrderNo
							$WhereLineStatus
							$WhereCategory
							GROUP BY " . $_POST['SummaryType'] . ",categorydescription
							ORDER BY " . $orderby;
				}
			}
		} // End of if ($_POST['ReportType']


		//echo "<br/>$sql<br/>";

		$ErrMsg = _('The SQL to find the parts selected failed with the message');
		$result = DB_query($sql, $db, $ErrMsg);
		$ctr = 0;
		$TotalQty = 0;
		$TotalExtCost = 0;
		$TotalExtPrice = 0;
		$TotalInvQty = 0;
		$FileName = $_SESSION['reports_dir'] . '/POReport.csv';
		$FileHandle = fopen($FileName, 'w');

		// Create array for summary type to display in header. Access it with $SaveSummaryType
		$Summary_Array['orderno'] = _('Order Number');
		$Summary_Array['itemcode'] = _('Part Number');
		$Summary_Array['extprice'] = _('Extended Price');
		$Summary_Array['supplierno'] = _('Customer Number');
		$Summary_Array['suppname'] = _('Customer Name');
		$Summary_Array['month'] = _('Month');
		$Summary_Array['categoryid'] = _('Stock Category');

		// Create array for sort for detail report to display in header
		$Detail_Array['purchorderdetails.orderno'] = _('Order Number');
		$Detail_Array['purchorderdetails.itemcode'] = _('Part Number');
		$Detail_Array['suppliers.supplierid,purchorderdetails.orderno'] = _('Supplier Number');
		$Detail_Array['suppliers.suppname,suppliers.supplierid,purchorderdetails.orderno'] = _('Supplier Name');

		// Display Header info
		if ($_POST['ReportType'] == 'Summary') {
			$SortBy_Display = $Summary_Array[$SaveSummaryType];
		}
		else {
			$SortBy_Display = $Detail_Array[$_POST['SortBy']];
		}
		fprintf($FileHandle, '"' . _('Purchase Order Report') . '","' . $_POST['ReportType'] . ' ' . _('By') . ' ' . $SortBy_Display . "\n");
		fprintf($FileHandle, '"' . _('Date Type') . '","' . $_POST['DateType'] . '"' . "\n");
		fprintf($FileHandle, '"' . _('Date Range') . '","' . $_POST['FromDate'] . _(' To ') . $_POST['ToDate'] . '"' . "\n");
		if (mb_strlen(trim($PartNumber)) > 0) {
			fprintf($FileHandle, '"' . _('Part Number') . '","' . $_POST['PartNumberOp'] . ' ' . $_POST['PartNumber'] . '"' . "\n");
		}
		if (mb_strlen(trim($_POST['SupplierId'])) > 0) {
			fprintf($FileHandle, '"' . _('Supplier Number') . '","' . $_POST['SupplierIdOp'] . ' ' . $_POST['SupplierId'] . '"' . "\n");
		}
		if (mb_strlen(trim($_POST['SupplierName'])) > 0) {
			fprintf($FileHandle, '"' . _('Supplier Name') . '","' . $_POST['SupplierNameOp'] . ' ' . $_POST['SupplierName'] . '"' . "\n");
		}
		fprintf($FileHandle, '"' . _('Line Item Status') . '","' . $_POST['LineStatus'] . '"' . "\n");
		fprintf($FileHandle, '"' . _('Stock Category') . '","' . $_POST['Category'] . '"' . "\n");
		if ($_POST['ReportType'] == 'Detail') {
			if ($_POST['DateType'] == 'Order') {
				fprintf($FileHandle, '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n", _('Order No') , _('Part Number') , _('Order Date') , _('Supplier No') , _('Supplier Name') , _('Order Qty') , _('Extended Cost') , _('Extended Price') , _('Invoiced Qty') , _('Line Status') , _('Item Due') , _('Part Description'));
				$linectr = 0;
				while ($myrow = DB_fetch_array($result)) {
					$linectr++;

					// Detail for both DateType of Order
					fprintf($FileHandle, '"%s","%s","%s","%s","%s",%s,%s,%s,%s,"%s","%s","%s"' . "\n", $myrow['orderno'], $myrow['itemcode'], ConvertSQLDate($myrow['orddate']) , $myrow['supplierno'], $myrow['suppname'], number_format($myrow['quantityord'], $myrow['decimalplaces']) , number_format($myrow['extcost'], 2) , number_format($myrow['extprice'], 2) , number_format($myrow['qtyinvoiced'], $myrow['decimalplaces']) , $myrow['linestatus'], ConvertSQLDate($myrow['deliverydate']) , $myrow['description']);
					$LastDecimalPlaces = $myrow['decimalplaces'];
					$TotalQty+= $myrow['quantityord'];
					$TotalExtCost+= $myrow['extcost'];
					$TotalExtPrice+= $myrow['extprice'];
					$TotalInvQty+= $myrow['qtyinvoiced'];
				} //END WHILE LIST LOOP


				// Print totals

				fprintf($FileHandle, '"%s","%s","%s","%s","%s",%s,%s,%s,%s,"%s","%s"' . "\n", 'Totals', _('Lines - ') . $linectr, ' ', ' ', ' ', number_format($TotalQty, 2) , number_format($TotalExtCost, 2) , number_format($TotalExtPrice, 2) , number_format($TotalInvQty, 2) , ' ', ' ');
			}
			else {

				// Header for Date Type of Delivery Date
				fprintf($FileHandle, '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n", _('Order No') , _('Part Number') , _('Order Date') , _('Supplier No') , _('Supplier Name') , _('Received') , _('Extended Cost') , _('Extended Price') , _('Invoiced Qty') , _('Line Status') , _('Delivered') , _('Part Description'));
				$linectr = 0;
				while ($myrow = DB_fetch_array($result)) {
					$linectr++;

					// Detail for both DateType of Ship

					// In sql, had to alias grns.qtyrecd as quantityord so could use same name here

					fprintf($FileHandle, '"%s","%s","%s","%s","%s",%s,%s,%s,%s,"%s","%s","%s"' . "\n", $myrow['orderno'], $myrow['itemcode'], ConvertSQLDate($myrow['orddate']) , $myrow['supplierno'], $myrow['suppname'], number_format($myrow['quantityord'], $myrow['decimalplaces']) , number_format($myrow['extcost'], 2) , number_format($myrow['extprice'], 2) , number_format($myrow['qtyinvoiced'], $myrow['decimalplaces']) , $myrow['linestatus'], ConvertSQLDate($myrow['deliverydate']) , $myrow['description']);
					$LastDecimalPlaces = $myrow['decimalplaces'];
					$TotalQty+= $myrow['quantityord'];
					$TotalExtCost+= $myrow['extcost'];
					$TotalExtPrice+= $myrow['extprice'];
					$TotalInvQty+= $myrow['qtyinvoiced'];
				} //END WHILE LIST LOOP


				// Print totals

				fprintf($FileHandle, '"%s","%s","%s","%s","%s",%s,%s,%s,%s,"%s","%s"' . "\n", 'Totals', _('Lines - ') . $linectr, ' ', ' ', ' ', number_format($TotalQty, $LastDecimalPlaces) , number_format($TotalExtCost, 2) , number_format($TotalExtPrice, 2) , number_format($TotalInvQty, $LastDecimalPlaces) , " ", " ");
			}
		}
		else {

			// Print summary stuff
			$summarytype = $_POST['SummaryType'];

			// For SummaryType 'suppname' had to add supplierid to it for the GROUP BY in the sql,

			// but have to take it away for $myrow[$summarytype] to be valid


			// Set up description based on the Summary Type

			if ($summarytype == 'suppname,suppliers.supplierid') {
				$summarytype = 'suppname';
				$description = 'supplierno';
				$summaryheader = _('Supplier Name');
				$descriptionheader = _('Supplier Number');
			}
			if ($summarytype == 'itemcode' || $summarytype == 'extprice') {
				$description = 'description';
				$summaryheader = _('Part Number');
				$descriptionheader = _('Part Description');
			}
			if ($summarytype == 'supplierno') {
				$description = 'suppname';
				$summaryheader = _('Supplier Number');
				$descriptionheader = _('Supplier Name');
			}
			if ($summarytype == 'orderno') {
				$description = 'supplierno';
				$summaryheader = _('Order Number');
				$descriptionheader = _('Supplier Number');
			}
			if ($summarytype == 'categoryid') {
				$description = 'categorydescription';
				$summaryheader = _('Stock Category');
				$descriptionheader = _('Category Description');
			}
			$summarydesc = $summaryheader;
			if ($orderby == 'extprice DESC') {
				$summarydesc = _('Extended Price');
			}
			if ($summarytype == 'month') {
				$description = 'monthname';
				$summaryheader = _('Month');
				$descriptionheader = _('Month');
			}
			fprintf($FileHandle, '"%s","%s","%s","%s","%s","%s"' . "\n", _($summaryheader) , _($descriptionheader) , _('Quantity') , _('Extended Cost') , _('Extended Price') , _('Invoiced Qty'));
			$suppname = ' ';
			$linectr = 0;
			while ($myrow = DB_fetch_array($result)) {
				$linectr++;
				if ($summarytype == 'orderno') {
					$suppname = $myrow['suppname'];
				}
				fprintf($FileHandle, '"%s","%s",%s,%s,%s,%s,"%s"' . "\n", $myrow[$summarytype], $myrow[$description], number_format($myrow['quantityord'], $myrow['decimalplaces']) , number_format($myrow['extcost'], 2) , number_format($myrow['extprice'], 2) , number_format($myrow['qtyinvoiced'], $myrow['decimalplaces']) , $suppname);
				print '<br/>';
				$LastDecimalPlaces = $myrow['decimalplaces'];
				$TotalQty+= $myrow['quantityord'];
				$TotalExtCost+= $myrow['extcost'];
				$TotalExtPrice+= $myrow['extprice'];
				$TotalInvQty+= $myrow['qtyinvoiced'];
			} //END WHILE LIST LOOP


			// Print totals

			fprintf($FileHandle, '"%s","%s",%s,%s,%s,%s,"%s"' . "\n", 'Totals', _('Lines - ') . $linectr, number_format($TotalQty, $LastDecimalPlaces) , number_format($TotalExtCost, 2) , number_format($TotalExtPrice, 2) , number_format($TotalInvQty, $LastDecimalPlaces) , ' ');
		} // End of if ($_POST['ReportType']

		fclose($FileHandle);
		echo '<div class="centre"><p>' . _('The report has been exported as a csv file.') . '</p>';
		echo '<p><a href="' . $FileName . '">' . _('click here') . '</a> ' . _('to view the file') . '</div></p>';
		echo '<div class="centre"><a href="">' . _('Return to Selection') . '</a></div>';
	} // End of if inputerror != 1


} // End of function submitcvs()


function display(&$db) //####DISPLAY_DISPLAY_DISPLAY_DISPLAY_DISPLAY_DISPLAY_#####

{

	// Display form fields. This function is called the first time

	// the page is called.

	echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">';
	echo '<tr><td>' . _('Report Type') . ':</td>';
	echo '<td><select name="ReportType">';
	echo '<option selected="True" value="Detail">' . _('Detail') . '</option>';
	echo '<option value="Summary">' . _('Summary') . '</option>';
	echo '</select></td><td>&nbsp</td></tr>';
	echo '<tr><td>' . _('Date Type') . ':</td>';
	echo '<td><select name="DateType">';
	echo '<option selected="True" value="Order">' . _('Order Date') . '</option>';
	echo '<option value="Delivery">' . _('Delivery Date') . '</option>';
	echo '</select></td><td>&nbsp</td></tr>';
	echo '<tr>
		<td>' . _('Date Range') . ':</td>
		<td><input type="text" class="date" alt="' . $_SESSION['DefaultDateFormat'] . '" name="FromDate" size="10" maxlength="10" value="' . $_POST['FromDate'] . '" /> ' . _('To') . ':&nbsp&nbsp
		<input type="text" class="date" alt="' . $_SESSION['DefaultDateFormat'] . '" name="ToDate" size="10" maxlength="10" value="' . $_POST['ToDate'] . '" /></td>
	</tr>';
	echo '<tr><td>' . _('Part Number') . ':</td>';
	echo '<td><select name="PartNumberOp">';
	echo '<option selected="True" value="Equals">' . _('Equals') . '</option>';
	echo '<option value="LIKE">' . _('Begins With') . '</option>';
	echo '</select>';
	echo '&nbsp&nbsp';
	if (isset($_POST['PartNumber'])) {
		echo '<input type="text" name="PartNumber" size="20" maxlength="20" value="' . $_POST['PartNumber'] . '" /></td></tr>';
	}
	else {
		echo '<input type="text" name="PartNumber" size="20" maxlength="20" value="" /></td></tr>';
	}
	echo '<tr><td>' . _('Supplier Number') . ':</td>';
	echo '<td><select name="SupplierIdOp">';
	echo '<option selected="True" value="Equals">' . _('Equals') . '</option>';
	echo '<option value="LIKE">' . _('Begins With') . '</option>';
	echo '</select>';
	echo '&nbsp&nbsp';
	if (isset($_POST['SupplierId'])) {
		echo '<input type="text" name="SupplierId" size="10" maxlength="10" value="' . $_POST['SupplierId'] . '" /></td></tr>';
	}
	else {
		echo '<input type="text" name="SupplierId" size="10" maxlength="10" value="" /></td></tr>';
	}
	echo '<tr><td>' . _('Supplier Name') . ':</td>';
	echo '<td><select name="SupplierNameOp">';
	echo '<option selected="True" value="LIKE">' . _('Begins With') . '</option>';
	echo '<option value="Equals">' . _('Equals') . '</option>';
	echo '</select>';
	echo '&nbsp&nbsp';
	if (isset($_POST['SupplierName'])) {
		echo '<input type="text" name="SupplierName" size="30" maxlength="30" value="' . $_POST['SupplierName'] . '" /></td></tr>';
	}
	else {
		echo '<input type="text" name="SupplierName" size="30" maxlength="30" value="" /></td></tr>';
	}
	echo '<tr><td>' . _('Order Number') . ':</td>';
	echo '<td>' . _('Equals') . ':&nbsp&nbsp';
	if (isset($_POST['OrderNo'])) {
		echo '<input type="text" name="OrderNo" size="10" maxlength="10" value="' . $_POST['OrderNo'] . '" /></td></tr>';
	}
	else {
		echo '<input type="text" name="OrderNo" size="10" maxlength="10" value="" /></td></tr>';
	}
	echo '<tr><td>' . _('Line Item Status') . ':</td>';
	echo '<td><select name="LineStatus">';
	echo '<option selected="True" value="All">' . _('All') . '</option>';
	echo '<option value="Completed">' . _('Completed') . '</option>';
	echo '<option value="Open">' . _('Not Completed') . '</option>';
	echo '</select></td><td>&nbsp</td></tr>';
	echo '<tr><td>' . _('Stock Categories') . ':</td><td> <select name="Category">';
	$sql = "SELECT categoryid, categorydescription FROM stockcategory";
	$CategoryResult = DB_query($sql, $db);
	echo '<option selected="True" value="All">' . _('All Categories') . '</option>';
	While ($myrow = DB_fetch_array($CategoryResult)) {
		echo '<option value="' . $myrow['categoryid'] . '">' . $myrow['categorydescription'] . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><td>&nbsp</td></tr>';
	echo '<tr><td>' . _('Sort By') . ':</td>';
	echo '<td><select name="SortBy">';
	echo '<option selected="True" value="purchorderdetails.orderno">' . _('Order Number') . '</option>';
	echo '<option value="purchorderdetails.itemcode">' . _('Part Number') . '</option>';
	echo '<option value="suppliers.supplierid,purchorderdetails.orderno">' . _('Supplier Number') . '</option>';
	echo '<option value="suppliers.suppname,suppliers.supplierid,purchorderdetails.orderno">' . _('Supplier Name') . '</option>';
	echo '</select></td><td>&nbsp</td></tr>';
	echo '<tr><td>&nbsp</td></tr>';
	echo '<tr><td>' . _('Summary Type') . ':</td>';
	echo '<td><select name="SummaryType">';
	echo '<option selected="True" value="orderno">' . _('Order Number') . '</option>';
	echo '<option value="itemcode">' . _('Part Number') . '</option>';
	echo '<option value="extprice">' . _('Extended Price') . '</option>';
	echo '<option value="supplierno">' . _('Supplier Number') . '</option>';
	echo '<option value="suppname">' . _('Supplier Name') . '</option>';
	echo '<option value="month">' . _('Month') . '</option>';
	echo '<option value="categoryid">' . _('Stock Category') . '</option>';
	echo '</select></td><td>&nbsp</td></tr>';
	echo '<tr><td>&nbsp</td></tr>
	<tr>
		<td colspan="4"><div class="centre"><input type="submit" name="submit" value="' . _('Run Inquiry') . '" /></div></td>
	</tr>
	<tr>
		<td colspan="4"><div class="centre"><input type="submit" name="submitcsv" value="' . _('Export as csv file') . '" /></div></td>
	</tr>
	</table>
	<br/>';
	echo '</form>';
} // End of function display()

include ('includes/footer.inc');
?>
<?php
	/**
	 * Returns the JSON version of a REC registry file
	 */

	define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
	date_default_timezone_set('Australia/Melbourne');

	# Includes
	require_once("inc/error.inc.php");
	require_once("inc/database.inc.php");
	require_once("inc/security.inc.php");
	require_once("inc/json.pdo.inc.php");
	require_once("PHPExcel/Classes/PHPExcel/IOFactory.php");


	# Performs the query and returns XML or JSON
	try {
		// File name to the XLS spreadsheet
		$p_file_url = "http://ret.cleanenergyregulator.gov.au/ArticleDocuments/327/RET-data-0315.xls.aspx";
		if (isset($_REQUEST['file_url']))
		{
			$p_file_url = $_REQUEST['file_url'];
		}

		// Sheet name
		$p_sheet = "SGU-Solar Panel";
		if (isset($_REQUEST['sheet']))
		{
			$p_sheet=$_REQUEST['sheet'];
		}

		// Mapping: columns to JSON attributes
		$p_col_postcode = 'A';
		if (isset($_REQUEST['col_postcode']))
		{
			$p_col_postcode=$_REQUEST['col_postcode'];
		}
		$p_col_qty = 'AH';
		if (isset($_REQUEST['col_qty']))
		{
			$p_col_qty=$_REQUEST['col_qty'];
		}
		$p_col_kw = 'AI';
		if (isset($_REQUEST['col_kw']))
		{
			$p_col_kw=$_REQUEST['col_kw'];
		}
		$col_array = array($p_col_postcode=>"postcode",$p_col_qty=>"install_qty",$p_col_kw=>"rated_output_kw");
		$col_array_keys=array_keys($col_array);

		// Header may contain some interesting info (like data relevance date)
		$headerLinesNb = 4;

		// Download the file
		$file_in_a_string = file_get_contents($p_file_url);

		// Writing the file to staging area. Do we loose anything in this process?
		$staged_file_path = realpath('staging').'/'.basename($p_file_url);
		file_put_contents($staged_file_path , $file_in_a_string);
		//echo date('H:i:s') , " Loading Excel file: ".$staged_file_path , EOL;
		$callStartTime = microtime(true);

		// Reader filter to minimise memory consumption
		class MyReadFilter implements PHPExcel_Reader_IReadFilter
		{
			public function readCell($column, $row, $worksheetName = '') {
				global $p_sheet, $headerLinesNb, $col_array_keys;
				if ($worksheetName == $p_sheet)
				{
					if (in_array($column,$col_array_keys)) {
						//if ($row >= $headerLinesNb) {
							return true;
						//}
						return false;
					}
					return false;
				}
				return false;
			}
		}
		//echo date('H:i:s') , " Create Excel5 reader" , EOL;
		$objReader = PHPExcel_IOFactory::createReader('Excel5');
		$objReader->setReadFilter( new MyReadFilter() );
		$objReader->setLoadSheetsOnly($p_sheet);
		$objReader->setReadDataOnly(true);
		//echo date('H:i:s') , " Load from file" , EOL;
		$objPHPExcel = $objReader->load($staged_file_path);

		//$objPHPExcel = PHPExcel_IOFactory::load($staged_file_path);
		$callEndTime = microtime(true);
		$callTime = $callEndTime - $callStartTime;
		// Echo loading time
		//echo 'Call time to read file was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
		// Echo memory usage
		//echo date('H:i:s') , ' Current memory usage: ' , (memory_get_usage(true) / 1024 / 1024) , " MB" , EOL;

		// Do something with the file => manipulate and expose as JSON
		$arr = array();
		foreach ($objPHPExcel->getActiveSheet()->getRowIterator() as $row) {

			//echo '    Row number - ' , $row->getRowIndex() , EOL;
			if ($row->getRowIndex()>$headerLinesNb)
			{
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set

				$arr_line = array();
				foreach ($cellIterator as $cell) {
					//echo '        Cell - ' , $cell->getColumn() , ' - ' , $cell->getCalculatedValue() , EOL;
					if (in_array($cell->getColumn(),$col_array_keys))  {
						//echo '        Cell - ' , $cell->getColumn() , ' - ' ,$col_array[$cell->getColumn()],' - ', $cell->getCalculatedValue() , EOL;
						$cell_val = $cell->getCalculatedValue();
						if ($cell->getColumn()==$p_col_kw)
						{
							$cell_val = round(floatval($cell->getCalculatedValue()),1);
						}
						$arr_line[$col_array[$cell->getColumn()]] = $cell_val;
					}
				}
				// If no postcode information, don't add it to the output json
				//echo print_r($arr_line);
				if (isset($arr_line["postcode"]) && $arr_line["postcode"])
				{
					$arr[]=$arr_line;
				}
			}

		}

		$date=new DateTime(); //this returns the current date time
		$datestr = $date->format('Ymd-His');

		$overall_arr=array("meta"=>array("file"=>$p_file_url,"sheet"=>$p_sheet,"col_postcode"=>$p_col_postcode,"col_qty"=>$p_col_qty,"col_kw"=>$p_col_kw,"internal_timestamp"=>$objPHPExcel->getActiveSheet()->getCell("A3")->getFormattedValue(),"json_timestamp"=>$datestr),"data"=>$arr);

		// Required to cater for IE
		//header("Content-Type: application/json");
		header("Content-Type: text/html");
		// Allow CORS
		header("Access-Control-Allow-Origin: *");

		// Outputting the content
		echo json_encode($overall_arr, JSON_PRETTY_PRINT);
	}
	catch (Exception $e) {
		trigger_error("Caught Exception: " . $e->getMessage(), E_USER_ERROR);
	}

?>
<?php

namespace AMovil\Shared\Exports\Infrastructure;

use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\Writer;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory as IOFactorySpread;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SpreedSheetExport implements ExportService
{
    private $objPHPExcel;
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->objPHPExcel = $spreadsheet;
    }

	public function reset(){
		$this->objPHPExcel = new Spreadsheet();
	}

    public function loadData($headers, $data, $options = [])
	{
        $objPHPExcel = $this->objPHPExcel;
		// options
		$sheetIndex = isset($options['sheetIndex']) ? $options['sheetIndex'] : 0;
		$rowType = isset($options['rowType']) ? $options['rowType'] : 'object';
		$validateProps = isset($options['validateProps']) ? $options['validateProps'] : false;
		$title = isset($options['title']) ? $options['title'] : 'REGISTROS';
		$fillWith = isset($options['fillWith']) ? $options['fillWith'] : '';
		$x_start_index = isset($options['x_start_index']) ? $options['x_start_index']+1 : 1;
		$y_start_index = isset($options['y_start_index']) ? $options['y_start_index'] : 0;
		$styles = isset($options['styles']) ? $options['styles'] : [];
		$headerStyles = isset($styles['header']) ? $styles['header'] : [];
		$bodyStyles = isset($styles['body']) ? $styles['body'] : [];

        try {
            $objPHPExcel->setActiveSheetIndex($sheetIndex);
        } catch (\Throwable $th) {
            if($sheetIndex !== 0){
                $objPHPExcel->createSheet();
            }
            $objPHPExcel->setActiveSheetIndex($sheetIndex);
        }
		$objPHPExcel->getActiveSheet()->setTitle($title);

		// default styles
		foreach($styles as $rango => $style){
			if(str_contains($rango, ':')){
				/*$objPHPExcel->getActiveSheet()->getStyle($rango)->getAlignment()->setHorizontal(SpreadsheetStyle\Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle($rango)->getFont()->setSize(8);
				$objPHPExcel->getActiveSheet()->getStyle($rango)->getAlignment()->setWrapText(true);*/

                $objPHPExcel->getActiveSheet()->getStyle($rango)->applyFromArray($style);
			}
		}

		// excel headers
		$i = $x_start_index;
		foreach($headers as $name => $header){
			$label = isset($header['label']) ? $header['label'] : $name;

			$objPHPExcel->getActiveSheet()
				->setCellValueByColumnAndRow($i, $y_start_index + 1, $label);
			$objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($i, $y_start_index + 1)->getFont()->setBold(true);

            if(count($headerStyles) !== 0){
                $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($i, $y_start_index + 1)->applyFromArray($headerStyles);
            }

			if(array_key_exists('mapValue', $header)){
				$headers[$name]['hasMapValue'] = true;
			}else{
				$headers[$name]['hasMapValue'] = false;
			}
            
			$i++;
		}

		$i = $y_start_index;
		if ($rowType === 'object') {
			if ($validateProps) {
				foreach($data as $index => $row){
					$j = $x_start_index;
					foreach($headers as $name => $header){
						$value = $fillWith;
						if(property_exists($row, $name)){
							$value = $row->{$name};
						}
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($j, $i+2, $value);
						$j++;
					}
					$i++;
				}
			}else{
				// foreach($data as $index => $row){
				// 	$j = $x_start_index;
				// 	foreach($headers as $name => $header){
				// 		$value = $row->{$name};
				// 		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($j, $i+2, $value);
				// 		$j++;
				// 	}
				// 	$i++;
				// }
				$plain_data = [];
				foreach($data as $index => $row){
					$j = $x_start_index;
					$plain_row = [];
					foreach($headers as $name => $header){
						$plain_row[] = $row->{$name};
					}
					$plain_data[$index] = $plain_row;
					$data[$index] = null;
				}

				$startLetter = Coordinate::stringFromColumnIndex($x_start_index);
				$startIndex = $y_start_index+2;
				// dd($plain_data);
				$objPHPExcel->getActiveSheet()->fromArray($plain_data, NULL, "{$startLetter}{$startIndex}");
			}
		} else {
			if ($validateProps) {
				foreach($data as $index => $row){
					$j = $x_start_index;
					foreach($headers as $name => $header){
						$value = $fillWith;
						if(array_key_exists($name, $row)){
							$value = $row[$name];
						}
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($j, $i+2, $value);
						$j++;
					}
					$i++;
				}
			}else{
				foreach($data as $index => $row){
					$j = $x_start_index;
					foreach($headers as $name => $header){
						$objPHPExcel->getActiveSheet()
						->setCellValueExplicitByColumnAndRow($j, $i+2, $row[$name], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
						// $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($j, $i+2, $row[$name]);
						$j++;
					}
					$i++;
				}
			}
		}

		$startLetter = Coordinate::stringFromColumnIndex($x_start_index);
		$startIndex = $y_start_index+2;
		$endLetter = Coordinate::stringFromColumnIndex($x_start_index + count($headers)-1);
		$endIndex = ($y_start_index + 2 + count($data)-1);

		if($startIndex <= $endIndex){
			if(count($bodyStyles) !== 0){
				$objPHPExcel->getActiveSheet()
				->getStyle("{$startLetter}{$startIndex}:{$endLetter}{$endIndex}")
				->applyFromArray($bodyStyles);
			}
			$j = $x_start_index;
			foreach($headers as $name => $header){
				if(array_key_exists('bodyStyles', $header)){
					$letter = Coordinate::stringFromColumnIndex($j);

					$objPHPExcel->getActiveSheet()
					->getStyle("{$letter}{$startIndex}:{$letter}{$endIndex}")
					->applyFromArray($header['bodyStyles']);
				}
				$j++;
			}
		}

        $objPHPExcel->setActiveSheetIndex(0);
	}

    public function makeChart($data, $options)
    {
        //
    }

    public function getExportReference(){
        return $this->objPHPExcel;
    }

    public function download($filename){
        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        // ob_end_clean();

        $sp_writer = IOFactorySpread::createWriter($this->objPHPExcel, 'Xlsx');
        $sp_writer->setIncludeCharts(true);
        $sp_writer->save('php://output');
    }

	public function getWriter($type): Writer
	{
		WriterType::guard($type);
		$writer = IOFactorySpread::createWriter($this->objPHPExcel, $type);
		return new SpreedSheetWriter($writer);
	}
}
